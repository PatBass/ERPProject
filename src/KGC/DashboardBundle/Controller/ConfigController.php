<?php
// src/KGC/DashboardBundle/Controller/ConfigController.php

namespace KGC\DashboardBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationExceptions;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\Bundle\SharedBundle\Entity\Source;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ClientBundle\Entity\CampagneSms;
use KGC\ClientBundle\Entity\Contact;
use KGC\ClientBundle\Entity\ListContact;
use KGC\ClientBundle\Entity\Mail;
use KGC\ClientBundle\Entity\Option;
use KGC\ClientBundle\Entity\Sms;
use KGC\ClientBundle\Form\CampagneType;
use KGC\ClientBundle\Form\ContactType;
use KGC\ClientBundle\Form\ListContactType;
use KGC\ClientBundle\Form\MailType;
use KGC\ClientBundle\Form\PlanType;
use KGC\ClientBundle\Form\ProductType;
use KGC\ClientBundle\Form\SmsType;
use KGC\ClientBundle\Form\SourceType;
use KGC\ClientBundle\Form\WebsiteType;
use KGC\CommonBundle\Controller\CommonController;
use KGC\DashboardBundle\Form\ForfaitTarificationType;
use KGC\DashboardBundle\Form\MoyenPaiementType;
use KGC\DashboardBundle\Form\RdvSourceType;
use KGC\DashboardBundle\Form\TpeType;
use KGC\RdvBundle\Entity\CodePromo;
use KGC\RdvBundle\Entity\CodeTarification;
use KGC\RdvBundle\Entity\Etiquette;
use KGC\RdvBundle\Entity\FormUrl;
use KGC\RdvBundle\Entity\ForfaitTarification;
use KGC\RdvBundle\Entity\MoyenPaiement;
use KGC\RdvBundle\Entity\Support;
use KGC\RdvBundle\Entity\TPE;
use KGC\RdvBundle\Form\CodePromoType;
use KGC\RdvBundle\Form\CodeTarificationType;
use KGC\RdvBundle\Form\EtiquetteType;
use KGC\RdvBundle\Form\FormUrlType;
use KGC\RdvBundle\Form\SupportType;
use KGC\StatBundle\Calculator\Calculator;
use KGC\StatBundle\Entity\BonusParameter;
use KGC\StatBundle\Entity\StatisticRenderingRule;
use KGC\StatBundle\Form\PastDateType;
use KGC\StatBundle\Form\BonusParameterType;
use KGC\StatBundle\Form\PhonisteBonusChallengeType;
use KGC\StatBundle\Form\PhonisteBonusHebdoType;
use KGC\StatBundle\Form\PhonistPenaltyType;
use KGC\StatBundle\Form\PsychicPenaltyType;
use KGC\StatBundle\Form\StatisticRenderingRuleType;
use KGC\UserBundle\Entity\Poste;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Entity\SalaryParameter;
use KGC\UserBundle\Entity\Voyant;
use KGC\UserBundle\Form\PosteType;
use KGC\UserBundle\Form\SalaryParameterType;
use KGC\UserBundle\Form\VoyantTarificationType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigController.
 */
