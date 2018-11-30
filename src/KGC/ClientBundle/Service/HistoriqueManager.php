<?php

namespace KGC\ClientBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\ClientBundle\Entity\Historique;
use KGC\ClientBundle\Entity\Option;
use KGC\ClientBundle\Form\HistoriqueBoolType;
use KGC\ClientBundle\Form\HistoriqueDatetimeType;
use KGC\ClientBundle\Form\HistoriqueDrawType;
use KGC\ClientBundle\Form\HistoriqueInputType;
use KGC\ClientBundle\Form\HistoriqueOptionsType;
use KGC\ClientBundle\Form\HistoriqueOptionType;
use KGC\ClientBundle\Form\HistoriquePendulumType;
use KGC\ClientBundle\Form\HistoriqueTextType;
use KGC\UserBundle\Entity\Utilisateur;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Form\FormInterface;

/**
 * @DI\Service("kgc.client.historique.manager")
 */
class HistoriqueManager
{
    const HISTORY_SECTION_NOTES = 'section_notes';
    const HISTORY_SECTION_HISTORY = 'section_history';
    const HISTORY_SECTION_PENDULUM = 'section_pendulum';
    const HISTORY_SECTION_COM = 'section_com';
    const HISTORY_SECTION_ALERT = 'section_alert';
    const HISTORY_SECTION_QUALITY = 'section_quality';
    const HISTORY_SECTION_DRAW = 'section_draw';

