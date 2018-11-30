<?php

namespace KGC\RdvBundle\Controller;

use KGC\RdvBundle\Service\PlanningService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

/**
 * PlanningController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class PlanningController extends Controller
{
    /**
     * @return PlanningService
     */
    protected function getPlanningService()
    {
        return $this->get('kgc.rdv.planning.service');
    }

    /**
     * Affiche un planning compact mode widget pour le standard.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_STANDAR, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function displayWidgetAction()
    {
        $currentUser = $this->getUser();

        // Si l'utilisateur connecté est un voyant on filtre uniquement ses RDVs
        $user = $currentUser->isVoyant() ? $currentUser : null;
        $removeEmptySlots = null !== $user;
        $end = $currentUser->isVoyant()
            ? new \Datetime('tomorrow')
            : null
        ;

        $result = $this->getPlanningService()->buildSimplePlanning($user, $removeEmptySlots, $end);

        return $this->render('KGCRdvBundle:Planning:widget.html.twig', $result);
    }

    /**
     * Affiche le planning complet du jour en mode fullview.
     *
     * @param \DateTime $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function fullviewAction(\DateTime $date = null)
    {
        $result = $this->getPlanningService()->buildFullPlanning($date);

        return $this->render('KGCRdvBundle:Planning:full.html.twig', $result);
    }

    /**
     * Affiche le planning complet du jour en mode fullview pour le tchat.
     *
     * @param \DateTime $date
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_MANAGER_CHAT")
     */
    public function fullviewTchatAction(\DateTime $date = null)
    {
        $result = $this->getPlanningService()->buildFullTchatPlanning($date);

        return $this->render('KGCRdvBundle:Planning:fullTchat.html.twig', $result);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widgetSelectionAction(Request $request, $vue = null)
    {
        $result = $this->getPlanningService()->buildSelectPlanning($request);
        $view_vars = array_merge($result, ['vue' => $vue]);

        return $this->render('KGCRdvBundle:Planning:selection.html.twig', $view_vars);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widgetSelectionReminderAction(Request $request, $default = null)
    {
        $result = $this->getPlanningService()->buildSelectReminderPlanning($default);

        return $this->render('KGCRdvBundle:Planning:reminder.html.twig', $result);
    }
}
