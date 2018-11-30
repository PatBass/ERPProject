<?php

// src/KGC/RdvBundle/Controller/QualiteController.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\CommonBundle\Controller\CommonController;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * QualiteController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class QualiteController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCRdvBundle:RDV';
    }

    protected function listSuiviJour($idconsultant, $tpl = null)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $request = $this->get('request');
        $begin = $request->getSession()->get('today_reminder_period_begin');
        $end = $request->getSession()->get('today_reminder_period_end');
        $export = $request->query->has('export');

        $form = $this->createFormBuilder()
            ->add('period', new DatePeriodType())
            ->getForm()
        ;
        $form['period']['begin']->setData($begin);
        $form['period']['end']->setData($end);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $begin = $form['period']['begin']->getData();
            $request->getSession()->set('today_reminder_period_begin', $begin);
            $end = $form['period']['end']->getData();
            $request->getSession()->set('today_reminder_period_end', $end);
        } elseif ($form->isSubmitted()) {
            if ($form['period']['begin']->getData() === null) {
                $request->getSession()->remove('today_reminder_period_begin');
                $request->getSession()->remove('today_reminder_period_end');
            }
        }

        $endClone = null;
        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
        }

        $liste = $this->getRepository()->getTodayReminders($consultant, $begin, $endClone);

        $oliste = $this->get('kgc.rdv.planning.service')->sortByDayHour($liste, 'getReminderDate');

        $twig = null === $tpl ? 'suivis.liste.html.twig' : 'suivis.'.$tpl.'.liste.html.twig';

        $qualityOptions = [];
        if (null === $tpl) {
            $qualityOptions = [
                'quality_info_options' => [
                    Historique::TYPE_RECAP => false,
                    Historique::TYPE_NOTES => false,
                    Historique::TYPE_OPINION => false,
                    Historique::TYPE_REMINDER_STATE => true,
                ],
            ];
        }

        if($export) {
            $data = $this->get('kgc.stat.decorator.csv')->decorate($oliste, ['qualite_consultation' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_qualite_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCRdvBundle:Qualite:'.$twig, array(
            'fiches_list' => $oliste,
            'nresults' => count($liste),
            'form' => $form->createView(),
            'begin' => $begin,
            'end' => $end,
        ) + $qualityOptions);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function ListeSuivisJourPsychicAction()
    {
        $currentUser = $this->getUser();

        return $this->listSuiviJour($currentUser->getId(), 'voyant');
    }

    /**
     * Widget liste des consultations de la veille.
     *
     *
     * @param int $idconsultant
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE")
     */
    public function ListeConsultationsVeilleAction($idconsultant = 0)
    {
        $request = $this->get('request');
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);
        $filter = $request->getSession()->get('yesterday_done_filter');
        $begin = $request->getSession()->get('yesterday_done_period_begin');
        $end = $request->getSession()->get('yesterday_done_period_end');
        $export = $request->query->has('export');

        $form = $this->createFormBuilder()
            ->add('filtre', 'choice', array(
                'empty_value' => 'Aucun',
                'required' => false,
                'choices' => array(
                    'nrp' => 'NRP',
                    'lt10min' => 'Moins de 10min.',
                    '10-30min' => 'De 10 à 30 minutes',
                    'gt30min' => 'Plus de 30min.',
                ),
                'attr' => array(
                    'class' => 'submit-onchange',
                ),
                'expanded' => true,
                'data' => $filter,
            ))
            ->add('period', new DatePeriodType())
            ->getForm()
        ;
        $form['period']['begin']->setData($begin);
        $form['period']['end']->setData($end);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $filter = $form['filtre']->getData();
            $request->getSession()->set('yesterday_done_filter', $filter);
            $begin = $form['period']['begin']->getData();
            $request->getSession()->set('yesterday_done_period_begin', $begin);
            $end = $form['period']['end']->getData();
            $request->getSession()->set('yesterday_done_period_end', $end);
        } elseif ($form->isSubmitted()) {
            if ($form['period']['begin']->getData() === null) {
                $request->getSession()->remove('yesterday_done_period_begin');
                $request->getSession()->remove('yesterday_done_period_end');
            }
        }

        $endClone = null;
        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
        }

        $liste = $this->getRepository()->getYesterdayDone($consultant, $filter, $begin, $endClone);

        $oliste = $this->get('kgc.rdv.planning.service')->sortByDayHour($liste, 'getDateConsultation');

        if($export) {
            $data = $this->get('kgc.stat.decorator.csv')->decorate($oliste, ['qualite_consultation' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_qualite_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCRdvBundle:Qualite:veille.liste.html.twig', array(
            'quality_info_options' => [
                Historique::TYPE_RECAP => true,
                Historique::TYPE_NOTES => true,
                Historique::TYPE_OPINION => true,
                Historique::TYPE_REMINDER_STATE => false,
            ],
            'fiches_list' => $oliste,
            'nresults' => count($liste),
            'form' => $form->createView(),
            'begin' => $begin,
            'end' => $end,
        ));
    }

    /**
     * Widget liste des suivis du jour.
     *
     *
     * @param int $idconsultant
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE")
     */
    public function ListeSuivisJourAction($idconsultant = 0)
    {
        return $this->listSuiviJour($idconsultant);
    }

    /**
     * Méthode VoirficheAction
     * Consultation de la fiche pour le service Qualité.
     *
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function VoirficheAction(Request $request, $id)
    {
        $currUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->findById($id);

        $refererParams = $this->getRefererParams($request);
        $urlClose = isset($refererParams['_route']) ? $refererParams['_route'] : null;
        $urlClose = in_array($urlClose, ['kgc_qualite_page', 'kgc_search_page']) ? $urlClose : null;
        $redirectRoute = $request->query->get('redirect-route');

        if ($rdv) {
            $request = $this->get('request');
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();

            $historiqueManager = $this->get('kgc.client.historique.manager');
            $formType = new Form\RDVQualiteType($currUser, $em, $historiqueManager, $param_edit);
            $form = $this->createForm($formType, $rdv);

            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();
            if ($result) { // form soumis ok
                $this->addFlash('light#pencil-light', $client.'--Consultation mise à jour.');

                $state = $rdv->getReminderState();
                if ($state) {
                    $reminder_state = $state->getCode();
                    if ($reminder_state == Option::REMINDER_STATE_CONFIRMED) {
                        $this->addFlash('modal', $this->get('router')->generate('kgc_rdv_dupliquer', ['id' => $rdv->getId(), 'suivi' => 1]));
                    }
                }

                $close = $form['fermeture']->getData();
                if ($close) {
                    return $this->redirect($this->get('router')->generate($redirectRoute ?: 'kgc_qualite_page'));
                }
                $form = $this->createForm($formType, $rdv);
            } elseif ($result === false) { // form soumis erreur
                $this->addFlash('error#pencil', $client.'--Consultation non enregistrée.');
            }
        }

        return $this->render('KGCRdvBundle:Qualite:fiche.html.twig', array(
            'rdv' => $rdv,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : null,
            'url_close' => $urlClose,
        ));
    }
}
