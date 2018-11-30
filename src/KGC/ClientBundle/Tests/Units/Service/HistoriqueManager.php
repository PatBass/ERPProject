<?php

namespace KGC\ClientBundle\Tests\Units\Service;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\ClientBundle\Service\HistoriqueManager as testedClass;
use KGC\RdvBundle\Entity\RDV;
use KGC\UserBundle\Entity\Utilisateur;
use atoum\test;

class HistoriqueManager extends test
{
    protected function createObject()
    {
        $objectManager = new \mock\Doctrine\Common\Persistence\ObjectManager();

        return new testedClass($objectManager);
    }

    protected function _extractTypesFromConfig(array $formFields)
    {
        $types = [];
        foreach ($formFields as $ff) {
            $types[] = $ff['name'];
        }

        $types[] = Historique::TYPE_MAIL;

        return $types;
    }

    protected function buildFakeHistory()
    {
        $u = new Utilisateur();
        $u->setId(999);
        $u->setUsername('unit-test');

        $c = new Client();
        $c->setNom('Unit');
        $c->setPrenom('Test');

        $rdv1 = new RDV($u);
        $rdv1->setId(888);
        $rdv1->setDateConsultation(new \DateTime());

        $rdv2 = new RDV($u);
        $rdv2->setId(889);
        $rdv2->setDateConsultation(new \DateTime());

        $rdv3 = new RDV($u);
        $rdv3->setId(890);
        $rdv3->setDateConsultation(new \DateTime());

        $h1 = new Historique();
        $h1->setConsultant($u);
        $h1->setClient($c);
        $h1->setRdv($rdv1);
        $h1->setType(Historique::TYPE_PRO_SITUATION);
        $h1->setBackendType(Historique::BACKEND_TYPE_OPTION);
        $h1->setOption(
            new Option(Option::TYPE_PRO_SITUATION, Option::PRO_SITUATION_HIRED)
        );

        $h2 = new Historique();
        $h2->setConsultant($u);
        $h2->setClient($c);
        $h2->setRdv($rdv1);
        $h2->setType(Historique::TYPE_FREE_NOTES);
        $h2->setBackendType(Historique::BACKEND_TYPE_TEXT);
        $h2->setText('
Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet,
Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet,
Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet,
Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet,
');

        $h3 = new Historique();
        $h3->setConsultant($u);
        $h3->setClient($c);
        $h3->setRdv($rdv2);
        $h3->setType(Historique::TYPE_PROBLEMS);
        $h3->setBackendType(Historique::BACKEND_TYPE_TEXT);
        $h3->setText('
Salut tout le monde les gens, comment allez-vous ?
Salut tout le monde les gens, comment allez-vous ?
Salut tout le monde les gens, comment allez-vous ?
Salut tout le monde les gens, comment allez-vous ?
Salut tout le monde les gens, comment allez-vous ?
');

        return [$h1, $h2, $h3];
    }

    public function testInstance()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->then
                ->object($historiqueManager)->isInstanceOf('KGC\ClientBundle\Service\HistoriqueManager')
        ;
    }

    public function testGetTypeLabelMapping()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->then
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_PROFILE))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_BEHAVIOR))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_SITUATION))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_HUSBAND_FIRSTNAME))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_OTHER_FIRSTNAME))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_PRO_SITUATION))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_JOB))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_PROBLEMS))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_OBJECTIVE))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_MEANS))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_SENDING))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_PRODUCT))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_PLAN))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping(Historique::TYPE_FREE_NOTES))->isNotEmpty()
                ->string($historiqueManager->getTypeLabelMapping('fake'))->isEmpty()

        ;
    }

    public function testGetFormFields()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->and($formFields = $historiqueManager->getFormFields())
            ->then
                ->array($formFields)->size->isEqualTo(23)
                ->array($this->_extractTypesFromConfig($formFields))
                    ->containsValues(Historique::buildByPrefixes('TYPE'))

        ;
    }

    public function testGetFormConfigs()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->and($formConfig = $historiqueManager->getFormConfigs())
            ->then
                ->array($formConfig)->isIdenticalTo([
                    ['name' => Historique::TYPE_PROFILE, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_BEHAVIOR, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_SITUATION, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_HUSBAND_FIRSTNAME, 'backendType' => Historique::BACKEND_TYPE_STRING],
                    ['name' => Historique::TYPE_OTHER_FIRSTNAME, 'backendType' => Historique::BACKEND_TYPE_STRING],
                    ['name' => Historique::TYPE_PRO_SITUATION, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_JOB, 'backendType' => Historique::BACKEND_TYPE_STRING],

                    ['name' => Historique::TYPE_PROBLEMS, 'backendType' => Historique::BACKEND_TYPE_TEXT],
                    ['name' => Historique::TYPE_OBJECTIVE, 'backendType' => Historique::BACKEND_TYPE_TEXT],
                    ['name' => Historique::TYPE_MEANS, 'backendType' => Historique::BACKEND_TYPE_TEXT],

                    ['name' => Historique::TYPE_SENDING, 'backendType' => Historique::BACKEND_TYPE_OPTIONS],
                    ['name' => Historique::TYPE_PRODUCT, 'backendType' => Historique::BACKEND_TYPE_OPTIONS],
                    ['name' => Historique::TYPE_PLAN, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_FREE_NOTES, 'backendType' => Historique::BACKEND_TYPE_TEXT],

                    ['name' => Historique::TYPE_PENDULUM, 'backendType' => Historique::BACKEND_TYPE_PENDULUM],

                    ['name' => Historique::TYPE_REMINDER, 'backendType' => Historique::BACKEND_TYPE_DATETIME],
                    ['name' => Historique::TYPE_RECURRENT, 'backendType' => Historique::BACKEND_TYPE_BOOL],
                    ['name' => Historique::TYPE_STOP_FOLLOW, 'backendType' => Historique::BACKEND_TYPE_BOOL],

                    ['name' => Historique::TYPE_RECAP, 'backendType' => Historique::BACKEND_TYPE_BOOL],
                    ['name' => Historique::TYPE_NOTES, 'backendType' => Historique::BACKEND_TYPE_BOOL],
                    ['name' => Historique::TYPE_OPINION, 'backendType' => Historique::BACKEND_TYPE_OPTION],
                    ['name' => Historique::TYPE_REMINDER_STATE, 'backendType' => Historique::BACKEND_TYPE_OPTION],

                    ['name' => Historique::TYPE_DRAW, 'backendType' => Historique::BACKEND_TYPE_DRAW],
                ])

        ;
    }

    public function testGetHistoryFieldsBySection()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->and($result = $historiqueManager->getHistoryFieldsBySection(testedClass::HISTORY_SECTION_NOTES))
            ->then
                ->array($result)->isIdenticalTo([
                    Historique::TYPE_PROFILE,
                    Historique::TYPE_BEHAVIOR,
                    Historique::TYPE_SITUATION,
                    Historique::TYPE_HUSBAND_FIRSTNAME,
                    Historique::TYPE_OTHER_FIRSTNAME,
                    Historique::TYPE_PRO_SITUATION,
                    Historique::TYPE_JOB,
                ])

            ->and($result = $historiqueManager->getHistoryFieldsBySection(testedClass::HISTORY_SECTION_HISTORY))
            ->then
                ->array($result)->isIdenticalTo([
                    Historique::TYPE_PROBLEMS,
                    Historique::TYPE_OBJECTIVE,
                    Historique::TYPE_MEANS,
                ])

            ->and($result = $historiqueManager->getHistoryFieldsBySection(testedClass::HISTORY_SECTION_PENDULUM))
            ->then
                ->array($result)->isIdenticalTo([
                    Historique::TYPE_PENDULUM,
                ])

            ->and($result = $historiqueManager->getHistoryFieldsBySection(testedClass::HISTORY_SECTION_COM))
            ->then
                ->array($result)->isIdenticalTo([
                    Historique::TYPE_SENDING,
                    Historique::TYPE_PRODUCT,
                    Historique::TYPE_PLAN,
                    Historique::TYPE_FREE_NOTES,
                ])

            ->and($result = $historiqueManager->getHistoryFieldsBySection(testedClass::HISTORY_SECTION_ALERT))
            ->then
                ->array($result)->isIdenticalTo([
                    Historique::TYPE_REMINDER,
                    Historique::TYPE_RECURRENT,
                    Historique::TYPE_STOP_FOLLOW,
                ])

            ->and($result = $historiqueManager->getHistoryFieldsBySection('fake_section'))
            ->then
                ->array($result)->isIdenticalTo([])
        ;
    }

    public function testFormatHistoriqueByRdv()
    {
        $this
            ->given($historiqueManager = $this->createObject())
            ->and($history = $this->buildFakeHistory())
            ->and($history = $historiqueManager->formatHistoriqueByRdv($history))

            // Empty
            ->then
                ->array($historiqueManager->formatHistoriqueByRdv([]))->isIdenticalTo([])

            // Structure
            ->then
                ->array($history)->size->isEqualTo(2)
                ->array(array_keys($history[1]))->isIdenticalTo([
                    'consultant',
                    'client',
                    'date',
                    'pro_situation',
                    'free_notes',
                ])
                ->array(array_keys($history[2]))->isIdenticalTo([
                    'consultant',
                    'client',
                    'date',
                    'problems',
                ])
                ->object($history[1]['consultant'])->isIdenticalTo($history[2]['consultant'])
                ->object($history[1]['client'])->isIdenticalTo($history[2]['client'])
                ->object($history[1]['date'])->isNotIdenticalTo($history[2]['date'])

            // Content
            ->then
                ->object($history[1]['consultant'])->isInstanceOf('KGC\UserBundle\Entity\Utilisateur')
                ->object($history[1]['client'])->isInstanceOf('KGC\Bundle\SharedBundle\Entity\Client')
                ->object($history[1]['date'])->isInstanceOf('\Datetime')
                ->array(array_keys($history[1]['pro_situation']))->isIdenticalTo([
                    'backendType',
                    'value',
                    'date',
                ])
                ->string($history[1]['pro_situation']['backendType'])->isIdenticalTo('option')
                ->object($history[1]['pro_situation']['value'])->isInstanceOf('KGC\ClientBundle\Entity\Option')
                ->string($history[1]['pro_situation']['value']->getCode())->isIdenticalTo('hired')
                ->string($history[1]['pro_situation']['backendType'])->isIdenticalTo('option')
                ->object($history[1]['pro_situation']['value'])->isInstanceOf('KGC\ClientBundle\Entity\Option')
                ->string($history[1]['pro_situation']['value']->getCode())->isIdenticalTo('hired')

        ;
    }
}
