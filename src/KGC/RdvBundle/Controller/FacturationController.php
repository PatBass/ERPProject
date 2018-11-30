<?php

// src/KGC/RdvBundle/Controller/FacturationnController.php


namespace KGC\RdvBundle\Controller;

use KGC\CommonBundle\Controller\CommonController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\RdvBundle\Lib\Facture;

/**
 * FacturationController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class FacturationController extends CommonController
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
     * Méthode index.
     *
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONIST")
     */
    public function indexAction($id)
    {
        $rdv = $this->getRepository()->findOneById($id);

        return $this->render('KGCRdvBundle:Facturation:index.modal.html.twig', array(
            'rdv' => $rdv,
        ));
    }

    /**
     * génère et affiche la facture.
     *
     * @param $id
     *
     * @return Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONIST")
     */
    public function affichageAction($id)
    {
        $consult = $this->getRepository()->findOneById($id);
        if ($consult) {
            $billingService = $this->get('kgc.billing.service');
            $billingService->create($consult);

            return new Response($billingService->Output(), 200, array(
                'Content-Type' => 'application/pdf',
            ));
        } else {
            return $this->render('KGCRdvBundle:Facturation:erreur.html.twig');
        }
    }

    /**
     * Méthode mail : génère la facture et envoi auto par mail.
     *
     *
     * @param int $id
     */
    public function mailAction($id)
    {
    }

    /**
     *
     */
    public function ajaxCalcMontantMinutesAction($idCodeTarif, $nbMin)
    {
        $mt = 0;
        $cdt_rep = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:CodeTarification');
        $code_tarification = $cdt_rep->findOneById($idCodeTarif);
        if ($code_tarification != null) {
            $mt = $code_tarification->getMultiplicateur() * $nbMin;
        }

        return $this->jsonResponse(['mt' => $mt]);
    }
}
