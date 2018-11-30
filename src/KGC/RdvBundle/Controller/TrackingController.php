<?php
// src/KGC/RdvBundle/Controller/TrackingController.php

namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\CommonBundle\Form\DatePeriodType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackingController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class TrackingController extends CommonController
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
     * Liste les consultations dont l'idastro manque ou est erronné.
     *
     * @return Response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_MANAGER_PHONE")
     */
    public function MissingIdastrosAction()
    {
        $liste = $this->getRepository()->getMissingIdastros();

        return $this->render('KGCRdvBundle:Tracking:missingIdastros.widget.html.twig', array(
            'fiches' => $liste,
        ));
    }
    
    /**
     * @param Request $request
     *
     * @return StreamedResponse
     * @Secure(roles="ROLE_ADMIN")
     */
    public function gclidExportAction(Request $request)
    {
        $serviceSuffix = $request->query->get('type');
        $exporter = null;
        if ('excel' === $serviceSuffix) {
            $exporter = $this->get('kgc.rdv.exporter.gclid.excel');
        } else {
            $exporter = $this->get('kgc.rdv.exporter.gclid.csv');
        }

        $begin = $request->getSession()->get('gclid_period_begin');
        $end = $request->getSession()->get('gclid_period_end');
        $exporter->export([
            'begin' => $begin,
            'end' => $end,
        ]);

        $response = $exporter->getResponse();

        return $response;
    }

    public function gclidWidgetAction()
    {
        $request = $this->get('request');

        $begin = $request->getSession()->get('gclid_period_begin');
        $end = $request->getSession()->get('gclid_period_end');

        $form = $this->createFormBuilder()
            ->add('period', new DatePeriodType())
            ->getForm()
        ;
        $form['period']['begin']->setData($begin);
        $form['period']['end']->setData($end);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $begin = $form['period']['begin']->getData();
            $end = $form['period']['end']->getData();
        } elseif ($form->isSubmitted()) {
            if ($form['period']['begin']->getData() === null) {
                $request->getSession()->remove('gclid_period_begin');
                $request->getSession()->remove('gclid_period_end');
            }
        }

        $begin = $begin ?: new \DateTime('00:00');
        $end = $end ?: new \DateTime('tomorrow 00:00');

        $request->getSession()->set('gclid_period_begin', $begin);
        $request->getSession()->set('gclid_period_end', $end);

        $exporter = $this->get('kgc.rdv.exporter.gclid.excel');

        $items = $exporter->getGclidItemsOrQb($begin, $end);

        return $this->render('KGCRdvBundle:Tracking:gclid.widget.html.twig', [
            'items' => $items,
            'form' => $form->createView(),
            'begin' => $begin,
            'end' => $end,
        ]);
    }
}
