<?php

// src/KGC/DashboardBundle/Controller/J1Controller.php


namespace KGC\DashboardBundle\Controller;

use KGC\RdvBundle\Form\J1Type;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\RdvBundle\Entity\Encaissement;
use KGC\RdvBundle\Form\ImpayeType;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * J1Controller.
 *
 * Page de visualisation du service J-1
 *
 * @category Controller
 *
 * @author Nicolas MENDEZ <nicolas.kgcom@gmail.com>
 */
class J1Controller extends Controller
{
    /**
     * Méthode index.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_J_1")
     */
    public function indexAction(Request $request)
    {
        $j1Widgets = $request->getSession()->get('j1widget') ?: [];
        $currentDate = new \DateTime();
        $currentDate->modify('-1 day');
        $begin = new \DateTime(
            isset($j1Widgets['begin'])
                ? $j1Widgets['begin']
                : $currentDate->format('Y-m-d')
        );
        $end = new \DateTime(
            isset($j1Widgets['end'])
                ? $j1Widgets['end']
                : $currentDate->format('Y-m-d')
        );

        $form = $this->createForm(new J1Type(['begin' => $begin, 'end' => $end]), ['period' => ['begin' => $begin, 'end' => $end]]);

        return $this->render(
            'KGCDashboardBundle:J1:index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
