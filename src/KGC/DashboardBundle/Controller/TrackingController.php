<?php

// src/KGC/DashboardBundle/Controller/TrackingController.php


namespace KGC\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * TrackingController.
 *
 * Page de gestion des consultations
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class TrackingController extends Controller
{
    /**
     * Méthode index.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles=" ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_MANAGER_PHONE")
     */
    public function indexAction()
    {
        return $this->render('KGCDashboardBundle:Tracking:index.html.twig');
    }
}
