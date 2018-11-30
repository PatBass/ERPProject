<?php

namespace KGC\ChatBundle\Controller;

use KGC\CommonBundle\Controller\CommonController;
use KGC\StatBundle\Form\PastDateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 *
 */
class StatController extends CommonController
{
    protected function getEntityRepository()
    {
        return '';
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function statsDashboardGeneralAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        $request->getSession()->set('chat_stats_date', $date);

        $params = $this->get('kgc.chat.calculator.stats')->calculate([
            'date' => $date,
            'get_general' => true,
        ]);

        return $this->render('KGCChatBundle:Stats:general.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function statsDetailTurnoverAction(Request $request, $type = '', $periode = '')
    {
        $params = [
            'date' => $request->getSession()->get('chat_stats_date') ? : new \DateTime(),
            'type' => $type,
            'periode' => $periode,
        ];

        $data = $this->get('kgc.chat.calculator.stats')->details($params);

        return $this->render('KGCChatBundle:Stats:turnover_details.html.twig', $data + $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function statsDetailSubscriptionAction(Request $request, $type = '', $periode = '')
    {
        $params = [
            'date' => $request->getSession()->get('chat_stats_date') ? : new \DateTime(),
            'type' => $type,
            'periode' => $periode,
        ];

        $data = $this->get('kgc.chat.calculator.stats')->details($params);

        return $this->render('KGCChatBundle:Stats:subscription_details.html.twig', $data + $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_MANAGER_CHAT")
     */
    public function statsDashboardUsersAction(Request $request)
    {
        $params = $this->get('kgc.chat.calculator.stats')->calculate([
            'get_users' => true,
        ]);

        return $this->render('KGCChatBundle:Stats:users.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_MANAGER_CHAT")
     */
    public function statsDashboardChatterAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $params = $this->get('kgc.chat.calculator.stats')->calculate([
            'date' => $form->get('past_date')->getData(),
            'get_chatter' => true,
        ]);

        return $this->render('KGCChatBundle:Stats:chatter.html.twig',
            $params + ['form' => $form->createView()]
        );
    }
}
