<?php

// src/KGC/DashboardBundle/Controller/UtilisateursController.php


namespace KGC\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * UtilisateursController.
 *
 * Page de gestion des utlisateurs ayant accès à l'application
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class UtilisateursController extends Controller
{
    /**
     * Méthode index.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction()
    {
        return $this->render('KGCDashboardBundle:Utilisateurs:index.html.twig');
    }
}
