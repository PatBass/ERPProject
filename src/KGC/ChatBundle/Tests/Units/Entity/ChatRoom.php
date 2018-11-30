<?php

namespace KGC\ChatBundle\Tests\Units\Entity;

use KGC\ChatBundle\Entity\ChatRoom as BaseChatRoom;
use atoum\test;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ChatBundle\Entity\ChatParticipant as BaseChatParticipant;
use KGC\UserBundle\Entity\Utilisateur;

class testedClass extends BaseChatRoom
{
}

class ChatParticipant extends BaseChatParticipant
{
    protected static $incId = 0;

    public function __construct()
    {
        $this->id = ++self::$incId;

        parent::__construct();
    }
}

class ChatRoom extends test
{
    public function testGetPsychic()
    {
        $this
            ->given($chatRoom = new \mock\KGC\ChatBundle\Tests\Units\Entity\testedClass)
            ->and($client = new Client)
            ->and($psychic1 = new Utilisateur)
            ->and($psychic2 = new Utilisateur)
            ->and($chatRoom->addChatParticipant((new ChatParticipant)->setClient($client)))
            ->and($chatRoom->addChatParticipant((new ChatParticipant)->setPsychic($psychic1)))
            ->and($chatRoom->addChatParticipant((new ChatParticipant)->setPsychic($psychic2)))
            ->then
                ->variable($chatRoom->getClient()->getId())->isIdenticalTo($client->getId())
                ->variable($chatRoom->getPsychic()->getId())->isIdenticalTo($psychic2->getId());
    }
}
