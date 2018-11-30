<?php

// src/KGC/DashboardBundle/Controller/QualiteController.php


namespace KGC\DashboardBundle\Controller;

use Doctrine\ORM\EntityRepository;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * QualiteController.
 *
 * Page de visualisation du planning
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class QualiteController extends Controller
{
    /**
     * Méthode index.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_QUALITE, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function indexAction()
    {
        $request = $this->get('request');
        $previousConsultant = $request->getSession()->get('qualite_consultant');
        $consultant = $previousConsultant instanceof Utilisateur ? $this->getDoctrine()->getEntityManager()->merge($previousConsultant) : null;

        $form = $this->createFormBuilder()
                     ->add('consultant', 'entity', array(
                        'class' => 'KGCUserBundle:Utilisateur',
                        'property' => 'username',
                        'empty_value' => 'Tous',
                        'required' => false,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->findAllByMainProfilQB(Profil::VOYANT, true);
                        },
                        'attr' => array(
                            'class' => 'chosen-select submit-onchange',
                        ),
                        'data' => $consultant,
                     ))
                     ->getForm()
        ;
        $form->handleRequest($request);
        if ($form->isValid()) {
            $consultant = $form['consultant']->getData();
            $request->getSession()->set('qualite_consultant', $consultant);
        }

        return $this->render('KGCDashboardBundle:Qualite:index.html.twig', array(
            'consultant' => $consultant,
            'form' => $form->createView(),
        ));
    }
}
