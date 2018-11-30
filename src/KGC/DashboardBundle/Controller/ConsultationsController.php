<?php

// src/KGC/DashboardBundle/Controller/ConsultationsController.php


namespace KGC\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * ConsultationsController.
 *
 * Page de gestion des consultations
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class ConsultationsController extends Controller
{
    /**
     * Méthode index.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD")
     */
    public function indexAction()
    {
        return $this->render('KGCDashboardBundle:Consultations:index.html.twig');
    }

    /**
     * Méthode index.
     *
     *
     * @param $id int
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_VOYANT")
     */
    public function ficheVoyantAction($id)
    {
        return $this->render('KGCDashboardBundle:Consultations:fiche.voyant.html.twig', array(
            'id' => $id,
        ));
    }
}
