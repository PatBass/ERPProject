<?php

namespace KGC\RdvBundle\Controller;

use KGC\CommonBundle\Controller\CommonController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\RdvBundle\Form;
use KGC\RdvBundle\Form\Handler;
use KGC\RdvBundle\Entity\ActionSuivi;
use KGC\RdvBundle\Events\RDVActionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * MiserelationController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class MiserelationController extends CommonController
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
     * Méthode Miserelation.
     *
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST, ROLE_VALIDATION")
     */
    public function MiserelationAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $rdv = $this->getRepository()->findOneById($id);
        if ($rdv) {
            $request = $this->get('request');
            $form_edit = $this->createForm(new Form\RDVEditType($rdv));
            $form_edithandler = new Handler\RDVEditHandler($form_edit, $request);
            $param_edit = $form_edithandler->process();
            $currUser = $this->getUser();
            $form = $this->createForm(new Form\RDVMiserelationType($currUser, $em, $param_edit, true, $this->getUser()->getIsDecryptAvailable()), $rdv);
            $formhandler = $this->get('kgc.rdv.formhandler');
            $result = $formhandler->process($form, $request);
            $client = $rdv->getClient()->getFullName();

            if ($result === true) { // form submit valid
                $msg = $form['miserelation']->getData() == 1
                    ? 'Consultation confirmée'
                    : 'Consultation annulée.';
                $this->addFlash('light#exchange-light', $client.'--Mise en relation enregistrée. '.$msg);
            } elseif ($result === false) { // form submit invalid
                $this->addFlash('error#exchange', $client.'--Mise en relation non validée');
            }
        }

        return $this->render('KGCRdvBundle:Miserelation:miserelation.html.twig', array(
            'form' => isset($form) ? $form->createView() : false,
            'form_edit' => isset($form_edit) ? $form_edit->createView() : false,
            'rdv' => $rdv,
        ));
    }

    /**
     * valide la prise en charge de la consultaion par le voyant.
     *
     * @param $id
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     *
     * @Secure(roles="ROLE_VOYANT")
     */
    public function PriseenchargeAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $rep_rdv = $em->getRepository('KGCRdvBundle:RDV');
        $pec = $rep_rdv->getPEC($this->getUser());
        if ($pec) {
            $this->addFlash('error#comments-alt', '--Une consultation est déjà prise en charge.');
        } else {
            $rdv = $this->getRepository()->findOneById($id);

            if (null === $rdv) {
                throw new NotFoundHttpException(
                    sprintf('Object with id: %d not found', $id)
                );
            }

            $client = $rdv->getClient()->getFullName();
            $manager = $this->get('kgc.rdv.manager');
            if ($manager->isTakeable($rdv)) {
                // Création de l'entrée d'historique
                $suiviRdvManager = $this->get('kgc.suivirdv.manager');
                $suiviRdvManager->create($rdv);

                // main action prise en charge + ajout à l'historique
                $action_rep = $em->getRepository('KGCRdvBundle:ActionSuivi');
                $main_action = $action_rep->findOneByIdcode(ActionSuivi::TAKE_CONSULT);
                $suiviRdvManager->setMainAction($main_action);
                $event = array(
                    'name' => constant('\KGC\RdvBundle\Events\RDVEvents::'.$main_action->getEvent()),
                    'object' => new RDVActionEvent($rdv, $this->getUser()),
                );
                $eventDispatcher = $this->get('event_dispatcher');
                $eventDispatcher->dispatch($event['name'], $event['object']);

                // mise à jour de l'historique avec nouveaux paramètres du rdv
                $suiviRdvManager->fillRdvInfos($rdv);
                // succès flush
                $em->flush();

                $this->addFlash('light#comments-alt-light', $client.'--Consultation prise en charge.');
            } else {
                $this->addFlash('error#comments-alt', $client.'--Prise en charge non autorisée.');
            }
        }

        $result = [
            'redirect_uri' => $this->get('router')->generate('kgc_dashboard', [], true),
        ];

        return $this->jsonResponse($result);
    }

    /**
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function effectueesWidgetAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('KGCRdvBundle:RDV');
        $liste = $repo->getEffectuees(8);

        return $this->render('KGCRdvBundle:Miserelation:effectuees.widget.html.twig', array(
            'liste' => $liste,
        ));
    }
}
