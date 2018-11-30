<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\ChatBundle\Entity\ChatRoom;

class ChatRoomFormulaRateRepository extends EntityRepository
{
    /**
     * Get the current chat room formula rate joined with consumptions
     * Join chat formula rate and chat type to avoid supplemntary requests.
     *
     * @param ChatRoom $room The room concerned
     *
     * @return ChatRoomFormulaRate | null
     */
    public function getCurrentWithConsumptions(ChatRoom $room)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
                    ->select('crfr', 'crc')
                    ->from('KGCChatBundle:ChatRoomFormulaRate', 'crfr')
                    ->leftJoin('crfr.chatRoomConsumptions', 'crc')
                    ->where('crfr.chatRoom = :room')
                    ->andWhere('crfr.startDate <= :now')
                    ->andWhere('crfr.endDate IS NULL')
                    ->setParameter('room', $room)
                    ->setParameter('now', new \DateTime())
                    ->orderBy('crfr.startDate', 'DESC')
                    ;

        $result = $qb->getQuery()->getResult();
        return $result ? array_shift($result) : null;
    }
}
