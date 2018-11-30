<?php

// src/KGC/UserBundle/Controller/SecurityController.php


namespace KGC\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * SecurityController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class SecurityController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        $request = $this->get('request');
        $session = $request->getSession();

        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        if ($request->isXmlHttpRequest()) {
            return $this->render('KGCUserBundle:Security:ajaxlogin.html.twig');
        } else {
            return $this->render('KGCUserBundle:Security:login.html.twig', array(
              'last_username' => $session->get(SecurityContext::LAST_USERNAME),
              'error' => $error,
            ));
        }
    }
}
