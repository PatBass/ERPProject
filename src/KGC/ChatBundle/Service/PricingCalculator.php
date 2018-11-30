<?php

namespace KGC\ChatBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ChatBundle\Entity\ChatFormulaRate;
use KGC\ChatBundle\Entity\ChatPayment;
use KGC\ChatBundle\Entity\ChatType;

/**
 * @DI\Service("kgc.chat.calculator.pricing")
 */
class PricingCalculator
{
    protected $chatPaymentRepository;

    protected function transformUnitString($chatType, $unit)
    {
        return (int) gmdate('i', $unit);
    }

    protected function buildConsumedLeftString($chatType, $unit)
    {
        return ChatType::TYPE_MINUTE === $chatType
            ? gmdate('i \m\i\n\u\t\e\s s\s', $unit)
            : sprintf('%s Questions', $unit)
            ;
    }

    public function buildOfferString(ChatPayment $p, $short = false)
    {
        $cfr = $p->getChatFormulaRate();
        switch ($cfr->getType()) {
            case ChatFormulaRate::TYPE_DISCOVERY:
                return $short ? 'Découverte' : 'Offre découverte';
            case ChatFormulaRate::TYPE_SUBSCRIPTION:
                return 'Abonnement';
            case ChatFormulaRate::TYPE_STANDARD:
                return 'Offre standard';
            case ChatFormulaRate::TYPE_PREMIUM:
                return 'Offre Premium';
            case ChatFormulaRate::TYPE_FREE_OFFER:
                if ($cfr->getFlexible()) {
                    if ($promotion = $p->getPromotion()) {
                        return $short ? 'Promotion #'.$promotion->getId() : $promotion->getName().' (Promo #'.$promotion->getId().')';
                    } else if ($cfr->getChatFormula()->getChatType()->getType() == ChatType::TYPE_MINUTE) {
                        return 'Temps offert';
                    } else {
                        return 'Questions offertes';
                    }
                } else {
                    return 'Offre gratuite';
                }
        }
    }

    protected function buildUnitString($chatType, $unit)
    {
        return ChatType::TYPE_MINUTE === $chatType
            ? sprintf('%s Minutes', $this->transformUnitString($chatType, $unit))
            : sprintf('%s Questions', $unit);
    }

    protected function buildPriceString($unit)
    {
        return sprintf('%s €', number_format($unit, 2, ',', ' '));
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->chatPaymentRepository = $em->getRepository('KGCChatBundle:ChatPayment');
    }

    /**
     * @param $websiteId
     * @param $email
     * @param null $roomId
     *
     * @return array
     */
    public function buildPricings($websiteId, $email, $roomId = null)
    {
        $pricing = [];

        $payments = $this->chatPaymentRepository->findByClientAndWebsiteForStat($websiteId, $email);

        foreach ($payments as $p) {
            $chatFormulaRate = $p->getChatFormulaRate();

            $type = $chatFormulaRate->getType();
            $chatType = $chatFormulaRate->getChatFormula()->getChatType()->getType();
            $originalUnits = $p->getUnit();
            $units = $this->buildUnitString($chatType, $originalUnits);

            $totalConsumption = 0;
            $roomConsumption = 0;

            $item = [
                'type' => $type,
                'offer' => $this->buildOfferString($p),
                'units' => $units,
                'date' => $p->getDate(),
                'price' => $this->buildPriceString($p->getAmount()/100),
                'chat_type' => $chatType,
                'consumed' => 0,
            ];

            foreach ($p->getChatRoomConsumptions() as $consumption) {
                $totalConsumption += $consumption->getUnit();
                $chatRoomFormulaRate = $consumption->getChatRoomFormulaRate();

                // If we have a roomId we need to keep only consumption for THIS particular room
                if (null === $roomId || ($roomId && $chatRoomFormulaRate->getChatRoom()->getId() == $roomId)) {
                    $item['consumed'] += $consumption->getUnit();
                    if (null !== $roomId) {
                        $roomConsumption += $consumption->getUnit();
                    }
                }
            }

            $item['left'] = $this->buildConsumedLeftString($chatType, $originalUnits - $totalConsumption);
            $item['consumed'] = $this->buildConsumedLeftString($chatType, $item['consumed']);

            if (null === $roomId || $roomConsumption) {
                $pricing[] = $item;
            }
        }

        return $pricing;
    }
}