    const FORM_CAT_QUALITY = 'qualite';

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @param ObjectManager $entityManager
     *
     * @DI\InjectParams({
     *     "entityManager"  = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function getTypeLabelMapping($type)
    {
        $labels = [
            Historique::TYPE_PROFILE => 'Profil',
            Historique::TYPE_BEHAVIOR => 'Comportement',
            Historique::TYPE_SITUATION => 'Situation sentimentale',
            Historique::TYPE_HUSBAND_FIRSTNAME => 'Prénom conjoint',
            Historique::TYPE_OTHER_FIRSTNAME => 'Prénom tierce personne',
            Historique::TYPE_PRO_SITUATION => 'Situation profesionnelle',
            Historique::TYPE_JOB => 'Profession',
            Historique::TYPE_PROBLEMS => 'Problèmes constatés',
            Historique::TYPE_OBJECTIVE => 'Objectifs à atteindre',
            Historique::TYPE_MEANS => 'Moyens pour y parvenir',
            Historique::TYPE_SENDING => 'Envois',
            Historique::TYPE_PRODUCT => 'Produits',
            Historique::TYPE_PLAN => 'Forfaits',
            Historique::TYPE_FREE_NOTES => 'Notes sur les propositions faites',
            Historique::TYPE_REMINDER => 'Rappel/Suivi client',
            Historique::TYPE_RECAP => 'Bilan',
            Historique::TYPE_NOTES => 'Notes',
            Historique::TYPE_OPINION => 'Avis',
            Historique::TYPE_REMINDER_STATE => 'État du suivi',
            Historique::TYPE_RECURRENT => 'Client récurrent ?',
            Historique::TYPE_STOP_FOLLOW => 'NVP Suivi ?',
        ];

        return array_key_exists($type, $labels) ? $labels[$type] : '';
    }

    public function getFormFields()
    {
        return [
            ['name' => Historique::TYPE_PROFILE, 'form' => new HistoriqueOptionType(Option::TYPE_PROFILE)],
            ['name' => Historique::TYPE_BEHAVIOR, 'form' => new HistoriqueOptionType(Option::TYPE_BEHAVIOR)],
            ['name' => Historique::TYPE_SITUATION, 'form' => new HistoriqueOptionType(Option::TYPE_SITUATION)],
            ['name' => Historique::TYPE_HUSBAND_FIRSTNAME, 'form' => new HistoriqueInputType()],
            ['name' => Historique::TYPE_OTHER_FIRSTNAME, 'form' => new HistoriqueInputType()],
            ['name' => Historique::TYPE_PRO_SITUATION, 'form' => new HistoriqueOptionType(Option::TYPE_PRO_SITUATION)],
            ['name' => Historique::TYPE_JOB, 'form' => new HistoriqueInputType()],
            ['name' => Historique::TYPE_PROBLEMS, 'form' => new HistoriqueTextType()],
            ['name' => Historique::TYPE_OBJECTIVE, 'form' => new HistoriqueTextType()],
            ['name' => Historique::TYPE_MEANS, 'form' => new HistoriqueTextType()],
            ['name' => Historique::TYPE_SENDING, 'form' => new HistoriqueOptionsType(Option::TYPE_SENDING)],
            ['name' => Historique::TYPE_PRODUCT, 'form' => new HistoriqueOptionsType(Option::TYPE_PRODUCT)],
            ['name' => Historique::TYPE_PLAN, 'form' => new HistoriqueOptionType(Option::TYPE_PLAN)],
            ['name' => Historique::TYPE_FREE_NOTES, 'form' => new HistoriqueTextType()],
            ['name' => Historique::TYPE_PENDULUM, 'form' => new HistoriquePendulumType(Option::TYPE_PENDULUM)],
            ['name' => Historique::TYPE_REMINDER, 'form' => new HistoriqueDatetimeType()],
            ['name' => Historique::TYPE_RECAP, 'form' => new HistoriqueBoolType()],
            ['name' => Historique::TYPE_NOTES, 'form' => new HistoriqueBoolType()],
            ['name' => Historique::TYPE_OPINION, 'form' => new HistoriqueOptionType(Option::TYPE_OPINION)],
            ['name' => Historique::TYPE_REMINDER_STATE, 'form' => new HistoriqueOptionType(Option::TYPE_REMINDER_STATE)],
            ['name' => Historique::TYPE_RECURRENT, 'form' => new HistoriqueBoolType()],
            ['name' => Historique::TYPE_STOP_FOLLOW, 'form' => new HistoriqueBoolType()],
            ['name' => Historique::TYPE_DRAW, 'form' => new HistoriqueDrawType()],
        ];
    }

    public function getFormConfigs()
    {
        return [
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
        ];
    }

    /**
     * Remove History from Client and from entity manager.
     *
     * @param Client     $client
     * @param Historique $h
     */
    protected function removeHistory(Client $client, Historique $h)
    {
        $client->removeHistorique($h);
        $this->entityManager->remove($h);
    }

    /**
     * @param Client     $client
     * @param Historique $h
     * @param $c
     */
    protected function dealWithRemoval(Client $client, Historique $h, $c)
    {
        $relations = [
            Historique::BACKEND_TYPE_OPTIONS,
            Historique::BACKEND_TYPE_PENDULUM,
        ];
        $removed = false;

        foreach ($relations as $r) {
            $method = 'get'.ucfirst($r);
            if ($r === $c['backendType']) {
                if (0 === count($h->$method())) {
                    $this->removeHistory($client, $h);
                    $removed = true;
                }
            }
        }

        return $removed;
    }

    /**
     * @param FormInterface $form
     * @param Utilisateur   $user
     */
    public function createHistoryFromRdv(FormInterface $form, Utilisateur $user)
    {
        $client = $form->get('client')->getData();
        $rdv = $form->getData();
        $subforms = [$form['notes'], $form['qualite']];

        $toflush = array();

        foreach ($this->getFormConfigs() as $c) {
            foreach ($subforms as $form) {
                if ($form->has($c['name'])) {
                    if (!$form->get($c['name'])->isDisabled()) {
                        $h = $form->get($c['name'])->getData();
                        $removed = false;
                        // We get the value based on the backend type
                        $getMethod = sprintf('get%s', ucfirst($c['backendType']));
                        $value = $h->$getMethod();
                        if (null !== $value) {
                            $h->setType($c['name']);
                            $h->setBackendType($c['backendType']);
                            $h->setClient($client);
                            $h->setRdv($rdv);
                            $h->setConsultant($user);

                            // We set the value based on the backend type
                            $setMethod = sprintf('set%s', ucfirst($c['backendType']));
                            $h->$setMethod($value);

                            if (Historique::BACKEND_TYPE_PENDULUM === $c['backendType']) {
                                foreach ($value as $pendulum) {
                                    if (null === $pendulum->getQuestion() && null === $pendulum->getCustomQuestion()) {
                                        $h->removePendulum($pendulum);
                                        $this->entityManager->remove($pendulum);
                                    }
                                }
                            }
                            if (Historique::BACKEND_TYPE_DRAW === $c['backendType']) {
                                foreach ($value as $draw) {
                                    if (null === $draw->getDeck() || null === $draw->getCard()) {
                                        $h->removeDraw($draw);
                                        $this->entityManager->remove($draw);
                                    }
                                }
                            }
                            $removed = $removed || $this->dealWithRemoval($client, $h, $c);
                        } else {
                            $this->removeHistory($client, $h);
                            $removed = true;
                        }
                        $removed = $removed || $this->dealWithRemoval($client, $h, $c);

                        if (!$removed) {
                            $this->entityManager->persist($h);
                            $toflush[] = $h;
                        }
                    }
                }
            }
        }
//        $this->entityManager->flush($toflush);
    }

    /**
     * @param FormInterface $form
     */
    public function getHistoryFromRdv(FormInterface $form)
    {
        $repo = $this->entityManager->getRepository('KGCClientBundle:Historique');
        $client = $form->get('client')->getData();
        $rdv = $form->getData();
        $subforms = ['notes', 'qualite'];

        foreach ($this->getFormConfigs() as $c) {
            $historique = $repo->findByRdvAndClientAndType($rdv->getId(), $client->getId(), $c['name']);
            if (null !== $historique) {
                foreach ($subforms as $subform) {
                    if ($form[$subform]->has($c['name'])) {
                        $form[$subform]->get($c['name'])->setData($historique);
                    }
                }
            }
        }
    }

    /**
     * @param array $history
     *
     * @return array
     */
    public function formatHistoriqueByRdv(array $history)
    {
        $formatted = [];
        $previousRdv = null;
        $currentRdv = null;
        $i = 0;
        foreach ($history as $h) {
            $currentRdv = $h->getRdv()->getId();
            if ($currentRdv !== $previousRdv) {
                ++$i;
                $formatted[$i] = [
                    'consultant' => $h->getConsultant(),
                    'client' => $h->getClient(),
                    'date' => $h->getRdv()->getDateConsultation(),
                ];
            }
            $getMethod = sprintf('get%s', ucfirst($h->getBackendType()));
            $formatted[$i] = array_merge($formatted[$i], [
                $h->getType() => ['backendType' => $h->getBackendType(), 'value' => $h->$getMethod(), 'date' => $h->getUpdatedAt()],
            ]);

            $previousRdv = $currentRdv;
        }

        return $formatted;
    }

    /**
     * Return an array with history fields for each history sections.
     *
     * @return array
     */
    public function getHistorySectionsMapping()
    {
        return [
            self::HISTORY_SECTION_NOTES => [
                Historique::TYPE_PROFILE,
                Historique::TYPE_BEHAVIOR,
                Historique::TYPE_SITUATION,
                Historique::TYPE_HUSBAND_FIRSTNAME,
                Historique::TYPE_OTHER_FIRSTNAME,
                Historique::TYPE_PRO_SITUATION,
                Historique::TYPE_JOB,
            ],
            self::HISTORY_SECTION_HISTORY => [
                Historique::TYPE_PROBLEMS,
                Historique::TYPE_OBJECTIVE,
                Historique::TYPE_MEANS,
            ],
            self::HISTORY_SECTION_PENDULUM => [
                Historique::TYPE_PENDULUM,
            ],
            self::HISTORY_SECTION_COM => [
                Historique::TYPE_SENDING,
                Historique::TYPE_PRODUCT,
                Historique::TYPE_PLAN,
                Historique::TYPE_FREE_NOTES,
            ],
            self::HISTORY_SECTION_ALERT => [
                Historique::TYPE_REMINDER,
                Historique::TYPE_RECURRENT,
                Historique::TYPE_STOP_FOLLOW,
            ],
            self::HISTORY_SECTION_QUALITY => [
                Historique::TYPE_RECAP,
                Historique::TYPE_NOTES,
                Historique::TYPE_OPINION,
                Historique::TYPE_REMINDER_STATE,
            ],
            self::HISTORY_SECTION_DRAW => [
                Historique::TYPE_DRAW,
            ],
        ];
    }

    /**
     * Return an array with history fields for $section parameter.
     *
     * @param $section
     *
     * @return mixed
     */
    public function getHistoryFieldsBySection($section)
    {
        $mapping = $this->getHistorySectionsMapping();

        return !empty($mapping[$section]) ? $mapping[$section] : [];
    }

    public function getFormConfigsBySection($sections)
    {
        if (!is_array($sections)) {
            $sections = [$sections];
        }

        $wanted_fields = array();
        foreach ($sections as $section) {
            $section_mapping = $this->getHistorySectionsMapping();
            $wanted_fields = array_merge($wanted_fields, $section_mapping[$section]);
        }

        $form_config = $this->getFormConfigs();
        $wanted_form_config = array_map(function ($value) use ($form_config) {
            foreach ($form_config as $f) {
                if ($f['name'] === $value) {
                    return $f;
                }
            }
        }, $wanted_fields);

        return $wanted_form_config;
    }

    public function getFormFieldsBySection($sections = array())
    {
        if (!is_array($sections)) {
            $sections = [$sections];
        }

        $wanted_fields = array();
        foreach ($sections as $section) {
            $section_mapping = $this->getHistorySectionsMapping();
            $wanted_fields = array_merge($wanted_fields, $section_mapping[$section]);
        }

        $form_fields = $this->getFormFields();
        $wanted_form_fields = array_map(function ($value) use ($form_fields) {
            foreach ($form_fields as $f) {
                if ($f['name'] === $value) {
                    return $f;
                }
            }
        }, $wanted_fields);

        return $wanted_form_fields;
    }
}
