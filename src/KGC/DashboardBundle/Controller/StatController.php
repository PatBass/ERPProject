<?php
// src/KGC/DashboardBundle/Controller/StatController.php

namespace KGC\DashboardBundle\Controller;

use Doctrine\ORM\EntityRepository;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\CommonBundle\Form\DatePeriodType;
use KGC\StatBundle\Calculator\Calculator;
use KGC\StatBundle\Calculator\ChatCalculator;
use KGC\StatBundle\Entity\StatisticRenderingRule;
use KGC\StatBundle\Form\AboColumnType;
use KGC\StatBundle\Form\SortingColumnType;
use KGC\StatBundle\Form\StatDateType;
use KGC\StatBundle\Form\StatisticColumnType;
use KGC\UserBundle\Entity\Utilisateur;
use KGC\UserBundle\Entity\Profil;
use KGC\StatBundle\Form\WebsiteType;
use KGC\StatBundle\Form\SupportType;
use KGC\StatBundle\Form\SourceType;
use KGC\StatBundle\Form\UrlType;
use KGC\StatBundle\Form\CodePromoType;
use KGC\StatBundle\Form\StatScopeType;
use KGC\StatBundle\Repository\StatRepository;
use KGC\UserBundle\Entity\Voyant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * StatController.
 *
 * @category Controller
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class StatController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function generalAction(Request $request)
    {
        $s = $request->getSession();

        $periodbase_begin = $s->get('admin_general_periodbase_begin');
        $periodbase_begin = $periodbase_begin === null ? new \DateTime('first day of this month') : $periodbase_begin;
        $periodbase_end = $s->get('admin_general_periodbase_end');
        $periodbase_end = $periodbase_end === null ? new \DateTime('last day of this month') : $periodbase_end;

        $form = $this->createFormBuilder()
            ->add('period_base', new DatePeriodType(), [
                'required' => true,
                'data' => [
                    'begin' => $periodbase_begin,
                    'end' => $periodbase_end,
                ],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $periodbase_begin = $form['period_base']['begin']->getData();
            $periodbase_end = $form['period_base']['end']->getData();
        }

        $s->set('admin_general_periodbase_begin', $periodbase_begin);
        $s->set('admin_general_periodbase_end', $periodbase_end);

        return $this->render('KGCDashboardBundle:Admin:general.stats.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE, ROLE_AFFILIATE")
     */
    public function specificAction(Request $request)
    {
        $s = $request->getSession();
        $tabs = $s->get('admin_specific_tabs') ?: array();
        $last_config = $s->get('admin_specific_config') ?: [
            'begin' => new \DateTime('first day of this month'),
            'end' => new \DateTime('last day of this month'),
            'statScope' => StatScopeType::KEY_ALL,
            'dateType' => StatDateType::KEY_DATE_TYPE_CONSULTATION,
            'sorting_column' => StatisticColumnType::CODE_RDV_TOTAL,
            'sorting_dir' => SortingColumnType::KEY_DESC,
            'websites' => array(), 'sources' => array(),
            'urls' => array(), 'codesPromo' => array(),
            'phonists' => array(),
            'proprios' => array(),
            'reflex_affiliates' => array(),
            'reflex_sources' => array(),
            'consultants' => array(),
            'supports' => array(),
            'ca' => StatisticColumnType::$CA_CHOICES,
            'rdv' => StatisticColumnType::$RDV_LIGHTED_CHOICES
        ];

        $websites = $this->getDoctrine()->getRepository('KGCSharedBundle:Website')->findAll(true);
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        if($role == 'affiliate') {
            $sources = $this->getDoctrine()->getRepository('KGCRdvBundle:Source')->findBy(['affiliateAllowed' => 1]);
        } else {
            $sources = $this->getDoctrine()->getRepository('KGCRdvBundle:Source')->findAll(true);
        }
        $urls = $this->getDoctrine()->getRepository('KGCRdvBundle:FormUrl')->findAll();
        $codesPromo = $this->getDoctrine()->getRepository('KGCRdvBundle:CodePromo')->findAll(true);
        $supports = $this->getDoctrine()->getRepository('KGCRdvBundle:Support')->findAll(true);

        $consultants = array();
        $phonists = array();
        $er = $this->getDoctrine()->getRepository('KGCUserBundle:Utilisateur');
        $landing = $this->getDoctrine()->getRepository('KGCSharedBundle:LandingUser');

        $consultants['all'] = 'Tous';
        $consultants['NULL'] = 'Aucun';
        foreach($er->findAllByMainProfil(Profil::VOYANT) as $c) {
            $consultants[$c->getId()] = $c->getUserName();
        }

        $phonists['all'] = 'Tous';
        foreach($er->findAllByMainProfil(Profil::PHONISTE) as $c) {
            $phonists[$c->getId()] = $c->getUserName();
        }
        $proprios['all'] = 'Tous';
        foreach($er->findByActif(1) as $c) {
            $proprios[$c->getId()] = $c->getUserName();
        }
        $reflex_affiliates['all'] = 'Tous';
        foreach($landing->getReflexAffiliate() as $aAffiliate) {
            $affiliate = $aAffiliate['reflexAffilateId'];
            $reflex_affiliates[$affiliate] = $affiliate;
        }
        $reflex_sources['all'] = 'Tous';
        foreach($landing->getReflexSource() as $aSource) {
            $source = $aSource['reflexSource'];
            $reflex_sources[$source] = $source;
        }

        $form = $this->createFormBuilder()
            ->add('period_base', new DatePeriodType(), [
                'required' => true,
                'data' => [
                    'begin' => $last_config['begin'],
                    'end' => $last_config['end'],
                ],
            ])
            ->add('statScope', new StatScopeType(), [
                'data' => [
                    'selected' => $last_config['statScope']
                ]
            ])
            ->add('dateType', new StatDateType(), [
                'data' => [
                    'selected' => isset($last_config['dateType']) ? $last_config['dateType'] : StatDateType::KEY_DATE_TYPE_CONSULTATION
                ]
            ])
            ->add('sorting_column', new SortingColumnType(), [
                'data' => [
                    'sorting_column' => $last_config['sorting_column'],
                    'sorting_dir' => $last_config['sorting_dir']
                ]
            ])
            ->add('websites', new WebsiteType($websites), [
                'data' => $last_config['websites']
            ])
            ->add('sources', new SourceType($sources), [
                'data' => $last_config['sources']
            ])
            ->add('urls', new UrlType($urls), [
                'data' => $last_config['urls']
            ])
            ->add('supports', new SupportType($supports), [
                'data' => $last_config['supports']
            ])
            ->add('codesPromo', new CodePromoType($codesPromo), [
                'data' => $last_config['codesPromo']
            ])
            ->add('phonists', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $phonists,
                'data' => $last_config['phonists'],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('proprios', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $proprios,
                'data' => isset($last_config['proprios']) ? $last_config['proprios'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('reflex_affiliates', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $reflex_affiliates,
                'data' => isset($last_config['reflex_affiliates']) ? $last_config['reflex_affiliates'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('reflex_sources', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $reflex_sources,
                'data' => isset($last_config['reflex_sources']) ? $last_config['reflex_sources'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('consultants', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $consultants,
                'data' => $last_config['consultants'],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('columns', new StatisticColumnType(), [
                'data' => [
                    'selected_ca' => $last_config['ca'],
                    'selected_rdv' => $last_config['rdv'],
                    'separated' => true,
                    'multiple' => true,
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $tab_config = [
                'begin' =>  $form['period_base']['begin']->getData(),
                'end' => $form['period_base']['end']->getData(),
                'statScope' => $form['statScope']['statScope']->getData(),
                'dateType' => $form['dateType']['dateType']->getData(),
                'websites' => $form['websites']['websites']->getData(),
                'sources' => $form['sources']['sources']->getData(),
                'urls' => $form['urls']['urls']->getData(),
                'codesPromo' => $form['codesPromo']['codespromo']->getData(),
                'supports' => $form['supports']['supports']->getData(),
                'phonists' => $form['phonists']->getData(),
                'proprios' => $form['proprios']->getData(),
                'reflex_affiliates' => $form['reflex_affiliates']->getData(),
                'reflex_sources' => $form['reflex_sources']->getData(),
                'consultants' => $form['consultants']->getData(),
                'rdv' => $form['columns']['rdv']->getData(),
                'ca' => $form['columns']['ca']->getData(),
                'sorting_dir' => $form['sorting_column']['dir']->getData(),
                'sorting_column' => $form['sorting_column']['column']['column']->getData()
            ];
            array_unshift($tabs, $tab_config);
            $s->set('admin_specific_config', $tab_config);
        }

        $s->set('admin_specific_tabs', $tabs);


        return $this->render('KGCDashboardBundle:Admin:specific.stats.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicAction()
    {
        $request = $this->get('request');
        $s = $request->getSession();
        $psychic_mode = false;


        if ($this->getUser()->isVoyant()) {
            $psychic_mode = !$this->getUser()->isManagerVoyant();
            $consultant = $this->getUser();
        } else {
            $previousConsultant = $s->get('admin_psychicstats_consultant');
            $consultant = $previousConsultant instanceof Utilisateur ? $this->getDoctrine()->getEntityManager()->merge($previousConsultant) : null;
        }

        $periodbase_begin = $s->get('admin_psychicstats_periodbase_begin');
        $periodbase_begin = $periodbase_begin === null ? new \DateTime('first day of this month') : $periodbase_begin;
        $periodbase_end = $s->get('admin_psychicstats_periodbase_end');
        $periodbase_end = $periodbase_end === null ? new \DateTime('last day of this month') : $periodbase_end;

        $formbuilder = $this->createFormBuilder()
            ->add('period_base', new DatePeriodType(), [
                'required' => true,
               'data' => [
                   'begin' => $periodbase_begin,
                   'end' => $periodbase_end,
               ],
            ])
            ->add('period_compare', new DatePeriodType());
        if (!$psychic_mode) {
            $formbuilder->add('consultant', 'entity', array(
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'empty_value' => 'Tous',
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByMainProfilQB(Profil::VOYANT);
                },
                'attr' => array(
                    'class' => 'chosen-select',
                ),
                'data' => $consultant,
             ));
        }

        $form = $formbuilder->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $s->remove('admin_psychicstats_ca');
            if (!$psychic_mode) {
                $consultant = $form['consultant']->getData();
                $s->set('admin_psychicstats_consultant', $consultant);
            }
            $periodbase_begin = $form['period_base']['begin']->getData();
            $periodbase_end = $form['period_base']['end']->getData();
        }

        $config = [
            'begin' => $periodbase_begin,
            'end' => $periodbase_end,
            'consultant' => $consultant,
            'get_stats' => true,
        ];
        $stats = $this->get('kgc.stat.calculator.consultant')->calculate($config);
        $s->set('admin_psychicstats_ca', $stats['stats']);

        $s->set('admin_psychicstats_periodbase_begin', $periodbase_begin);
        $s->set('admin_psychicstats_periodbase_end', $periodbase_end);

        return $this->render('KGCDashboardBundle:Admin:psychic.stats.html.twig', array(
            'consultant' => $consultant,
            'form' => $form->createView(),
            'psychic_mode' => $psychic_mode,
        ) + $stats);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function specificTchatAction(Request $request)
    {
        $s = $request->getSession();
        $tabs = $s->get('admin_specific_tabs') ?: array();
        $last_config = $s->get('admin_specific_config') ?: [
            'begin' => new \DateTime('first day of this month'),
            'end' => new \DateTime('last day of this month'),
            'statScope' => StatScopeType::KEY_ALL,
            'dateType' => StatDateType::KEY_DATE_TYPE_CONSULTATION,
            'sorting_column' => StatisticColumnType::CODE_RDV_TOTAL,
            'sorting_dir' => SortingColumnType::KEY_DESC,
            'websites' => array(), 'sources' => array(),
            'urls' => array(), 'codesPromo' => array(),
            'phonists' => array(),
            'proprios' => array(),
            'reflex_affiliates' => array(),
            'reflex_sources' => array(),
            'consultants' => array(),
            'supports' => array(),
            'ca' => StatisticColumnType::$CA_CHOICES,
            'rdv' => StatisticColumnType::$RDV_LIGHTED_CHOICES
        ];

        $websites = $this->getDoctrine()->getRepository('KGCSharedBundle:Website')->findAll(true);
        $sources = $this->getDoctrine()->getRepository('KGCRdvBundle:Source')->findAll(true);
        $urls = $this->getDoctrine()->getRepository('KGCRdvBundle:FormUrl')->findAll();
        $codesPromo = $this->getDoctrine()->getRepository('KGCRdvBundle:CodePromo')->findAll(true);
        $supports = $this->getDoctrine()->getRepository('KGCRdvBundle:Support')->findAll(true);

        $consultants = array();
        $phonists = array();
        $er = $this->getDoctrine()->getRepository('KGCUserBundle:Utilisateur');
        $landing = $this->getDoctrine()->getRepository('KGCSharedBundle:LandingUser');

        $consultants['all'] = 'Tous';
        $consultants['NULL'] = 'Aucun';
        foreach($er->findAllByMainProfil(Profil::VOYANT) as $c) {
            $consultants[$c->getId()] = $c->getUserName();
        }

        $phonists['all'] = 'Tous';
        foreach($er->findAllByMainProfil(Profil::PHONISTE) as $c) {
            $phonists[$c->getId()] = $c->getUserName();
        }
        $proprios['all'] = 'Tous';
        foreach($er->findByActif(1) as $c) {
            $proprios[$c->getId()] = $c->getUserName();
        }
        $reflex_affiliates['all'] = 'Tous';
        foreach($landing->getReflexAffiliate() as $aAffiliate) {
            $affiliate = $aAffiliate['reflexAffilateId'];
            $reflex_affiliates[$affiliate] = $affiliate;
        }
        $reflex_sources['all'] = 'Tous';
        foreach($landing->getReflexSource() as $aSource) {
            $source = $aSource['reflexSource'];
            $reflex_sources[$source] = $source;
        }

        $form = $this->createFormBuilder()
            ->add('period_base', new DatePeriodType(), [
                'required' => true,
                'data' => [
                    'begin' => $last_config['begin'],
                    'end' => $last_config['end'],
                ],
            ])
            ->add('statScope', new StatScopeType(), [
                'data' => [
                    'selected' => $last_config['statScope']
                ]
            ])
            ->add('dateType', new StatDateType(), [
                'data' => [
                    'selected' => isset($last_config['dateType']) ? $last_config['dateType'] : StatDateType::KEY_DATE_TYPE_CONSULTATION
                ]
            ])
            ->add('sorting_column', new SortingColumnType(), [
                'data' => [
                    'sorting_column' => $last_config['sorting_column'],
                    'sorting_dir' => $last_config['sorting_dir']
                ]
            ])
            ->add('websites', new WebsiteType($websites), [
                'data' => $last_config['websites']
            ])
            ->add('sources', new SourceType($sources), [
                'data' => $last_config['sources']
            ])
            ->add('urls', new UrlType($urls), [
                'data' => $last_config['urls']
            ])
            ->add('supports', new SupportType($supports), [
                'data' => $last_config['supports']
            ])
            ->add('codesPromo', new CodePromoType($codesPromo), [
                'data' => $last_config['codesPromo']
            ])
            ->add('phonists', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $phonists,
                'data' => $last_config['phonists'],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('proprios', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $proprios,
                'data' => isset($last_config['proprios']) ? $last_config['proprios'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('reflex_affiliates', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $reflex_affiliates,
                'data' => isset($last_config['reflex_affiliates']) ? $last_config['reflex_affiliates'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('reflex_sources', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $reflex_sources,
                'data' => isset($last_config['reflex_sources']) ? $last_config['reflex_sources'] : [],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('consultants', 'choice', array(
                'required' => false,
                'multiple' => true,
                'choices' => $consultants,
                'data' => $last_config['consultants'],
                'attr' => array(
                    'class' => 'chosen-select tag-input-style',
                    'data-placeholder' => "Indifférent",
                )
            ))
            ->add('columns', new StatisticColumnType(), [
                'data' => [
                    'selected_ca' => $last_config['ca'],
                    'selected_rdv' => $last_config['rdv'],
                    'separated' => true,
                    'multiple' => true,
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $tab_config = [
                'begin' =>  $form['period_base']['begin']->getData(),
                'end' => $form['period_base']['end']->getData(),
                'statScope' => $form['statScope']['statScope']->getData(),
                'dateType' => $form['dateType']['dateType']->getData(),
                'websites' => $form['websites']['websites']->getData(),
                'sources' => $form['sources']['sources']->getData(),
                'urls' => $form['urls']['urls']->getData(),
                'codesPromo' => $form['codesPromo']['codespromo']->getData(),
                'supports' => $form['supports']['supports']->getData(),
                'phonists' => $form['phonists']->getData(),
                'proprios' => $form['proprios']->getData(),
                'reflex_affiliates' => $form['reflex_affiliates']->getData(),
                'reflex_sources' => $form['reflex_sources']->getData(),
                'consultants' => $form['consultants']->getData(),
                'rdv' => $form['columns']['rdv']->getData(),
                'ca' => $form['columns']['ca']->getData(),
                'sorting_dir' => $form['sorting_column']['dir']->getData(),
                'sorting_column' => $form['sorting_column']['column']['column']->getData()
            ];
            array_unshift($tabs, $tab_config);
            $s->set('admin_specific_config', $tab_config);
        }

        $s->set('admin_specific_tabs', $tabs);


        return $this->render('KGCDashboardBundle:Admin:specific.stats.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function aboTchatAction(Request $request)
    {
        $date = $request->getSession()->has('admin_abo_tchat') ? $request->getSession()->get('admin_abo_tchat'): new \DateTime();
        $columns = $request->getSession()->has('admin_abo_tchat_columns') ? $request->getSession()->get('admin_abo_tchat_columns'): AboColumnType::$ABO_LIGHTED_CHOICES;
        $form = $this->createFormBuilder()
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'empty_data' => '01/' . date('m/Y'),
                'limit-size' => true,
                'attr' => array(
                    'class' => 'date-picker-month',
                ),
            ))
            ->add('columns', new AboColumnType(), [
                'data' => [
                    'selected_abo' => $columns,
                    'multiple' => true,
                ]
            ])
            ->getForm()
        ;
        $form['date']->setData($date);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $date = $form['date']->getData();
            $request->getSession()->set('admin_abo_tchat', $date);
            $columns = $form['columns']->getData();
            if(!empty($columns)) {
                $request->getSession()->set('admin_abo_tchat_columns', $columns['abo']);
            }
            $columns = $columns['abo'];
        } elseif ($form->isSubmitted()) {
            if ($form['date']->getData() === null) {
                $request->getSession()->remove('admin_abo_tchat');
            }
            if ($form['columns']->getData() === null) {
                $request->getSession()->remove('admin_abo_tchat_columns');
            }
        }

        if(!$request->getSession()->has('admin_abo_tchat')) {
            $request->getSession()->set('admin_abo_tchat', $date);
        }

        $params = $this->getChatCalculator()->calculate([
            'date' => $form->get('date')->getData(),
            'columns' => $columns,
            'stat_abo' => true,
        ]);

        return $this->render('KGCDashboardBundle:Admin:abo-tchat.widget.stats.html.twig', [
            'form' => $form->createView(),
            'columns' => $columns,
            'columns_name' => AboColumnType::$ABO_HEADER,
            'date' => $date,
            'list' => $params
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_MANAGER_CHAT, ROLE_ADMIN_CHAT")
     */
    public function aboTchatDetailAction(Request $request, $type, $column = 'NB', $date)
    {
        $export = $request->query->has('export');
        $params = [
            'date' => $date,
            'title' => ChatCalculator::LABELS[$type],
            'type' => $type,
            'column' => $column,
            'request' => $request,
            'export' => $export,
            'stat_abo' => true,
        ];

        $data = $this->getChatCalculator()->details($params);

        if($export) {
            return new Response($data['csv'], 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_stats_abo.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }


        return $this->render('KGCDashboardBundle:Admin:abo-tchat.details.stats.html.twig', $data + $params);
    }

    /**
     * @return ChatCalculator
     */
    protected function getChatCalculator()
    {
        return $this->get('kgc.stat.calculator.chat');
    }
}
