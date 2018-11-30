<?php

// src/KGC/RdvBundle/Controller/SecurisationController.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\PaymentBundle\Exception\Payment\InvalidCardDataException;
use KGC\PaymentBundle\Exception\Payment\PaymentFailedException;
use KGC\PaymentBundle\Exception\Payment\PaymentRefusedException;
use KGC\RdvBundle\Entity\Etat;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * SecurisationController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class SecurisationController extends Controller
{
    /**
     * Méthode liste
     * Liste les rdv dont la sécurisation n'est pas faite (refusée != faite).
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE")
     */
    public function ListeAction()
    {
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $liste_rdv = $rep_rdv->getSecurisations();
        $display = array();
        $compact = array();
        foreach ($liste_rdv as $rdv) {
            if ($rdv->getDateConsultation()->format('dmY') == date('dmY')) {
                $display[] = $rdv;
            } else {
                $compact[] = $rdv;
            }
        }

        return $this->render('KGCRdvBundle:Securisation:liste.html.twig', array(
            'display' => $display,
            'compact' => $compact,
        ));
    }

    /**
     * Méthode Sécuriser
     * Enregistre les données de sécurisation.
     *
     *
     * @param int $id identifiant de la consultation
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function SecuriserAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $rdv = $rep_rdv->findOneById($id);
        $paymentFailed = false;

        if ($rdv && $rdv->getSecurisation() == RDV::SECU_PENDING && count($rdv->getCartebancaires())) {
            $request = $this->get('request');
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $currUser = $this->getUser();
            $client = $rdv->getClient()->getFullName();
            $form = $this->createForm(new Form\RDVSecurisationType($currUser, $em, $param_edit, [], true, $this->getUser()->getIsDecryptAvailable()), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');

            try {
                $result = $formhandler->process($form, $request);

                if ($result === true) { // form submit valid
                    $msg = $rdv->getEtat()->getIdCode() == Etat::CONFIRMED
                            ? 'Consultation confirmée.'
                            : 'Consultation annulée.';
                    $this->get('session')->getFlashBag()->add('light#shield-light', $client.'--Sécurisation enregistrée. '.$msg);
                } elseif ($result === false) { // form submit invalid
                    $this->get('session')->getFlashBag()->add('error#shield', $client.'--Sécurisation non enregistrée. ');
                }
            } catch (PaymentFailedException $e) {
                $paymentFailed = true;
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
                $this->get('session')->getFlashBag()->add('error#shield', $client.'--'.$message);
            } catch (\Exception $e) {
                $paymentFailed = true;
                $this->get('session')->getFlashBag()->add('error#shield', $client.'--Erreur lors de la sécurisation ('.$e->getMessage().')');
            }
        }

        return $this->render('KGCRdvBundle:Securisation:securiser.html.twig', array(
            'form' => isset($form) ? $form->createView() : false,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : false,
            'rdv' => $rdv,
            'paymentFailed' => $paymentFailed,
            'errorMessage' => isset($errorMessage) ? $errorMessage : null
        ));
    }

    /**
     * Méthode cancel pre-auth : annule la pré-autorisation.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function CancelPreAuthorizationAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $em->getRepository('KGCRdvBundle:RDV')->findOneById($id);
        $client = $rdv->getClient()->getFullName();

        $request = $this->get('request');
        $currUser = $this->getUser();
        $client = $rdv->getClient()->getFullName();

        try {
            $status = $this->get('kgc.rdv.manager')->cancelPreAuthorization($rdv);
            if ($status && $status->isCanceled()) {
                $this->addFlash('light#remove-light', $client . '--Pré-autorisation annulée.');
            } else {
                $this->addFlash('error#remove', $client . '--Pré-autorisation non annulée.');
            }
        } catch (PaymentFailedException $e) {
            $this->addFlash('error#remove', "Impossible d'annuler la pré-authorisation :--".$e->getCode().' - '.$e->getMessage());
        }

        return $this->render('::emptylayout.html.twig');
    }
}
