<?php
// src/KGC/RdvBundle/Controller/TelecollecteController.php

namespace KGC\RdvBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\RdvBundle\Entity\Telecollecte;
use KGC\RdvBundle\Form\TelecollecteType;
use KGC\RdvBundle\Repository\TPERepository;
use KGC\StatBundle\Form\PastDateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FacturationController.
 *
 * @category Controller
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class TelecollecteController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCRdvBundle:Telecollecte';
    }

    /**
     * @return Response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN")
     */
    public function telecollecteWidgetAction(Request $request)
    {
        $tpe_repo = $this->getDoctrine()->getManager()->getRepository('KGCRdvBundle:TPE');
        $form = $this->createFormBuilder()
                ->add('past_date', new PastDateType())
                ->add('tpe', 'entity', [
                    'class' => 'KGCRdvBundle:TPE',
                    'query_builder' => function(TPERepository $r){
                        return $r->getTelecollecteTPEQB();
                    },
                    'property' => 'libelle',
                    'required' => true,
                    'input_addon' => 'credit-card',
                    'data' => $tpe_repo->getTelecollecteDefaultTPE(),
                    'attr' => ['class'=>'submit-onchange']
                ])
                ->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        list($begin, $end) = $this->get('kgc.stat.calculator.admin')->getFullMonthIntervalFromDate($date);
        $tpe = $form->get('tpe')->getData();
        $tls_repo = $this->getRepository();
        $data = $tls_repo->getByDateAndTpe($begin, $end, $tpe);

        return $this->render('KGCRdvBundle:Telecollecte:widget.html.twig', array(
            'data' => $data,
            'form' => $form->createView()
        ));
    }
    
    /**
     * @return Response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN")
     */
    public function telecollecteAjouterAction(Request $request, $id)
    {
        $tlc = new Telecollecte();
        $modif = false;
        $close = false;
        if ($id != 0) {
            $tlc = $this->findById($id);
            $modif = true;
        }
        $form = $this->createForm(new TelecollecteType(), $tlc);
        $form->handleRequest($request);
        $statusMsg = $modif ? 'modifiée' : 'ajoutée';
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $close = $form['fermeture']->getData();
                $dmg = $this->getDoctrine()->getManager();
                $dmg->persist($tlc);
                $dmg->flush();
                
                $msg = sprintf('Télécollecte %s -- Pour %s le %s.', $statusMsg, $tlc->getTpe()->getLibelle(), $tlc->getDate()->format('d/m/Y'));
                $this->addFlash('light#credit-card-light', $msg);
                if ($modif) {
                    $form = $this->createForm(new TelecollecteType());
                }
            } else {
                $msg = sprintf('Erreur -- Télécollecte non %s.', $statusMsg);
                $this->addFlash('error#credit-card', $msg);
            }
        }

        return $this->render('KGCRdvBundle:Telecollecte:ajouter.html.twig', array(
            'form' => $form->createView(),
            'modif' => $modif,
            'tlc' => $tlc,
            'close' => $close,
        ));
    }
}