class ConfigController extends CommonController
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

    /**
     * @return true
     */
    protected function isAdminChatOnly()
    {
        $securityContext = $this->get('security.context');
        $session = $this->get('session');
        return $securityContext->isGranted('ROLE_ADMIN_CHAT') && ($session->get('dashboard') == 'chat') || !$securityContext->isGranted('ROLE_MANAGER_PHONE');
    }

    /**
     * @param $template
     * @param $request
     * @param $em
     * @param $form
     * @param $o
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function tpePaymentHandler($template, $request, $em, $form, $o, $upd = false)
    {
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLibelle().'--'.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:'.$template.'.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param $repo
     * @param $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function tpePaymentList($repo, $template)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository($repo)->findAll();

        return $this->render('KGCDashboardBundle:Config:'.$template.'.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function paymentListAction()
    {
        return $this->tpePaymentList('KGCRdvBundle:MoyenPaiement', 'payments');
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function paymentEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
                ? $em->getRepository('KGCRdvBundle:MoyenPaiement')->find($id)
                : new MoyenPaiement();
        $upd = null !== $id;
        $form = $this->createForm(new MoyenPaiementType($upd), $o);

        return $this->tpePaymentHandler('payments', $request, $em, $form, $o, $upd);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function tpeEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:TPE')->find($id)
            : new TPE();
        $upd = null !== $id;
        $form = $this->createForm(new TpeType(), $o);

        return $this->tpePaymentHandler('tpe', $request, $em, $form, $o, $upd);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function tpeListAction()
    {
        return $this->tpePaymentList('KGCRdvBundle:TPE', 'tpe');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function mailListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $mails = $em->getRepository('KGCClientBundle:Mail')->findByTchat($tchat);

        return $this->render('KGCDashboardBundle:Config:mails.list.html.twig', [
            'mails' => $mails,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function mailEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $mail = $id
            ? $em->getRepository('KGCClientBundle:Mail')->find($id)
            : new Mail();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $form = $this->createForm(new MailType($tchat), $mail);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($mail);
                $em->flush();
                $this->addFlash('light#cog-light', 'Mail '.$mail->getCode().'--Template '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', 'Mail '.$mail->getCode().'--Template non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:mails.edit.html.twig', [
            'mail' => $mail,
            'form' => $form->createView(),
            'upd' => $upd,
            'tchat' => $tchat
        ]);
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function mailStateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $o = $em->getRepository('KGCClientBundle:Mail')->find($id);
            $o->setEnabled(!$o->getEnabled());
            $statusMsg = $o->getEnabled() ? 'activé' : 'désactivé';
            $this->addFlash('light#cog-light', 'Mail '.$o->getCode().'--Template '.$statusMsg.'.');

            $em->flush();
        } else {
            $this->addFlash('error#cog', 'Erreur--Identifiant de mail manquant.');
        }

        return $this->jsonResponse([true]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function posteListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $postes = $em->getRepository('KGCUserBundle:Poste')->findAll();

        return $this->render('KGCDashboardBundle:Config:postes.list.html.twig', [
            'postes' => $postes,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function posteEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $poste = $id
            ? $em->getRepository('KGCUserBundle:Poste')->find($id)
            : new Poste();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new PosteType(), $poste);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($poste);
                $em->flush();
                $this->addFlash('light#cog-light', 'Poste '.$poste->getName().'-- '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', 'Poste '.$poste->getName().'-- '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:postes.edit.html.twig', [
            'poste' => $poste,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function smsListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        }else {
            $tchat = 0;
        }
        $sms = $em->getRepository('KGCClientBundle:Sms')->findByTchat($tchat);

        return $this->render('KGCDashboardBundle:Config:sms.list.html.twig', [
            'sms' => $sms,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function smsEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $sms = $id
            ? $em->getRepository('KGCClientBundle:Sms')->find($id)
            : new Sms();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $form = $this->createForm(new SmsType($tchat), $sms);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($sms);
                $em->flush();
                $this->addFlash('light#cog-light', 'Sms '.$sms->getCode().'--Template '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', 'Sms '.$sms->getCode().'--Template non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:sms.edit.html.twig', [
            'sms' => $sms,
            'form' => $form->createView(),
            'upd' => $upd,
            'tchat' => $tchat
        ]);
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        }else {
            $tchat = 0;
        }
        $campagne = $em->getRepository('KGCClientBundle:CampagneSms')->findByTchat($tchat);

        return $this->render('KGCDashboardBundle:Config:campagne.list.html.twig', [
            'campagne' => $campagne,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $campagne = $id
            ? $em->getRepository('KGCClientBundle:CampagneSms')->find($id)
            : new CampagneSms();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $form = $this->createForm(new CampagneType($tchat), $campagne);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($campagne);
                $em->flush();
                $this->addFlash('light#cog-light', 'Campagne '.$campagne->getName().'--Modèle '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', 'Campagne '.$campagne->getName().'--Modèle non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:campagne.edit.html.twig', [
            'campagne' => $campagne,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneSendAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $campagne =  $em->getRepository('KGCClientBundle:CampagneSms')->find($id);

        $smsService = $this->get('kgc.client.sms.service');
        $array = $smsService->sendCampagne($campagne);
        return $this->render('KGCDashboardBundle:Config:campagne.sent.html.twig', [
            'list' => $array,
        ]);
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneListContactListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        }else {
            $tchat = 0;
        }
        $list = $em->getRepository('KGCClientBundle:ListContact')->findByTchat($tchat);

        return $this->render('KGCDashboardBundle:Config:campagne.list-contact.list.html.twig', [
            'list' => $list,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneListContactEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $list = $id
            ? $em->getRepository('KGCClientBundle:ListContact')->find($id)
            : new ListContact();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $form = $this->createForm(new ListContactType($tchat), $list);

        $formhandler = $this->get('kgc.listcontact.formhandler');
        $result = $formhandler->process($form, $request);

        if ($result === true) {
            $this->addFlash('light#cog-light', 'Liste '.$list->getName().'--'.$statusMsg.'.');
            if(count($request->files)) {
                if ('chat' === $this->get('session')->get('dashboard')) {
                    $tchat = 1;
                }else {
                    $tchat = 0;
                }
                $list = $em->getRepository('KGCClientBundle:ListContact')->findByTchat($tchat);

                return $this->render('KGCDashboardBundle:Config:campagne.list-contact.list.html.twig', [
                    'list' => $list,
                ]);
            }
        } elseif ($result === false) { // form submit invalid
            $this->addFlash('error#cog', 'Liste '.$list->getName().'--non '.$statusMsg.'.');
        }

        return $this->render('KGCDashboardBundle:Config:campagne.list-contact.edit.html.twig', [
            'list' => $list,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function campagneListContactSupprimerAction(Request $request, $id)
    {
        $flag = false;
        $close = false;
        $em = $this->getDoctrine()->getEntityManager();
        $list = $em->getRepository('KGCClientBundle:ListContact')->find($id);
        $form = $this->createFormBuilder($list)->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $flag = true;
            $em->remove($list);
            $em->flush();
            $this->addFlash(
                'light#user-light',
                $list->getName().'--Liste supprimé.'
            );
            $close = true;
        }

        return $this->render('KGCDashboardBundle:Config:campagne.list-contact.supprimer.html.twig', array(
            'list' => $list,
            'form' => $form->createView(),
            'flag' => $flag,
            'close' => $close,
        ));
    }


    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneListContactAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $list = $em->getRepository('KGCClientBundle:ListContact')->find($id);
        $contacts = $em->getRepository('KGCClientBundle:Contact')->findByList($id);

        return $this->render('KGCDashboardBundle:Config:campagne.contact.list.html.twig', [
            'contact' => $contacts,
            'list' => $list
        ]);
    }

    /**
     * @param Request $request
     * @param $list
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function campagneContactEditAction(Request $request, $list, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $list = $em->getRepository('KGCClientBundle:ListContact')->find($list);
        $contact = $id
            ? $em->getRepository('KGCClientBundle:Contact')->find($id)
            : (new Contact())->setList($list);

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        } else {
            $tchat = 0;
        }
        $form = $this->createForm(new ContactType($list, $tchat), $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($contact);
                $em->flush();
                $this->addFlash('light#user-light', $contact->fullname().'--Contact '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $contact->fullname().'--Contact non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:campagne.contact.edit.html.twig', [
            'list' => $list,
            'contact' => $contact,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function campagneContactSupprimerAction(Request $request, $id)
    {
        $flag = false;
        $close = false;
        $em = $this->getDoctrine()->getEntityManager();
        $contact = $em->getRepository('KGCClientBundle:Contact')->find($id);
        $form = $this->createFormBuilder($contact)->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $flag = true;
            $em->remove($contact);
            $em->flush();
            $this->addFlash(
                'light#user-light',
                $contact->fullname().'--Contact supprimé.'
            );
            $close = true;
        }

        return $this->render('KGCDashboardBundle:Config:campagne.contact.supprimer.html.twig', array(
            'contact' => $contact,
            'form' => $form->createView(),
            'flag' => $flag,
            'close' => $close,
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function tarificationListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $isChat = $this->isAdminChatOnly();
        $objects = $em->getRepository('KGCUserBundle:Voyant')->findAllWithTarification($isChat);

        return $this->render('KGCDashboardBundle:Config:tarifications.list.html.twig', [
            'isChat' => $isChat,
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function tarificationEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCUserBundle:Voyant')->find($id)
            : new Voyant();
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new VoyantTarificationType($em, $this->isAdminChatOnly()), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getNom().'--Voyant '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getNom().'--Voyant non '.$statusMsg.'.');

                if ($errors = $form->get('utilisateur')->getErrors()) {
                    foreach ($errors as $error) {
                        $this->addFlash('error#cog', $o->getNom().'--'.$error->getMessage().'.');
                    }
                }
            }
        }

        return $this->render('KGCDashboardBundle:Config:tarifications.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function forfaitTarificationListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCClientBundle:Option')->findForfaitTarification();

        return $this->render('KGCDashboardBundle:Config:forfait.tarifications.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function forfaitTarificationEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:ForfaitTarification')->find($id)
            : new ForfaitTarification();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new ForfaitTarificationType($upd), $o);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $em->persist($o);
                    $em->flush();
                    $this->addFlash('light#cog-light', 'Tarification du forfait --'.$statusMsg.'.');
                } else if ($form->get('utilisateur')->getErrors()->count() > 0) {
                    foreach ($form->get('utilisateur')->getErrors() as $error) {
                        $this->addFlash('error#cog', $error->getMessage());
                    }
                } else {
                    $this->addFlash('error#cog', 'Tarification du forfait--Non '.$statusMsg.'.');
                }
            }
        } catch (UniqueConstraintViolationException $e) {
            $this->addFlash('error#cog', 'Tarification du forfait--Existe déjà !');
            $form->addError(new FormError('Cette tarification existe déjà.'));
        }

        return $this->render('KGCDashboardBundle:Config:forfait.tarifications.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function supportListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCRdvBundle:Support')->findAll();

        return $this->render('KGCDashboardBundle:Config:supports.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function supportEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:Support')->find($id)
            : new Support();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new SupportType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLibelle().'--Support '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Support non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:supports.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function supportStateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $o = $em->getRepository('KGCRdvBundle:Support')->find($id);
            $o->setEnabled(!$o->getEnabled());
            $statusMsg = $o->getEnabled() ? 'activé' : 'désactivé';
            $this->addFlash('light#cog-light', $o->getLibelle().'--Support '.$statusMsg.'.');

            $em->flush();
        } else {
            $this->addFlash('error#cog', 'Erreur--Identifiant de support manquant.');
        }

        return $this->jsonResponse([true]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statisticRenderingRuleListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCStatBundle:StatisticRenderingRule')->findAll();

        return $this->render('KGCDashboardBundle:Config:statisticRenderingRules.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statisticRenderingRuleDeleteAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $object = $em->getRepository('KGCStatBundle:StatisticRenderingRule')->find($id);
        if($object != null) {
            $em->remove($object);
            $em->flush();
        }
        return $this->redirectToRoute('kgc_config_statistic');
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statisticRenderingRuleEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCStatBundle:StatisticRenderingRule')->find($id)
            : new StatisticRenderingRule();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new StatisticRenderingRuleType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', '--Statistique '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Statistique non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:statisticRenderingRules.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function codepromoListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCRdvBundle:CodePromo')->findAll();

        return $this->render('KGCDashboardBundle:Config:codepromos.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function codepromoEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:CodePromo')->find($id)
            : new CodePromo();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new CodePromoType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getCode().'--Code promo '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getCode().'--Code promo non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:codepromos.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function codepromoStateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $o = $em->getRepository('KGCRdvBundle:CodePromo')->find($id);
            $o->setEnabled(!$o->getEnabled());
            $em->flush();
            $statusMsg = $o->getEnabled() ? 'activé' : 'désactivé';
            $this->addFlash('light#cog-light', $o->getCode().'--Code promo '.$statusMsg.'.');
        } else {
            $this->addFlash('error#cog', 'Erreur--Identifiant de code promo manquant.');
        }

        $result = [
            'redirect_uri' => $this->get('router')->generate('kgc_config_codepromo', [], true),
        ];

        return $this->jsonResponse($result);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function websiteListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $objects = [];

        $securityContext = $this->get('security.context');
        $session = $this->get('session');

        //get Phone website
        if ($session->get('dashboard') != 'chat' && ($securityContext->isGranted('ROLE_MANAGER_PHONE'))) {
            $objects['phone'] = $em->getRepository('KGCSharedBundle:Website')->findIsChat(false);
        }

        //get Chat website
        if ($isChat = $this->isAdminChatOnly()) {
            $objects['chat'] = $em->getRepository('KGCSharedBundle:Website')->findIsChat(true);
        }

        $paymentGateways = $em->getRepository('KGCRdvBundle:TPE')->getAvailablePaymentGatewaysForTchat();

        return $this->render('KGCDashboardBundle:Config:websites.list.html.twig', [
            'isChat' => $isChat,
            'objects' => $objects,
            'paymentGateways' => $paymentGateways,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_ADMIN_CHAT")
     */
    public function websiteEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCSharedBundle:Website')->find($id)
            : new Website();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $paymentGateways = $o->getReference() ?
            $em->getRepository('KGCRdvBundle:TPE')->getAvailablePaymentGatewaysForTchat()
            : [];

        $form = $this->createForm(new WebsiteType($paymentGateways), $o);
        $form->handleRequest($request);
        $redirect = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $redirect = null !== $o->getFile();
                $o->upload();
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLibelle().'--Site '.$statusMsg.'.');

                if ($redirect) {
                    return $this->redirect($this->get('router')->generate('kgc_config_website'));
                }
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Site non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:websites.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function forfaitListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCClientBundle:Option')->findAllByType(Option::TYPE_PLAN);

        return $this->render('KGCDashboardBundle:Config:forfaits.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function forfaitEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCClientBundle:Option')->find($id)
            : new Option(Option::TYPE_PLAN, '');
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new PlanType($upd), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLabel().'--Forfait '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getLabel().'--Forfait non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:forfaits.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function tarificationSimpleListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCRdvBundle:CodeTarification')->findAll();

        return $this->render('KGCDashboardBundle:Config:tarifications.simple.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function tarificationSimpleEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:CodeTarification')->find($id)
            : new CodeTarification()
        ;
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new CodeTarificationType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLibelle().'--Tarification '.$statusMsg.'e.');
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Tarification non '.$statusMsg.'e.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:tarifications.simple.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function salaryListAction(Request $request)
    {
        $nature = $request->query->get('nature');
        $type = $request->query->get('type');

        if (empty($nature) || empty($type)) {
            throw new \InvalidArgumentException(
                sprintf('Request params "nature" and "type" must be defined !')
            );
        }

        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCUserBundle:SalaryParameter')->findBy([
            'nature' => $nature,
            'type' => $type,
        ]);

        return $this->render('KGCDashboardBundle:Config:salary.list.html.twig', [
            'objects' => $objects,
            'nature' => $nature,
            'type' => $type,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function salaryEditAction(Request $request, $id)
    {

        $nature = $request->query->get('nature');
        $type = $request->query->get('type');

        if (empty($nature) || empty($type)) {
            throw new \InvalidArgumentException(
                sprintf('Request params "nature" and "type" must be defined !')
            );
        }

        $em = $this->getDoctrine()->getEntityManager();
        if($id) {
            $o = $em->getRepository('KGCUserBundle:SalaryParameter')->find($id);
        }
        else {
            $o = new SalaryParameter();
            $o->setNature($nature);
            $o->setType($type);
        }
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new SalaryParameterType($upd), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', '--Salaire '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', '--Salaire non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:salary.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
            'nature' => $nature,
            'type' => $type,
        ]);
    }
    
    /**
     * @return \KGC\DashboardBundle\Controller\Response
     * 
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonisteBonusHebdoAction()
    {
        $request = $this->get('request');
        $date = $request->getSession()->get('phoniste_hebdo_bonus_date') ?: new \DateTime();
        
        $form = $this->createFormBuilder()
            ->add('date', new PastDateType(), [ 'data'=>$date ])
            ->getForm()
        ;

        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $request->getSession()->set('phoniste_hebdo_bonus_date', $date);
        }
        
        list($begin, $end) = Calculator::getSQLWeekInterval($date);
        
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('KGCStatBundle:BonusParameter');
        $liste = $repo->getPhonisteHebdoBonus($begin, $end);

        return $this->render('KGCDashboardBundle:Config:bonus.phoniste_hebdo.html.twig', array(
            'list' => $liste,
            'nresults' => count($liste),
            'form' => $form->createView(),
        ));
    }
    
    /**
     * @return \KGC\DashboardBundle\Controller\Response
     * 
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonisteBonusChallengeAction()
    {
        $request = $this->get('request');
        $date = $request->getSession()->get('phoniste_challenge_bonus_date') ?: new \DateTime();
        
        $form = $this->createFormBuilder()
            ->add('date', new PastDateType(), [ 'data'=>$date ])
            ->getForm()
        ;

        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $request->getSession()->set('phoniste_challenge_bonus_date', $date);
        }
        
        list($begin, $end) = Calculator::getFullMonthIntervalFromDate($date);
        
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('KGCStatBundle:BonusParameter');
        $liste = $repo->getPhonisteChallengeBonus($begin, $end);

        return $this->render('KGCDashboardBundle:Config:bonus.phoniste_challenge.html.twig', array(
            'list' => $liste,
            'nresults' => count($liste),
            'form' => $form->createView(),
        ));
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonisteBonusHebdoEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('KGCStatBundle:BonusParameter');
        $o = $id ? $repo->find($id) : new BonusParameter();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new PhonisteBonusHebdoType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Recherche si ce paramètre existe déjà pour cette semaine et cet utilisateur
                list($begin, $end) = Calculator::getSQLWeekInterval($o->getDate());
                $o->setDate($begin);
                $bonus = $repo->getPhonisteHebdoBonus($begin, $end, $o->getUser());
                if(isset($bonus)){
                    $bonus->setObjective($o->getObjective());
                    $bonus->setAmount($o->getAmount());
                    $em->persist($bonus);
                    $statusMsg = 'modifiée';
                } else {
                    $em->persist($o);
                }
                $em->flush();
                $this->addFlash('light#cog-light', 'Prime Hebdo phoniste '.$statusMsg.'.--');
            } else {
                $this->addFlash('error#cog', 'Prime Hebdo phoniste--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:bonus.phoniste_hebdo.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd
        ]);
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonisteBonusChallengeEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('KGCStatBundle:BonusParameter');
        $o = $id ? $repo->find($id) : new BonusParameter();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new PhonisteBonusChallengeType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Recherche si ce paramètre existe déjà pour ce mois et cet utilisateur
                list($begin, $end) = Calculator::getFullMonthIntervalFromDate($o->getDate());
                $bonus = $repo->getPhonisteChallengeBonus($begin, $end, $o->getUser());
                if(isset($bonus)){
                    $bonus->setDate($o->getDate());
                    $bonus->setAmount($o->getAmount());
                    $em->persist($bonus);
                    $statusMsg = 'modifiée';
                } else {
                    $em->persist($o);
                }
                $em->flush();
                $this->addFlash('light#cog-light', 'Prime Challenge phoniste '.$statusMsg.'.--');
            } else {
                $this->addFlash('error#cog', 'Prime Challenge phoniste--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:bonus.phoniste_challenge.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd
        ]);
    }
            
    /**
     * @return \KGC\DashboardBundle\Controller\Response
     * 
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonistePenaltyAction()
    {
        $request = $this->get('request');
        $date = $request->getSession()->get('phonist_penalty_date') ?: new \DateTime();
        
        $form = $this->createFormBuilder()
            ->add('date', new PastDateType(), [ 'data'=>$date ])
            ->getForm()
        ;

        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $request->getSession()->set('phonist_penalty_date', $date);
        }
        
        list($begin, $end) = Calculator::getFullMonthIntervalFromDate($date);
        
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('KGCStatBundle:BonusParameter');
        $liste = $repo->getPhonistPenalty($begin, $end);

        return $this->render('KGCDashboardBundle:Config:bonus.phonist_penalty.html.twig', array(
            'list' => $liste,
            'nresults' => count($liste),
            'form' => $form->createView(),
        ));
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function phonistePenaltyEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('KGCStatBundle:BonusParameter');
        $o = $id ? $repo->find($id) : new BonusParameter();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new PhonistPenaltyType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Recherche si ce paramètre existe déjà pour ce mois et cet utilisateur
                list($begin, $end) = Calculator::getFullMonthIntervalFromDate($o->getDate());
                $bonus = $repo->getPhonistPenalty($begin, $end, $o->getUser());
                if(isset($bonus)){
                    $bonus->setDate($o->getDate());
                    $bonus->setAmount($o->getAmount());
                    $em->persist($bonus);
                    $statusMsg = 'modifiée';
                } else {
                    $em->persist($o);
                }
                $em->flush();
                $this->addFlash('light#cog-light', 'Pénalité Phoniste '.$statusMsg.'.--');
            } else {
                $this->addFlash('error#cog', 'Pénalité Phoniste--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:bonus.phonist_penalty.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd
        ]);
    }
    
    /**
     * @return \KGC\DashboardBundle\Controller\Response
     * 
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function psychicPenaltyAction()
    {
        $request = $this->get('request');
        $date = $request->getSession()->get('psychic_penalty_date') ?: new \DateTime();
        
        $form = $this->createFormBuilder()
            ->add('date', new PastDateType(), [ 'data'=>$date ])
            ->getForm()
        ;

        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $request->getSession()->set('psychic_penalty_date', $date);
        }
        
        list($begin, $end) = Calculator::getFullMonthIntervalFromDate($date);
        
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('KGCStatBundle:BonusParameter');
        $liste = $repo->getPsychicPenalty($begin, $end);

        return $this->render('KGCDashboardBundle:Config:bonus.psychic_penalty.html.twig', array(
            'list' => $liste,
            'nresults' => count($liste),
            'form' => $form->createView(),
        ));
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function psychicPenaltyEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('KGCStatBundle:BonusParameter');
        $o = $id ? $repo->find($id) : new BonusParameter();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new PsychicPenaltyType(), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Recherche si ce paramètre existe déjà pour ce mois et cet utilisateur
                list($begin, $end) = Calculator::getFullMonthIntervalFromDate($o->getDate());
                $bonus = $repo->getPsychicPenalty($begin, $end, $o->getUser());
                if(isset($bonus)){
                    $bonus->setDate($o->getDate());
                    $bonus->setAmount($o->getAmount());
                    $em->persist($bonus);
                    $statusMsg = 'modifiée';
                } else {
                    $em->persist($o);
                }
                $em->flush();
                $this->addFlash('light#cog-light', 'Pénalité Voyant '.$statusMsg.'.--');
            } else {
                $this->addFlash('error#cog', 'Pénalité Voyant--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:bonus.psychic_penalty.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd
        ]);
    }
    
    /**
     * @return \KGC\DashboardBundle\Controller\Response
     * 
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function psychicBonusHebdoAction()
    {
        $request = $this->get('request');
        $date = $request->getSession()->get('psychic_hebdo_bonus_date') ?: new \DateTime();
        
        $form = $this->createFormBuilder()
            ->add('date', new PastDateType(), [ 'data'=>$date ])
            ->getForm()
        ;

        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $request->getSession()->set('psychic_hebdo_bonus_date', $date);
        }
        
        list($begin, $end) = Calculator::getSQLWeekInterval($date);
        
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('KGCStatBundle:BonusParameter');
        $liste = $repo->getPsychicHebdoBonus($begin, $end);

        return $this->render('KGCDashboardBundle:Config:bonus.psychic_hebdo.html.twig', array(
            'list' => $liste,
            'nresults' => count($liste),
            'form' => $form->createView(),
        ));
    }
    
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function psychicBonusHebdoEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('KGCStatBundle:BonusParameter');
        $o = $id ? $repo->find($id) : new BonusParameter();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new BonusParameterType(), $o, [
            'code' => BonusParameter::PSYCHIC_HEBDO,
            'userProfil' => Profil::VOYANT
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Recherche si ce paramètre existe déjà pour cette semaine et cet utilisateur
                list($begin, $end) = Calculator::getSQLWeekInterval($o->getDate());
                $o->setDate($begin);
                $bonus = $repo->getPsychicHebdoBonus($begin, $end, $o->getUser());
                if(isset($bonus)){
                    $bonus->setObjective($o->getObjective());
                    $bonus->setAmount($o->getAmount());
                    $em->persist($bonus);
                    $statusMsg = 'modifiée';
                } else {
                    $em->persist($o);
                }
                $em->flush();
                $this->addFlash('light#cog-light', 'Prime Hebdo voyant '.$statusMsg.'.--');
            } else {
                $this->addFlash('error#cog', 'Prime Hebdo voyant--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:bonus.psychic_hebdo.edit.html.twig', [
            'o' => $o,
            'form' => $form->createView(),
            'upd' => $upd
        ]);
    }
    
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function productsListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCClientBundle:Option')->findAllByType(Option::TYPE_PRODUCT);

        return $this->render('KGCDashboardBundle:Config:products.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function productsEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCClientBundle:Option')->find($id)
            : new Option(Option::TYPE_PRODUCT, '');
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new ProductType($upd), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getCode().'--Produit '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getCode().'--Produit non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:products.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function sourceListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCRdvBundle:Source')->findAll();

        return $this->render('KGCDashboardBundle:Config:sources.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function sourceEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:Source')->find($id)
            : new \KGC\RdvBundle\Entity\Source();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new RdvSourceType(), $o);
        $form->handleRequest($request);
        $redirect = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLabel().'--Source '.$statusMsg.'.');

                if ($redirect) {
                    return $this->redirect($this->get('router')->generate('kgc_config_tracking'));
                }
            } else {
                $this->addFlash('error#cog', $o->getLabel().'--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:sources.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }
    
        
    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function sourceStateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $o = $em->getRepository('KGCRdvBundle:Source')->find($id);
            $o->setEnabled(!$o->getEnabled());
            $statusMsg = $o->getEnabled() ? 'activé' : 'désactivé';
            $this->addFlash('light#cog-light', $o->getLabel().'--Source '.$statusMsg.'.');

            $em->flush();
        } else {
            $this->addFlash('error#cog', 'Erreur--Identifiant de source manquant.');
        }

        return $this->jsonResponse([true]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function formurlListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCRdvBundle:FormUrl')->findAll();

        return $this->render('KGCDashboardBundle:Config:formurl.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function formurlEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:FormUrl')->find($id)
            : new FormUrl();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifiée' : 'ajoutée';

        $form = $this->createForm(new FormUrlType(), $o);
        $form->handleRequest($request);
        $redirect = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLabel().'--Url de formulaire '.$statusMsg.'.');

                if ($redirect) {
                    return $this->redirect($this->get('router')->generate('kgc_config_tracking'));
                }
            } else {
                $this->addFlash('error#cog', $o->getLabel().'--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:formurl.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }
    
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function sourceVEDListAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $objects = $em->getRepository('KGCSharedBundle:Source')->findAll();

        return $this->render('KGCDashboardBundle:Config:sources_ved.list.html.twig', [
            'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_ADMIN_CHAT, ROLE_MANAGER_PHONE")
     */
    public function sourceVEDEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCSharedBundle:Source')->find($id)
            : new Source();

        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new SourceType(), $o);
        $form->handleRequest($request);
        $redirect = false;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLabel().'--Source '.$statusMsg.'.');

                if ($redirect) {
                    return $this->redirect($this->get('router')->generate('kgc_config_tracking'));
                }
            } else {
                $this->addFlash('error#cog', $o->getLabel().'--'.(string)$form->getErrors(true, false));
            }
        }

        return $this->render('KGCDashboardBundle:Config:sources_ved.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function etiquettesListAction()
    {
        $em = $this->getDoctrine()->getManager();
        $objects = $em->getRepository('KGCRdvBundle:Etiquette')->findAll();
        return $this->render('KGCDashboardBundle:Config:etiquettes.list.html.twig', [
        'objects' => $objects,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function etiquettesEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $o = $id
            ? $em->getRepository('KGCRdvBundle:Etiquette')->find($id)
            : new Etiquette();
        $upd = null !== $id;
        $statusMsg = $upd ? 'modifié' : 'ajouté';

        $form = $this->createForm(new EtiquetteType($upd), $o);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $o->setIdcode($this->slugify($o->getLibelle()));
                $em->persist($o);
                $em->flush();
                $this->addFlash('light#cog-light', $o->getLibelle().'--Étiquette '.$statusMsg.'.');
            } else {
                $this->addFlash('error#cog', $o->getLibelle().'--Étiquette non '.$statusMsg.'.');
            }
        }

        return $this->render('KGCDashboardBundle:Config:etiquettes.edit.html.twig', [
            'object' => $o,
            'form' => $form->createView(),
            'upd' => $upd,
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function etiquettesReactivateAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('KGCRdvBundle:Etiquette')->find($id);
        $entity->setActive(true);
        $em->flush();
        return $this->redirect($this->generateUrl('kgc_config_etiquette'));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function etiquettesDeleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('KGCRdvBundle:Etiquette')->find($id);
        $entity->setActive(false);
        $em->flush();
        return $this->redirect($this->generateUrl('kgc_config_etiquette'));
    }

    private function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '_', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '_', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}
