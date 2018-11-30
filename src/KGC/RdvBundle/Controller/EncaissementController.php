<?php

// src/KGC/RdvBundle/Controller/EncaissementController.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\CommonBundle\Service\Paginator;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Entity\Tiroir;
use KGC\RdvBundle\Entity\TPE;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Exception\Payment\PaymentRefusedException;
use KGC\StatBundle\Calculator\UnpaidCalculator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use KGC\ClientBundle\Entity\Historique;

/**
 * EncaissementController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class EncaissementController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCRdvBundle:Encaissement';
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function WidgetListeAction(Request $request)
    {
        $displayFrom = $request->query->get('display');
        $rep_enc = $this->getRepository();
        $liste = $rep_enc->getToDo();
        $display = array();
        $compact = array();
        foreach ($liste as $enc) {
            if ($enc->getConsultation()->getDateConsultation()->format('dmY') == date('dmY')) {
                $display[] = $enc;
            } else {
                $compact[] = $enc;
            }
        }

        $display = 'unpaid' === $displayFrom ? array_merge($compact, $display) : $display;
        $compact = 'unpaid' === $displayFrom ? [] : $compact;

        return $this->render('KGCRdvBundle:Encaissement:encaissements.widget.html.twig', array(
            'display' => $display,
            'compact' => $compact,
            'display_from' => $displayFrom,
            'rdv_counts' => count($liste),
        ));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONE")
     */
    public function WidgetDoneListeAction(Request $request, $type)
    {
        $unpaidWidgets = $request->getSession()->get('unpaidwidget') ?: [];

        $begin = new \DateTime(
            isset($unpaidWidgets['begin'])
                ? $unpaidWidgets['begin']
                : null
        );
        $end = new \DateTime(
            isset($unpaidWidgets['end'])
                ? $unpaidWidgets['end']
                : null
        );

        $form = $this->createForm(new Form\ImpayeType(['begin' => $begin, 'end' => $end]));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $period = $form->get('period')->getData();

            $request->getSession()->set('unpaidwidget', ['begin' => $period['begin']->format('Y-m-d'), 'end' => $period['end']->format('Y-m-d')]);

            $period['end']->modify('+1 day');

            $receipts = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:Encaissement')->getDone($type, $period);
        } else {
            $type = 'done';

            $receipts = null;
        }

        switch ($type) {
            case 'denied':
                $title = 'Refusés';
                break;
            case 'cbi':
                $title = 'C.B.I';
                break;
            default:
                $title = 'Acceptés';
                break;
        }

        return $this->render('KGCRdvBundle:Encaissement:done.widget.html.twig', ['receipts' => $receipts, 'title' => $title, 'type' => $type]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function exportDoneAction(Request $request, $type, $format)
    {
        $unpaidWidgets = $request->getSession()->get('unpaidwidget') ?: [];

        $begin = new \DateTime(
            isset($unpaidWidgets['begin'])
                ? $unpaidWidgets['begin']
                : null
        );
        $end = new \DateTime(
            isset($unpaidWidgets['end'])
                ? $unpaidWidgets['end']
                : null
        );

        $end->modify('+1 day');

        $query = $this->get('doctrine.orm.entity_manager')
            ->getRepository('KGCRdvBundle:Encaissement')
            ->getDoneRdvQuery($type, ['begin' => $begin, 'end' => $end]);

        $exporter = $this->get('kgc.rdv.csv_exporter');

        $formatter = null;
        if ($format == 'rdv-crm') {
            $formatter = $this->get('kgc.elastic.rdv.crmrdv_formatter');
        } else {
            $formatter = $this->get('kgc.elastic.rdv.rdv_formatter');
        }

        if ($exporter->export($query, $formatter)) {
            return $exporter->getResponse();
        }
    }

    /**
     * ModalTraitementAction
     * Formulaire de traitement d'un encaissement.
     *
     * @param int $id id de l'encaissement
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function ModalTraitementAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $suiviRdvManager = $this->get('kgc.suivirdv.manager');
        $enc = $this->findById($id);
        if ($enc and $enc->getEtat() == Encaissement::PLANNED) {
            $rdv = $enc->getConsultation();
            $request = $this->get('request');
            $request->getSession()->set('original_rdv', clone $rdv);
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVEncaissementType($currUser, $enc, $em, $param_edit, true, $this->getUser()->getIsDecryptAvailable()), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                if ($enc->getEtat() == Encaissement::DENIED && $enc->getTpe() instanceof TPE && $enc->getTpe()->getPaymentGateway() !== null) {
                    $gateway = $this->get('kgc.payment.factory')->get($enc->getTpe()->getPaymentGateway());
                    $e = $gateway->getPaymentException($enc->getPayment());
                    if ($e instanceof InvalidCardDataException) {
                        $message = 'Coordonnées bancaires invalides';
                    } else if ($e instanceof PaymentRefusedException) {
                        $message = 'Paiement refusé';
                    } else {
                        $message = 'Impossible de procéder au paiement';
                    }
                    if ($e->getMessage()) {
                        $message .= ' ('.($e->getCode() > 0 ? $e->getCode().' - ' : '').$e->getMessage().')';
                    }
                    $this->addFlash('error#euro', $client.'--'.$message);
                } else {
                    $this->addFlash('light#euro-light', $client.'--Traitement de lʼencaissement enregistré.');
                }

                $close = $form['fermeture']->getData();
                if (
                    !$close
                    || $enc->getEtat() == Encaissement::DENIED
                    || ($enc->getEtat() == Encaissement::DONE && $enc->getTpe() instanceof TPE && $enc->getTpe()->getPaymentGateway() !== null)
                ) {
                    return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
                }
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#euro', $client.'--Traitement de lʼencaissement non enregistré.');
            }
        }

        return $this->render('KGCRdvBundle:Encaissement:modal.html.twig', array(
            'rdv' => $enc->getConsultation(),
            'enc' => $enc,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : null,
            'close' => isset($close) ? $close : false,
        ));
    }

    /**
     * Marquer un encaissement en Opposition.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function OppositionAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $suiviRdvManager = $this->get('kgc.suivirdv.manager');
        $enc = $this->findById($id);
        if ($enc and $enc->getEtat() == Encaissement::DONE) {
            $rdv = $enc->getConsultation();
            $request = $this->get('request');
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVOppositionType($currUser, $enc, $em), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result === true) { // form submit valid
                $this->addFlash('light#euro-light', $client.'--Opposition enregistrée.');

                return $this->redirect($this->get('router')->generate('kgc_rdv_fiche', array('id' => $rdv->getId())));
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#euro', $client.'--Opposition non enregistrée.');
            }
        }

        return $this->render('KGCRdvBundle:Encaissement:opposition.html.twig', array(
            'rdv' => $enc->getConsultation(),
            'enc' => $enc,
            'form' => isset($form) ? $form->createView() : null,
        ));
    }

    /**
     * Méthode WidgetFNAListeAction
     * Widget de visualisation/recherche de fiche dans tiroir FNA.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function WidgetFNAListeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $rep_tir = $em->getRepository('KGCRdvBundle:Tiroir');
        $rep_dos = $em->getRepository('KGCRdvBundle:Dossier');

        $tiroirFNA = $rep_tir->findOneByIdcode(Tiroir::UNPAID);
        $list[] = $tiroirFNA;
        $list = array_merge($list, $rep_dos->getDossiersSwitchTiroirCode(Tiroir::UNPAID, true));

        return $this->render('KGCRdvBundle:Encaissement:widgetFNA.html.twig', array(
            'list' => $list,
        ));
    }

    /**
     * @return UnpaidCalculator
     */
    protected function getUnpaidCalculator()
    {
        return $this->get('kgc.stat.calculator.unpaid');
    }

    /**
     * Méthode WidgetStatFNAListeAction
     * Widget de visualisation/recherche des stats du FNA.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function WidgetStatFNAListeAction(Request $request)
    {
        $request = $this->get('request');
        $date = $request->getSession()->has('fna_stat_widget_list') ? $request->getSession()->get('fna_stat_widget_list'): new \DateTime();

        $form = $this->createFormBuilder()
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'empty_data' => '01/' . date('m/Y'),
                'limit-size' => true,
                'attr' => array(
                    'class' => 'date-picker-month',
                ),
            ))
            ->getForm()
        ;
        $form['date']->setData($date);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $date = $form['date']->getData();
            $request->getSession()->set('fna_stat_widget_list', $date);
        } elseif ($form->isSubmitted()) {
            if ($form['date']->getData() === null) {
                $request->getSession()->remove('fna_stat_widget_list');
            }
        }

        $params = $this->getUnpaidCalculator()->calculate([
            'date' => $form->get('date')->getData(),
            'get_unpaid' => true,
        ]);

        return $this->render('KGCRdvBundle:Encaissement:widgetStatFNA.html.twig', array(
            'form' => $form->createView(),
            'date' => $date,
            'list' => $params['unpaid']
        ));
    }

    /**
     * Méthode WidgetStatFNAListeDetailAction
     * Widget de visualisation/recherche des stats du FNA.
     *
     * @param $classement
     * @param $date
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function WidgetStatFNAListeDetailAction(Request $request, $classement, $date)
    {
        $export = $request->query->has('export');
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        if($classement == 'TOTAL') {
            $rep_tir = $em->getRepository('KGCRdvBundle:Tiroir');
            $rep_dos = $em->getRepository('KGCRdvBundle:Dossier');
            $tiroirFNA = $rep_tir->findOneByIdcode(Tiroir::UNPAID);
            $list[] = $tiroirFNA;
            $list = array_merge($list, $rep_dos->getDossiersSwitchTiroirCode(Tiroir::UNPAID, true));
            $classements = [];
            foreach ($list as $classement) {
                $classements[] = $classement->getId();
            }
            $title = 'TOTAL';
        } else {
            $classements = [$classement];
            $classementObject = $em->getRepository('KGCRdvBundle:Classement')->find($classement);
            $title = $classementObject->getLibelle();
        }
        if($date != 'TOTAL') {
            $startDate = new \DateTime($date);
            $endClone = null;
            if ($startDate) {
                $endClone = clone $startDate;
                $endClone->modify('last day of this month');
                $endClone->add(new \DateInterval('P1D'));
            }
            $dateinterval = ['begin' => $startDate, 'end' => $endClone];
            $title .= ' ('.$startDate->format('m/Y').')';
            $rdvs = $rep_rdv->getUnpaidByClassement($classements, null, $dateinterval, 'rdv', array());
        } else {
            $end = $request->getSession()->get('fna_stat_widget_list');
            $end->modify('+1month');
            $startDate = clone $end;
            $startDate->modify('-12month');
            $dateinterval = ['begin' => $startDate, 'end' => $end];
            $rdvs = $rep_rdv->getUnpaidByClassement($classements, null, $dateinterval, 'rdv', array());
            $title .= ' (TOTAL)';
        }

        if($export) {
            $csv = $this->getUnpaidCalculator()->getCsvRdvs($rdvs);
            return new Response($csv, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_impaye_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }
        return $this->render('KGCRdvBundle:Encaissement:unpaid_rdv_details.html.twig', [
            'fiches' => $rdvs,
            'title' => $title
        ]);
    }

    /**
     * @param $idclassement
     * @param string $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function ListeFicheTabAction($idclassement, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $request = $this->get('request');
        $export = $request->query->has('export');
        if ($request->getMethod() == 'POST') {
            if ($request->request->has('rdv', false)) {
                $rdv = $request->request->get('rdv', false);
                foreach ($rdv as $id_consultation => $array) {
                    $stateId = $array['state'] ? : null;
                    $dateState = isset($array['dateState']) && $array['dateState'] ? $array['dateState'] : null;
                    $realDateState = $state = null;
                    if ($dateState) {
                        $explode = explode(' ', $dateState);
                        if (count($explode)) {
                            $date = $explode[0];
                            if (isset($explode[1])) {
                                $hour = $explode[1];
                            }
                            $explodeDate = explode('/', $date);
                            if (count($explodeDate) == 3) {
                                $hour = $hour ? $hour . ':00' : '00:00:00';
                                $date = new \DateTime($explodeDate[2] . '-' . $explodeDate[1] . '-' . $explodeDate[0]);
                                if ($hour) {
                                    $explodeHour = explode(':', $hour);
                                    if (count($explodeHour)) {
                                        $date->setTime($explodeHour[0] ?: 0, $explodeHour[1] ?: 0);
                                    }
                                }
                                $realDateState = $date;
                            }
                        }
                    }
                    if ($stateId) {
                        $state = $em->getRepository('KGCRdvBundle:RdvState')->find($stateId);
                    }
                    $rdv = $rep_rdv->find($id_consultation);
                    $rdv->setState($state);
                    $rdv->setDateState($realDateState);
                    $em->persist($rdv);
                }
                $em->flush();
            }
        }

        $unpaidtabs = $request->getSession()->get('unpaidtab') ?: [];

        $begin = isset($unpaidtabs[$idclassement]) ? $unpaidtabs[$idclassement]['begin'] : null;
        $end = isset($unpaidtabs[$idclassement]) ? $unpaidtabs[$idclassement]['end'] : null;
        $periodField = isset($unpaidtabs[$idclassement]['periodField']) ? $unpaidtabs[$idclassement]['periodField'] : null;
        $tags = isset($unpaidtabs[$idclassement]) ? $unpaidtabs[$idclassement]['tags'] : [];

        $form = $this->createFormBuilder()
            ->add('period', new DatePeriodType(), [
                'required' => false,
                'data' => ['begin' => $begin, 'end' => $end],
            ])
            ->add('period_field', 'choice', [
                'required' => true,
                'choices' => [
                    'rdv' => 'Date consultation',
                    'last_enc' => 'Dernier encaissement',
                    'next_enc' => 'Prochain encaissement'
                ],
                'data' => $periodField
            ])
            ->add('tags', 'choice', [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $em->getRepository('KGCRdvBundle:Etiquette')->getEtiquettesChoicesSwitchProfils($this->getUser()->getProfils()),
                'data' => $tags,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isValid()) {
            $begin = $form['period']['begin']->getData();
            $end = $form['period']['end']->getData();
            $periodField = $form['period_field']->getData();
            $tags = $form['tags']->getData();
            $unpaidtabs[$idclassement] = ['begin' => $begin, 'end' => $end, 'periodField' => $periodField, 'tags' => $tags];
        } elseif ($form->isSubmitted()) {
            if ($form['period']['begin']->getData() === null) {
                unset($unpaidtabs[$idclassement]);
            }
        }

        $request->getSession()->set('unpaidtab', $unpaidtabs);

        $endClone = null;
        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
        }
        $dateinterval = ['begin' => $begin, 'end' => $endClone];

        switch ($periodField) {
            case 'next_enc':
                $orderColumn = 3;
                break;
            case 'last_enc':
                $orderColumn = 2;
                break;
            default:
                $orderColumn = 1;
                break;
        }

        $count = $rep_rdv->getUnpaidByClassementCount($idclassement, $dateinterval, $periodField, $tags);
        $paginator = new Paginator($count, $page, 100);
        $interval = $paginator->getLimits();

        $list = $rep_rdv->getUnpaidByClassement($idclassement, $interval, $dateinterval, $periodField, $tags);
        if($export) {
            $csv = $this->getUnpaidCalculator()->getCsvRdvs($rep_rdv->getUnpaidByClassement($idclassement, null, $dateinterval, $periodField, $tags));
            return new Response($csv, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_impaye_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCRdvBundle:Encaissement:listeFicheTab.html.twig', array(
            'consults' => $list,
            'idclassement' => $idclassement,
            'paginator' => $paginator,
            'orderColumn' => $orderColumn,
            'RdvStates' => $em->getRepository('KGCRdvBundle:RdvState')->findAll(),
            'form' => $form->createView()
        ));
    }

    /**
     * Méthode WidgetUnbalanced
     * Liste les rdv dont le montant n'est pas entièrement encaissé + planifié.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function WidgetUnbalancedAction()
    {
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $list = $rep_rdv->getUnbalanced();

        return $this->render('KGCRdvBundle:Encaissement:unbalanced.widget.html.twig', array(
            'consults' => $list,
        ));
    }
}
