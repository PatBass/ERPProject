<?php

namespace KGC\ChatBundle\Controller;

use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPromotion;
use KGC\ChatBundle\Entity\ChatSubscription;
use KGC\ChatBundle\Entity\ChatType;
use KGC\ChatBundle\Form\ChatFormulaRateType;
use KGC\ChatBundle\Form\ChatManualSubscriptionType;
use KGC\ChatBundle\Form\ChatPaymentOfferType;
use KGC\ChatBundle\Form\ChatPromotionFilterType;
use KGC\ChatBundle\Form\ChatPromotionType;
use KGC\ChatBundle\Form\ChatWebsiteType;
use KGC\CommonBundle\Controller\CommonController;
use KGC\Bundle\SharedBundle\Entity\Client;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class FormulaController.
 */
class FormulaController extends CommonController
{
    protected function getEntityRepository()
    {
        return 'KGCChatBundle:ChatFormulaRate';
    }

    /**
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function listAction(Request $request)
    {
        $session = $request->getSession();
        if ($websiteId = $session->get('chat_formula_website')) {
            $website = $this->getDoctrine()->getEntityManager()->getRepository('KGCSharedBundle:Website')->find($websiteId);
        } else {
            $website = null;
        }

        $form = $this->createForm(new ChatWebsiteType, ['website' => $website]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $website = $form->get('website')->getData();
            $session->set('chat_formula_website', $website ? $website->getId() : null);
        }

        $formulas = $this->getRepository()->findEditableChatFormulaRatesByWebsite($website);

        return $this->render('KGCChatBundle:Formula:list.html.twig',
            [
                'form' => $form->createView(),
                'formulas' => $formulas
            ]
        );
    }

    /**
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function listPromoAction(Request $request)
    {
        $session = $request->getSession();
        if ($websiteId = $session->get('chat_promotion_website')) {
            $website = $this->getDoctrine()->getEntityManager()->getRepository('KGCSharedBundle:Website')->find($websiteId);
        } else {
            $website = null;
        }

        $form = $this->createForm(new ChatPromotionFilterType, ['website' => $website]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $website = $form->get('website')->getData();
            $session->set('chat_promotion_website', $website ? $website->getId() : null);
        }

        $promotions = $this->getRepository('KGCChatBundle:ChatPromotion')->findChatPromotionsByWebsite($website);

        return $this->render(
            'KGCChatBundle:Formula:promo.list.html.twig',
            [
                'form' => $form->createView(),
                'promotions' => $promotions
            ]
        );
    }

    /**
     * @param Request         $request
     * @param ChatFormulaRate $formula
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function editAction(Request $request, ChatFormulaRate $formula)
    {
        $form = $this->createForm(new ChatFormulaRateType($formula->getType(), $formula->getChatFormula()->getChatType()), $formula);
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getEntityManager();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $em->persist($formula);
                $em->flush();
                $this->addFlash('light#cog-light', 'Formule--modifiée.');
            } else {
                $this->addFlash('error#cog', 'Formule--Non modifiée.');
            }
        }

        $history = $em->getRepository('KGCCommonBundle:Log')->getShortenedFormulaLogEntries($formula);

        return $this->render('KGCChatBundle:Formula:edit.html.twig',
            [
                'form' => $form->createView(),
                'formula' => $formula,
                'history' => $history
            ]
        );
    }

    /**
     * @param Request $request
     * @param Client  $formula
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function offerAction(Request $request, Client $client)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $formula = $em->getRepository('KGCChatBundle:ChatFormulaRate')->findOneByWebsiteRefAndType($client->getOrigin(), ChatFormulaRate::TYPE_FREE_OFFER, true);

        $form = $this->createForm(new ChatPaymentOfferType($chatType = $formula->getChatFormula()->getChatType()));
        $form->handleRequest($request);

        if ($chatType->getType() == ChatType::TYPE_QUESTION) {
            $title = 'Ajout de questions gratuites';
            $flashPrefix = 'Questions offertes';
        } else {
            $title = 'Ajout de minutes gratuites';
            $flashPrefix = 'Temps offert';
        }

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->get('kgc.chat.payment.manager')->processOfferPayment($form->getData(), $formula, $client);

                $this->addFlash('light#cog-light', $client->getMail().'--'.$flashPrefix.' affecté.');
            } else {
                $this->addFlash('error#cog', $client->getMail().'--'.$flashPrefix.' refusé.');
            }
        }

        return $this->render('KGCChatBundle:Formula:offer.html.twig',
            [
                'form' => $form->createView(),
                'client' => $client,
                'formula' => $formula,
                'modal_title' => $title
            ]
        );
    }

    /**
     * @param Request          $request
     * @param ChatSubscription $subscription
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function manualSubscriptionAction(Request $request, ChatSubscription $subscription)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $form = $this->createForm(new ChatManualSubscriptionType($subscription));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->get('kgc.chat.payment.manager')->processManualSubscription($form->getData(), $subscription);

                $this->addFlash('light#cog-light', $subscription->getClient()->getMail().'--Abonnement manuel effectué.');
            } else {
                $this->addFlash('error#cog', $subscription->getClient()->getMail().'--Abonnement manuel refusé.');
            }
        }

        return $this->render('KGCChatBundle:Formula:manualSubscription.html.twig',
            [
                'form' => $form->createView(),
                'subscription' => $subscription,
                'modal_title' => 'Abonnement manuel'
            ]
        );
    }

    /**
     * @param Request       $request
     * @param ChatPromotion $subscription
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function editPromoAction(Request $request, ChatPromotion $promotion)
    {
        $chatTypesByWebsite = $this->getRepository('KGCChatBundle:ChatFormula')->getChatTypesByWebsite();

        $em = $this->getDoctrine()->getEntityManager();

        $form = $this->createForm(new ChatPromotionType($chatTypesByWebsite, $em), $promotion);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($promotion);
                $em->flush();

                $this->addFlash('light#cog-light', 'Promotion #'.$promotion->getId().'--Enregistrée.');
            } else {
                $this->addFlash('error#cog', 'Promotion'.($promotion->getId() ? ' #'.$promotion->getId() : '').'--Non enregistrée.');
            }
        }

        return $this->render('KGCChatBundle:Formula:promo.edit.html.twig',
            [
                'form' => $form->createView(),
                'isNew' => $isNew = $promotion->getId() <= 0,
                'url' => $isNew ? 'kgc_chat_formulas_promo_new' : 'kgc_chat_formulas_promo_edit',
                'url_params' => $isNew ? [] : ['id' => $promotion->getId()]
            ]
        );
    }

    /**
     * @param Request          $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_CHAT")
     */
    public function newPromoAction(Request $request)
    {
        return $this->editPromoAction($request, new ChatPromotion);
    }
}
