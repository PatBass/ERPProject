<?php

// src/KGC/RdvBundle/Controller/J1Controller.php


namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use Symfony\Component\HttpFoundation\Request;

/**
 * J1Controller.
 *
 * @category Controller
 *
 * @author Nicolas MENDEZ <nicolas.kgcom@gmail.com>
 */
class J1Controller extends CommonController
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
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_J_1")
     */
    public function WidgetCanceledListeAction(Request $request, $type)
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

        $form = $this->createForm(new Form\J1Type(['begin' => $begin, 'end' => $end]));
        $form->handleRequest($request);

        switch ($type) {
            case 'nrp':
                $title = 'NRP';
                $idClassement = 14;
                break;
            case 'nvp':
                $title = 'NVP';
                $idClassement = 15;
                break;
            case 'cb':
                $title = 'CB non remplies';
                $idClassement = 19;
                break;
            case 'min':
                $title = '10 minutes';
                $idClassement = 12;
                break;
        }
        if ($form->isValid()) {
            $period = $form->get('period')->getData();

            $request->getSession()->set('j1widget', ['begin' => $period['begin']->format('Y-m-d'), 'end' => $period['end']->format('Y-m-d')]);

            $period['end']->modify('+1 day');

            $rdvs = $this->getRepository()->getJ1Canceled($idClassement, $period);
        } else {
            $type = 'nrp';
            $rdvs = null;
        }


        return $this->render('KGCRdvBundle:J1:canceled.widget.html.twig', ['rdvs' => $rdvs, 'title' => $title, 'type' => $type]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_J_1")
     */
    public function Widget10ListeAction(Request $request)
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

        $form = $this->createForm(new Form\J1Type(['begin' => $begin, 'end' => $end]));
        $form->handleRequest($request);

        $title = '10 minutes';
        $idClassement = 12;

        if ($form->isValid()) {
            $period = $form->get('period')->getData();

            $request->getSession()->set('j1widget', ['begin' => $period['begin']->format('Y-m-d'), 'end' => $period['end']->format('Y-m-d')]);

            $period['end']->modify('+1 day');

            $rdvs = $this->getRepository()->getJ110($idClassement, $period);
        } else {
            $type = 'nrp';
            $rdvs = null;
        }


        return $this->render('KGCRdvBundle:J1:canceled.widget.html.twig', ['rdvs' => $rdvs, 'title' => $title, 'type' => 'min']);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_J_1")
     */
    public function exportCanceledAction(Request $request, $type, $format)
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

        $end->modify('+1 day');

        switch ($type) {
            case 'nrp':
                $idClassement = 14;
                break;
            case 'nvp':
                $idClassement = 15;
                break;
            case 'cb':
                $idClassement = 19;
                break;
            case 'min':
                $idClassement = 12;
                break;
        }
        $period = ['begin'=>$begin, 'end'=>$end];

        $query = $this->getRepository()->getJ1CanceledQuery($idClassement, $period);

        $exporter = $this->get('kgc.rdv.csv_exporter');

        $formatter = null;
        if ($format == 'rdv-crm') {
            $formatter = $this->get('kgc.elastic.rdv.crmrdv_formatter');
        } else {
            $formatter = $this->get('kgc.elastic.rdv.rdv_formatter');
        }

        if ($exporter->export($query, $formatter)) {
            return $exporter->getResponse();
        }
    }


    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_J_1")
     */
    public function export10Action(Request $request, $format)
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

        $end->modify('+1 day');

        $idClassement = 12;
        $period = ['begin'=>$begin, 'end'=>$end];

        $query = $this->getRepository()->getJ110Query($idClassement, $period);

        $exporter = $this->get('kgc.rdv.csv_exporter');

        $formatter = null;
        if ($format == 'rdv-crm') {
            $formatter = $this->get('kgc.elastic.rdv.crmrdv_formatter');
        } else {
            $formatter = $this->get('kgc.elastic.rdv.rdv_formatter');
        }

        if ($exporter->export($query, $formatter)) {
            return $exporter->getResponse();
        }
    }
}
