<?php
// src/KGC/UserBundle/Controller/UtilisateurController.php

namespace KGC\UserBundle\Controller;

use KGC\UserBundle\Form\UtilisateurPosteType;
use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Controller\CommonController;
use KGC\UserBundle\Service\UserManager;
use KGC\UserBundle\Entity\Journal;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Form\JournalType;
use KGC\UserBundle\Form\UtilisateurType;

/**
 * Class UtilisateurController.
 */
class UtilisateurController extends CommonController
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @return string
     */
    protected function getEntityRepository()
    {
        return 'KGCUserBundle:Utilisateur';
    }

    /**
     * @param UserManager $userManager
     * @DI\InjectParams({
     *                                 "userManager" = @DI\Inject("kgc.user.manager")
     *                                 })
     */
    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Méthode widgetListe.
     *
     * Liste des utilisateurs sous forme de widget chargé en asynchrone
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function widgetListeAction()
    {
        return $this->render('KGCUserBundle:Utilisateur:liste.html.twig', array(
            'listeUtils' => $this->findAll(false),
        ));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function modalAjouterAction(Request $request, $id)
    {
        $user = new Utilisateur();
        $modif = false;
        $close = false;
        if ($id != 0) {
            $user = $this->findById($id);
            $modif = true;
        }
        $form = $this->createForm(new UtilisateurType(), $user);
        $form->handleRequest($request);
        $statusMsg = $modif ? 'modifié' : 'ajouté';
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $close = $form['fermeture']->getData();
                $this->userManager->updateUser($user);
                $msg = sprintf('%s -- Utilisateur %s (Id : %s).', $user->getUsername(), $statusMsg, $user->getId());
                $this->addFlash('light#user-light', $msg);
                if ($modif) {
                    $form = $this->createForm(new UtilisateurType());
                }
            } else {
                $msg = sprintf('%s -- Utilisateur non %s (Id : %s).', $user->getUsername(), $statusMsg, $user->getId());
                $this->addFlash('error#user', $msg);
            }
        }

        return $this->render('KGCUserBundle:Utilisateur:ajouter.html.twig', array(
            'form' => $form->createView(),
            'modif' => $modif,
            'user' => $user,
            'close' => $close,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function modalSupprimerAction(Request $request, $id)
    {
        $flag = false;
        $user = $this->findById($id);
        $form = $this->createFormBuilder($user)->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $flag = true;
            $this->userManager->removeUser($user);
            $this->addFlash(
                'light#user-light',
                $user->getUsername().'--Utilisateur supprimé.'
            );
        }

        return $this->render('KGCUserBundle:Utilisateur:supprimer.html.twig', array(
            'utilisateur' => $user,
            'form' => $form->createView(),
            'flag' => $flag,
        ));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function posteAction(Request $request)
    {
        $user = $this->getUser();
        $close = false;
        $form = $this->createForm(new UtilisateurPosteType(), $user);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $close = $form['fermeture']->getData();
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->addFlash('light#user-light', 'Poste '.($user->getPoste() ? $user->getPoste()->getName() : 'Aucun').'-- affecté');
            } else {
                $this->addFlash('error#user', 'erreur');
            }
        }

        return $this->render('KGCUserBundle:Utilisateur:poste.html.twig', array(
            'form' => $form->createView(),
            'close' => $close,
        ));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function posteWidgetAction(Request $request)
    {
        $user = $this->getUser();

        return $this->render('KGCUserBundle:Utilisateur:poste.widget.html.twig', array(
            'poste' => $user->getPoste(),
        ));
    }

    /**
     * Logging into database of the last activity time of the connected user
     * Useful to track inactivity.
     */
    public function aliveAction()
    {
        $currentUser = $this->getUser();
        if (null !== $currentUser) {
            $user = $this->findById($currentUser->getId());
            $user->setLastActiveTime(new \Datetime());
            $this->getDoctrine()->getManager()->flush($user);

            return $this->jsonResponse(['status' => 'OK']);
        }

        return $this->jsonResponse(['status' => 'ANONYMOUS']);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function modalJournalAction(Request $request, $id)
    {
        $close = false;
        $currUser = $this->getUser();
        $user = $this->findById($id);
        if ($user !== null && $currUser->isAdmin()) {
            $journal = new Journal($currUser, $user);
            $form = $this->createForm(new JournalType(), $journal);
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $close = $form['fermeture']->getData();
                    $this->userManager->addLog($user, $journal);
                    $msg = sprintf('%s -- Journal mis à jour.', $user->getUsername());
                    $this->addFlash('light#user-light', $msg);

                    $journal = new Journal($currUser, $user);
                    $form = $this->createForm(new JournalType(), $journal);
                } else {
                    $msg = sprintf('%s -- Journal non mis à jour.', $user->getUsername());
                    $this->addFlash('error#user', $msg);
                }
            }
        }

        return $this->render('KGCUserBundle:Utilisateur:journal.html.twig', array(
            'form' => isset($form) ? $form->createView() : false,
            'user' => $user,
            'close' => $close,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function switchEnableAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ($id) {
            $o = $this->findById($id);
            $o->setActif(!$o->getActif());
            $statusMsg = $o->getActif() ? 'activé' : 'désactivé';
            $this->addFlash('light#user-light', $o->getUsername().'--Utilisateur '.$statusMsg.'.');

            $em->flush();
        } else {
            $this->addFlash('error#user', 'Erreur--Identifiant de lʼutilisateur manquant.');
        }

        return $this->jsonResponse([true]);
    }
}
