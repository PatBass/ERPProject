<?php

// src/KGC/RdvBundle/Controller/EnvoiProduitController.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\CommonBundle\Service\Paginator;
use KGC\RdvBundle\Entity\EnvoiProduit;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnvoiProduitController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class EnvoiProduitController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCRdvBundle:EnvoiProduit';
    }

    /**
     * Suivi envois de produits.
     *
     * @return Response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_QUALITE, ROLE_MANAGER_PHONE")
     */
    public function indexAction($page = 1)
    {
        $request = $this->get('request');
        $begin = $request->getSession()->get('product_sending_period_begin');
        $end = $request->getSession()->get('product_sending_period_end');
        $state_filter = $request->getSession()->get('product_sending_state_filter');
        if ($state_filter === null) {
            $state_filter = [EnvoiProduit::PLANNED];
        }

        $form = $this->createFormBuilder()
            ->add('period', new DatePeriodType(), [
                'required' => false,
                'data' => ['begin' => $begin, 'end' => $end],
            ])
            ->add('etat', 'choice', [
                'choices' => [
                    EnvoiProduit::PLANNED => 'Prévus',
                    EnvoiProduit::DONE => 'Faits',
                    EnvoiProduit::CANCELLED => 'Annulés',
                ],
                'expanded' => true,
                'multiple' => true,
                'data' => $state_filter,
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isValid()) {
            $begin = $form['period']['begin']->getData();
            $request->getSession()->set('product_sending_period_begin', $begin);
            $end = $form['period']['end']->getData();
            $request->getSession()->set('product_sending_period_end', $end);
            $state_filter = $form['etat']->getData();
            $request->getSession()->set('product_sending_state_filter', $state_filter);
        } elseif ($form->isSubmitted()) {
            if ($form['period']['begin']->getData() === null) {
                $request->getSession()->remove('product_sending_period_begin');
                $request->getSession()->remove('product_sending_period_end');
            }
        }

        $endClone = null;
        if ($end) {
            $endClone = clone $end;
            $endClone->add(new \DateInterval('P1D'));
        }

        $count = $this->getRepository()->getTableSuiviCount($state_filter, $begin, $endClone);
        $paginator = new Paginator($count, $page, 20);
        $interval = $paginator->getLimits();

        $liste = $this->getRepository()->getTableSuivi($interval, $state_filter, $begin, $endClone);

        return $this->render('KGCRdvBundle:EnvoiProduit:index.html.twig', [
            'liste' => $liste,
            'form' => $form->createView(),
            'etats' => $state_filter,
            'begin' => $begin,
            'end' => $end,
            'paginator' => $paginator, ]
        );
    }
}
