<?php

// src/KGC/UserBundle/Controller/UtilisateurController.php


namespace KGC\UserBundle\Controller;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\Bundle\SharedBundle\Entity\TotalLeads;
use KGC\RdvBundle\Controller\ConsultationController;
use KGC\UserBundle\Form\ProspectDRIType;
use KGC\UserBundle\Form\ProspectEditType;
use KGC\UserBundle\Form\ProspectType;
use KGC\UserBundle\Handler\ProspectEditHandler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LandingUserController.
 */
class LandingUserController extends CommonController
{

    /**
     * @return string
     */
    protected function getEntityRepository()
    {
        return 'KGCSharedBundle:LandingUser';
    }

    /**
     * @param Request $request
     * @param $date
     * @param $type
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function leadsModalWebsiteDetailsAction(Request $request, $date = null, $type, $id)
    {
        $export = $request->query->has('export');

        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $date = new \DateTime($date);
        $website = $this->getRepository('KGCSharedBundle:Website')->find($id);
        $list = $this->getRepository()->getLeadsOfDay($date, 'website', $website, null, $type);

        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $list), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_prospect_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCUserBundle:Prospect:prospects_details.html.twig', array(
            'list' => $list
        ));
    }


    /**
     * @param Request $request
     * @param $date
     * @param $type
     * @param $id
     * @param $id2
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function leadsModalSourceDetailsAction(Request $request, $date = null, $type, $id, $id2)
    {
        $export = $request->query->has('export');
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $date = new \DateTime($date);
        $website = $this->getRepository('KGCSharedBundle:Website')->find($id);
        $source = $this->getRepository('KGCRdvBundle:Source')->find($id2);
        $list = $this->getRepository()->getLeadsOfDay($date, 'source', $website, $source, $type);
        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $list), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_prospect_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }
        return $this->render('KGCUserBundle:Prospect:prospects_details.html.twig', array(
            'list' => $list
        ));
    }


    /**
     * @param Request $request
     * @param $date
     * @param $type
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function leadsModalGlobalDetailsAction(Request $request, $date = null, $type)
    {
        $export = $request->query->has('export');
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $date = new \DateTime($date);
        $list = $this->getRepository()->getLeadsOfDay($date, 'global', $this->getRepository('KGCSharedBundle:Website')->getWebsitesLeadsOrder(), null, $type);

        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $list), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_prospect_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCUserBundle:Prospect:prospects_details.html.twig', array(
            'list' => $list
        ));
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_PHONISTE, ROLE_MANAGER_PHONIST, ROLE_QUALITE, ROLE_MANAGER_PHONE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_DRI, ROLE_J_1")
     */
    public function voirFicheAction(Request $request, $id)
    {
        $forceEmpty = $request->query->get('forceEmpty');
        $forceEmpty = $forceEmpty ?: false;

        $prospect = $this->getRepository()->find($id);
        $em = $this->getDoctrine()->getManager();
        $website = null;
        if ($prospect) {
            $request = $this->get('request');
            $request->getSession()->set('original_prospect', clone $prospect);
            $form_edit = $this->createForm(new ProspectEditType($prospect));
            $form_edithandler = new ProspectEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $website = $prospect->getWebsite() ?: $this->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            $source = $prospect->getSourceConsult() ?: $this->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            $codePromo = $prospect->getCodePromo() ?: $this->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            $voyant = $prospect->getVoyant() ?: $this->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            if (!$prospect->getFormurl()) {
                $find = ['label' => strtolower($prospect->getMyastroUrl())];
                if (!empty($website)) {
                    $find['website'] = $website;
                }
                if (!empty($source)) {
                    $find['source'] = $source;
                }
                $formurl = $this->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
            } else {
                $formurl = $prospect->getFormurl();
            }

            $support = $prospect->getSupport() ?: $this->getRepository('KGCRdvBundle:Support')->findOneByLibelle($prospect->getMyastroSupport());
            $linkEntities = ['website' => $website, 'source' => $source, 'codePromo' => $codePromo, 'formurl' => $formurl, 'voyant' => $voyant, 'state' => $prospect->getState(), 'support' => $support];
            $formType = new ProspectType($this->getUser(), $param_edit, $em, $linkEntities);
            $form = $this->createForm($formType, $prospect);
            $formhandler = $this->get('kgc.prospect.formhandler');
            $result = $formhandler->process($form, $request);
            if ($result !== null) { // submit
                if ($result) { // form soumis ok
                    $this->addFlash('light#pencil-light', $prospect->getFirstName() . '--prospect modifié.');
                    $client = $this->getRepository('KGCSharedBundle:Client')->findOneBymail($prospect->getEmail());
                    $close = $form['fermeture']->getData();
                } elseif ($result === false) { // submit invalid
                    $this->addFlash('error#plus', $prospect->getFirstName() . '--prospect non modifié.');
                }
            }

        }
        return $this->render('KGCUserBundle:Prospect:prospect_fiche.html.twig', array(
            'prospect' => $prospect,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => (isset($form_edit)) ? $form_edit->createView() : null,
            'linkEntities' => $linkEntities,
            'close' => isset($close) ? $close : null,
            'options' => ['rdv_btn' => false, 'btn_save' => true, 'fermeture' => true],
            'forceEmpty' => $forceEmpty,
            'consultation' => false
        ));
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_UNPAID_SERVICE, ROLE_J_1, ROLE_DRI, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST, ROLE_PHONISTE")
     */
    public function newConsultationProspectFicheAction(Request $request, $id)
    {
        $forceEmpty = $request->query->get('forceEmpty');
        $forceEmpty = $forceEmpty ?: false;

        $prospect = $this->getRepository()->find($id);
        $em = $this->getDoctrine()->getManager();
        $website = null;
        if ($prospect) {
            $request = $this->get('request');
            $request->getSession()->set('original_prospect', clone $prospect);
            $form_edit = $this->createForm(new ProspectEditType($prospect));
            $form_edithandler = new ProspectEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();

            $website = $prospect->getWebsite() ?: $this->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
            $source = $prospect->getSourceConsult() ?: $this->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
            $codePromo = $prospect->getCodePromo() ?: $this->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
            $voyant = $prospect->getVoyant() ?: $this->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
            if (!$prospect->getFormurl()) {
                $find = ['label' => strtolower($prospect->getMyastroUrl())];
                if (!empty($website)) {
                    $find['website'] = $website;
                }
                if (!empty($source)) {
                    $find['source'] = $source;
                }
                $formurl = $this->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
            } else {
                $formurl = $prospect->getFormurl();
            }
            $support = $prospect->getSupport() ?: $this->getRepository('KGCRdvBundle:Support')->findOneByLibelle($prospect->getMyastroSupport());
            $linkEntities = ['website' => $website, 'source' => $source, 'codePromo' => $codePromo, 'formurl' => $formurl, 'voyant' => $voyant, 'state' => $prospect->getState(), 'support' => $support];
            $formType = new ProspectType($this->getUser(), $param_edit, $em, $linkEntities);
            $form = $this->createForm($formType, $prospect);
            $formhandler = $this->get('kgc.prospect.formhandler');
            $result = $formhandler->process($form, $request);
            if ($result !== null) { // submit
                if ($result) { // form soumis ok
                    $this->addFlash('light#pencil-light', $prospect->getFirstName() . '--prospect modifié.');
                    $controller = new ConsultationController();
                    $controller->setContainer($this->container);
                    return $controller->AddRdvByProspectAction("modal", $prospect->getId());
                } else {
                    $this->addFlash('error#plus', $prospect->getFirstName() . '--prospect non modifié.');
                }
            }

        }
        return $this->render('KGCUserBundle:Prospect:prospect_fiche.html.twig', array(
            'prospect' => $prospect,
            'form' => isset($form) ? $form->createView() : null,
            'form_edit' => (isset($form_edit)) ? $form_edit->createView() : null,
            'linkEntities' => $linkEntities,
            'close' => isset($close) ? $close : null,
            'options' => ['rdv_btn' => true, 'btn_save' => false, 'fermeture' => false],
            'forceEmpty' => $forceEmpty,
            'consultation' => true
        ));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_PHONISTE, ROLE_DRI, ROLE_MANAGER_PHONE")
     */
    public function driFormAction(Request $request)
    {
        $export = $request->query->has('export');

        $em = $this->getDoctrine()->getManager();
        $formType = new ProspectDRIType($this->getUser(), $em);
        $form = $this->createForm($formType);
        $form->handleRequest($request);
        $data = null;
        $formData = $form->getData();
        if (null !== $formData) {
            if ($form["begin"]->getData()) {
                $data['begin'] = $form["begin"]->getData();
            }
            if ($form["end"]->getData()) {
                $data['end'] = $form["end"]->getData();
            }
            if ($form["firstName"]->getData()) {
                $data['firstName'] = $form["firstName"]->getData();
            }
            if ($form["website"]->getData()) {
                $data["website"] = $form["website"]->getData()->getId();
            }
            if ($form["sourceConsult"]->getData()) {
                $data["sourceConsult"] = $form["sourceConsult"]->getData()->getId();
            }
            if ($form["support"]->getData()) {
                $data["support"] = $form["support"]->getData()->getId();
            }
            if ($form["formurl"]->getData()) {
                $data["formurl"] = $form["formurl"]->getData()->getId();
            }
            if ($form["codePromo"]->getData()) {
                $data["codePromo"] = $form["codePromo"]->getData()->getId();
            }
            if ($form["state"]->getData()) {
                $data["state"] = $form["state"]->getData()->getId();
            }
        } else {
            $data = ['id' => false];
        }
        if (!$export) {
            $request->getSession()->set('driRequest', $data);
        } else {
            $data = $request->getSession()->get('driRequest');
        }

        $elemByPage = 10;
        $limit = $elemByPage;

        $totalItemsCount = $this->getRepository()->getNbDRI($data);
        $currentPage = $totalItemsCount > $elemByPage && $form["page"]->getData() ? $form["page"]->getData() : 1;
        $offset = ($currentPage - 1) * $limit;
        $offset = max(0, $offset); // Cannot be negative
        $elements = $this->getRepository()->searchDRI($data, $offset, $limit);
        $totalPagesCount = $totalItemsCount > $elemByPage ? ceil($totalItemsCount / $elemByPage) : 1;
        $indexMin = max(1, $currentPage - 2);
        $indexMax = min($indexMin + 4, $totalPagesCount);
        $showPages = range($indexMin, $indexMax, 1);

        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $this->getRepository()->searchDRI($data, 0, $totalItemsCount)), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_dri_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        $driHandled = $this->getRepository()->searchDRI($data, $offset, $limit, true);

        return $this->render('KGCUserBundle:DRI:dri_form_widget.html.twig', [
            'paginator' => ['totalItemsCount' => $totalItemsCount, 'totalPagesCount' => $totalPagesCount, 'currentPage' => $currentPage, 'showPages' => $showPages, 'elements' => $elements],
            'landingStates' => $em->getRepository('KGCSharedBundle:LandingState')->findAll(),
            'driHandled' => $driHandled,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Affiche Les DRIs.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function driWidgetAction(Request $request)
    {
        $export = $request->query->has('export');
        $em = $this->getDoctrine()->getManager();
        $formType = new ProspectDRIType($this->getUser(), $em);
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        $elemByPage = 10;
        $limit = $elemByPage;

        $totalItemsCount = $this->getRepository()->getNbDRI();
        $currentPage = $totalItemsCount > $elemByPage && $form["page"]->getData() ? $form["page"]->getData() : 1;
        $offset = ($currentPage - 1) * $limit;
        $offset = max(0, $offset); // Cannot be negative
        $elements = $this->getRepository()->searchDRI([], $offset, $limit);
        $totalPagesCount = $totalItemsCount > $elemByPage ? ceil($totalItemsCount / $elemByPage) : 1;
        $indexMin = max(1, $currentPage - 2);
        $indexMax = min($indexMin + 4, $totalPagesCount);
        $showPages = range($indexMin, $indexMax, 1);

        $driHandled = $this->getRepository()->searchDRI([], $offset, $limit, true);

        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $this->getRepository()->searchDRI([], 0, $totalItemsCount)), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_dri_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCUserBundle:DRI:dri_widget.html.twig', [
            'paginator' => ['totalItemsCount' => $totalItemsCount, 'totalPagesCount' => $totalPagesCount, 'currentPage' => $currentPage, 'showPages' => $showPages, 'elements' => $elements],
            'driHandled' => $driHandled,
            'landingStates' => $em->getRepository('KGCSharedBundle:LandingState')->findAll(),
            'form' => $form->createView(),
        ]);
    }

    private function getDriForm(Request $request, $name, $twig)
    {
        $export = $request->query->has('export');
        $em = $this->getDoctrine()->getManager();
        $formType = new ProspectDRIType($this->getUser(), $em);
        $form = $this->createForm($formType);
        $form->handleRequest($request);

        $elemByPage = 10;
        $limit = $elemByPage;
        $datas = ['begin' => new \DateTime('first day of this month'),'state' => $em->getRepository('KGCSharedBundle:LandingState')->findByName($name)];
        $totalItemsCount = $this->getRepository()->getNbDRI($datas);
        $currentPage = $totalItemsCount > $elemByPage && $form["page"]->getData() ? $form["page"]->getData() : 1;
        $offset = ($currentPage - 1) * $limit;
        $offset = max(0, $offset); // Cannot be negative
        $elements = $this->getRepository()->searchDRI($datas, $offset, $limit);
        $totalPagesCount = $totalItemsCount > $elemByPage ? ceil($totalItemsCount / $elemByPage) : 1;
        $indexMin = max(1, $currentPage - 2);
        $indexMax = min($indexMin + 4, $totalPagesCount);
        $showPages = range($indexMin, $indexMax, 1);

        $driHandled = $this->getRepository()->searchDRI($datas, $offset, $limit, true);

        if ($export) {
            $data = $this->get('kgc.prospect.decorator.csv')->decorate(array('list' => $this->getRepository()->searchDRI($datas, 0, $totalItemsCount)), ['standard_details' => 1]);
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_dri_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }
        return $this->render('KGCUserBundle:DRI:' . $twig . '_widget.html.twig', [
            'paginator' => ['totalItemsCount' => $totalItemsCount, 'totalPagesCount' => $totalPagesCount, 'currentPage' => $currentPage, 'showPages' => $showPages, 'elements' => $elements],
            'driHandled' => $driHandled,
            'landingStates' => $em->getRepository('KGCSharedBundle:LandingState')->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Affiche Les NRP.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function nrpWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'NRP', 'nrp');
    }

    /**
     * Affiche Les a rappeler.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function recallWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'A rappeler', 'recall');
    }

    /**
     * Affiche Les NVP.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function nvpWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'NVP', 'nvp');
    }

    /**
     * Affiche Les Faux numero.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function fnWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'Faux numéro', 'fn');
    }

    /**
     * Affiche Les En FNA.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function fnaWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'En FNA', 'fna');
    }

    /**
     * Affiche Les hésitants.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function hesitantWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'Hésitant', 'hesitant');
    }

    /**
     * Affiche Les sans CB.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function ssCbWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'Sans CB', 'sscb');
    }

    /**
     * Affiche Les mineurs.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE, ROLE_DRI")
     */
    public function mineurWidgetAction(Request $request)
    {
        return $this->getDriForm($request, 'Mineur', 'mineur');
    }
}
