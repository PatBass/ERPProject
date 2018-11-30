<?php

namespace KGC\ChatBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\ChatBundle\Entity\ChatRoom;
use KGC\ChatBundle\Service\UserManager;

class ChatRoomRepository extends EntityRepository
{
    const LAST_DONE_COUNT = 10;

    public function findAllLastByStatus($status)
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')->setParameter('status', $status)
            ->orderBy('r.startDate', 'DESC')
            ->setMaxResults(self::LAST_DONE_COUNT)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findAllByInterval(\Datetime $begin, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.dateCreated', 'ASC')
            ->andWhere('r.dateCreated >= :begin and r.dateCreated <= :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->andWhere('r.status NOT IN (:statuses)')->setParameter('statuses', [
                ChatRoom::STATUS_CLOSED,
                ChatRoom::STATUS_REFUSED,
            ]);

        return $qb->getQuery()->getResult();
    }

    public function findAllByPlanning(\Datetime $begin, \DateTime $end)
    {
        $liste = [];
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.dateCreated', 'ASC')
            ->andWhere('r.dateCreated >= :begin and r.dateCreated <= :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->andWhere('r.status NOT IN (:statuses)')->setParameter('statuses', [
                ChatRoom::STATUS_REFUSED,
            ]);
        foreach ($qb->getQuery()->getResult() as $chatRoom){
            $query = $this->getEntityManager()->getConnection()->executeQuery('
                        SELECT SUM(c2_.price) as sum
                        FROM chat_room c0_
                        INNER JOIN chat_room_formula_rate c1_ ON c0_.id = c1_.chat_room_id
                        INNER JOIN chat_formula_rate c2_ ON c1_.chat_formula_rate_id = c2_.id
                        WHERE c0_.id = '.$chatRoom->getId().'
                        ORDER BY c0_.date_created ASC')
            ;
            foreach ($query->fetchAll() as $row){
                if($row['sum']>0){
                    $liste[] = ['room' => $chatRoom, 'free'=> false];
                }else{
                    $liste[] = ['room' => $chatRoom, 'free'=> true];
                }
            }
        }
        return $liste;
    }

    public function findAllWebsiteByUserMail($mail)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('w')
            ->from('KGCSharedBundle:Website', 'w')
            ->join('KGCSharedBundle:Client', 'client', 'WITH', 'client.origin = w.reference')
            ->andWhere('client.email = :client_mail')->setParameter('client_mail', $mail)
            ->andWhere('w.reference IS NOT NULL')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findOneById($roomId)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r', 'participants')
            ->leftJoin('r.chatRoomFormulaRates', 'roomFormulaRates')->addSelect('roomFormulaRates')
            ->innerJoin('r.chatType', 'chatType')->addSelect('chatType')
            ->innerJoin('r.website', 'website')
            ->innerJoin('r.chatParticipants', 'participants')
            ->leftJoin('participants.client', 'client')
            ->leftJoin('r.chatMessages', 'messages')->addSelect('messages')
            ->andWhere('r.id = :room_id')->setParameter('room_id', $roomId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByWebsiteAndUserMail($websiteId, $mail)
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.chatRoomFormulaRates', 'roomFormulaRates')->addSelect('roomFormulaRates')
            ->innerJoin('r.chatType', 'chatType')->addSelect('chatType')
            ->innerJoin('r.website', 'website')
                ->andWhere('website.id = :website_id')->setParameter('website_id', $websiteId)
            ->innerJoin('r.chatParticipants', 'participants')
                ->addSelect('participants')
            ->innerJoin('r.chatParticipants', 'cpClient')
                ->innerJoin('cpClient.client', 'client')
                ->andWhere('client.email = :client_mail')->setParameter('client_mail', $mail)
                ->setParameter('client_mail', $mail)
            ->leftJoin('r.chatMessages', 'messages')->addSelect('messages')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Get active rooms
     * A room is considered active if there is at least one user in it.
     *
     * @param [OPTIONNAL] int $room_id If specified, return a boolean concerning this room_id only
     *
     * @return array || boolean
     */
    public function getActiveRooms($room_id = null)
    {
        $qb = $this->createQueryBuilder('r')
                   ->join('r.chatParticipants', 'cp', 'WITH', 'cp.leaveDate IS NULL')
                   ->where('r.startDate IS NOT NULL')
                   ->andWhere('r.leaveDate IS NULL');

        if (is_numeric($room_id)) {
            $qb->andWhere('r.id = :id')
               ->setParameter('id', $room_id)
               ->setMaxResults(1);

            $result = $qb->getQuery()->getOneOrNullResult();

            return $result !== null;
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    /**
     * Get all rooms where user is in it, with messages and chat participants all in one.
     *
     * @param string $user_type
     * @param mixed  $user
     *
     * @return array of rooms
     */
    public function findFullyRoomsByUser($user_type, $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(array('room', 'roomChatParticipant', 'message', 'messageChatParticipant'))
            ->from('KGCChatBundle:ChatRoom', 'room');
        if ($user_type == UserManager::TYPE_PSYCHIC) {
            $qb->join('room.chatParticipants', 'cpPsy', 'WITH', 'cpPsy.'.$user_type.' = :user AND cpPsy.leaveDate IS NULL');
        } else {
            $qb->join('room.chatParticipants', 'cpPsy', 'WITH', 'cpPsy.'.UserManager::TYPE_PSYCHIC.' IS NOT NULL AND cpPsy.leaveDate IS NULL');
            $qb->join('room.chatParticipants', 'cpCli', 'WITH', 'cpCli.'.$user_type.' = :user');
        }
        $qb
            ->join('room.chatParticipants', 'roomChatParticipant')
            ->setParameter('user', $user)
            ->leftJoin('room.chatMessages', 'message')
            ->leftJoin('message.chatParticipant', 'messageChatParticipant');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find previous chat room with messages having same client and same virtual psychic
     *
     * @param ChatRoom $room
     *
     * @return ChatRoom
     */
    public function findPreviousChatRoomWithMessages(ChatRoom $room)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(['room', 'message'])
            ->from('KGCChatBundle:ChatRoom', 'room')
            ->join('room.chatParticipants', 'cpCli', 'WITH', 'cpCli.client = :client')->setParameter('client', $room->getClient())
            ->join('room.chatParticipants', 'cpPsy', 'WITH', 'cpPsy.virtualPsychic = :virtualPsychic')->setParameter('virtualPsychic', $room->getVirtualPsychic())
            ->innerJoin('room.chatMessages', 'message')
            ->where('room < :room')->setParameter('room', $room)
            ->orderBy('room.id', 'DESC');

        $result = $qb->getQuery()->getResult();
        return $result ? array_shift($result) : null;
    }

    /**
     * Find conversations by website and client.
     *
     * @param Website $website
     * @param Client  $client
     *
     * @return array
     */
    public function findConversationsByWebsiteAndClient(Website $website, Client $client)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
                   ->select(array('room', 'roomChatParticipant', 'message', 'messageChatParticipant'))
                   ->from('KGCChatBundle:ChatRoom', 'room')
                   ->join('room.chatParticipants', 'cp', 'WITH', 'cp.client = :client')
                   ->join('room.chatParticipants', 'roomChatParticipant')
                   ->setParameter('client', $client)
                   ->leftJoin('room.chatMessages', 'message')
                   ->leftJoin('message.chatParticipant', 'messageChatParticipant')
                   ->where('room.website = :website')
                   ->setParameter('website', $website)
                   ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getCountForInterval(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->andWhere('r.startDate >= :begin AND r.startDate < :end')
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCountByTypeForInterval(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) as nb, type.type')
            ->innerJoin('r.chatType', 'type')

            ->andWhere('r.startDate >= :date_begin AND r.startDate < :date_end')
            ->setParameter('date_begin', $begin)
            ->setParameter('date_end', $end)
            ->addGroupBy('type.id')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param \Datetime $begin
     * @param \Datetime $end
     *
     * @return mixed
     */
    public function getCountByPsychicForInterval(\Datetime $begin, \Datetime $end)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('count(r) as nb, psychic.username as name')
            ->innerJoin('r.chatParticipants', 'participants')
            ->innerJoin('participants.psychic', 'psychic')

            ->andWhere('psychic.actif = 1')
            ->addGroupBy('psychic.id')
            ->addOrderBy('psychic.username', 'ASC')

            ->andWhere('r.startDate >= :date_begin AND r.startDate < :date_end')
            ->setParameter('date_begin', $begin)
            ->setParameter('date_end', $end)
        ;

        return $qb->getQuery()->getResult();
    }
}
