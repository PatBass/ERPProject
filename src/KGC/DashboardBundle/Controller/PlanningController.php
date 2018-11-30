<?php

// src/KGC/DashboardBundle/Controller/PlanningController.php


namespace KGC\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * PlanningController.
 *
 * Page de visualisation du planning
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class PlanningController extends Controller
{
    /**
     * Méthode index.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_QUALITE, ROLE_ADMIN_PHONE, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function indexAction()
    {
        $date = new \DateTime();
        $form = $this->createFormBuilder()
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
                'attr' => array(
                    'class' => 'submit-onchange',
                ),
            ))
            ->getForm()
        ;
        $request = $this->get('request');
        $form->submit($request);
        if ($form->isValid()) {
            $date = $form['date']->getData();
        }

        return $this->render('KGCDashboardBundle:Planning:index.html.twig', array(
            'date' => $date,
            'form' => $form->createView(),
        ));
    }

    /**
     * Méthode de planning pour le tchat.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_MANAGER_CHAT")
     */
    public function chatAction()
    {
        $date = new \DateTime();
        $form = $this->createFormBuilder()
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'date-picker' => true,
                'empty_data' => date('d/m/Y'),
                'limit-size' => true,
                'attr' => array(
                    'class' => 'submit-onchange',
                ),
            ))
            ->getForm()
        ;
        $request = $this->get('request');
        $form->submit($request);
        if ($form->isValid()) {
            $date = $form['date']->getData();
        }

        return $this->render('KGCDashboardBundle:Planning:tchat.html.twig', array(
            'date' => $date,
            'form' => $form->createView(),
        ));
    }
}
