<?php

// src/KGC/DashboardBundle/Controller/ImpayeController.php


namespace KGC\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Form\ImpayeType;

/**
 * ImpayeController.
 *
 * Page de visualisation du planning
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class ImpayeController extends Controller
{
    /**
     * Méthode index.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function indexAction(Request $request)
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

        $form = $this->createForm(new ImpayeType(['begin' => $begin, 'end' => $end]), ['period' => ['begin' => $begin, 'end' => $end]]);

        return $this->render(
            'KGCDashboardBundle:Impaye:index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
