<?php
// src/KGC/StatBundle/Controller/StatController.php

namespace KGC\StatBundle\Controller;

use Doctrine\ORM\EntityRepository;
use KGC\StatBundle\Calculator\ConsultantCalculator;
use KGC\StatBundle\Calculator\QualiteCalculator;
use KGC\StatBundle\Calculator\SpecificCalculator;
use KGC\StatBundle\Form\PastDateType;
use JMS\SecurityExtraBundle\Annotation\Secure;
use KGC\StatBundle\Calculator\AdminCalculator;
use KGC\StatBundle\Calculator\PhonisteCalculator;
use KGC\StatBundle\Calculator\StandardCalculator;
use KGC\StatBundle\Form\IntervalDateType;
use KGC\UserBundle\Entity\Profil;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class StatController.
 */
class StatController extends Controller
{
    /**
     * @return PhonisteCalculator
     */
    protected function getPhonisteCalculator()
    {
        return $this->get('kgc.stat.calculator.phoniste');
    }
    /**
     * @return StandardCalculator
     */
    protected function getStandardCalculator()
    {
        return $this->get('kgc.stat.calculator.standard');
    }

    /**
     * @return ConsultantCalculator
     */
    protected function getConsultantCalculator()
    {
        return $this->get('kgc.stat.calculator.consultant');
    }

    /**
     * @return AdminCalculator
     */
    protected function getAdminCalculator()
    {
        return $this->get('kgc.stat.calculator.admin');
    }

    /**
     * @return QualiteCalculator
     */
    protected function getQualiteCalculator()
    {
        return $this->get('kgc.stat.calculator.qualite');
    }

    /**
     * @return SpecificCalculator
     */
    protected function getSpecificCalculator()
    {
        return $this->get('kgc.stat.calculator.specific');
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function legendPhonisteAction(Request $request)
    {
        $params = $this->getPhonisteCalculator()->calculate();

        return $this->render('KGCStatBundle:Phoniste:legend.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function objectivePhonisteAction(Request $request)
    {
        $params = $this->getPhonisteCalculator()->calculate([
            'get_count' => true,
            'get_objective' => true,
        ]);

        return $this->render('KGCStatBundle:Phoniste:objective.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function bonusesPhonisteAction(Request $request)
    {
        $params = $this->getPhonisteCalculator()->calculate([
            'get_count' => true,
            'get_objective' => false,
            'get_validated' => true,
        ]);

        return $this->render('KGCStatBundle:Phoniste:bonuses.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function circleObjectivesPhonisteAction(Request $request)
    {
        $params = $this->getPhonisteCalculator()->calculate([
            'get_count' => true,
            'get_circle_objective_data' => true,
        ]);

        return $this->render('KGCStatBundle:Phoniste:circle_objectives.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_PHONISTE, ROLE_MANAGER_PHONIST")
     */
    public function bonusesRecapPhonisteAction(Request $request)
    {
        $date = $request->getSession()->get('phoniste_stats_date');
        $date = isset($date) ? $date : new \DateTime;

        $form = $this->createFormBuilder()->add('past_date', new PastDateType(), ['data'=>$date])->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        $request->getSession()->set('phoniste_stats_date', $date);

        $params = $this->getPhonisteCalculator()->calculate([
            'date' => $date,
            'get_count' => true,
            'get_bonus' => true,
        ]);

        return $this->render('KGCStatBundle:Phoniste:bonuses_recap.html.twig', $params + [ 'form'=> $form->createView() ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function usersStandardAction(Request $request)
    {
        $params = $this->getStandardCalculator()->calculate([
            'get_users' => true,
        ]);

        return $this->render('KGCStatBundle:Standard:users.html.twig', $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function statsStandardAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        $request->getSession()->set('standard_stats_date', $date);

        $params = $this->getStandardCalculator()->calculate([
            'date' => $date,
            'get_stats' => true,
            'get_support' => false,
            'get_phoning' => false,
        ]);

        return $this->render('KGCStatBundle:Standard:stats.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_VALIDATION, ROLE_MANAGER_PHONE")
     */
    public function statsStandardTurnoverDetailsAction(Request $request, $type = '', $periode = '')
    {
        $export = $request->query->has('export');
        $params = [
            'date' => $request->getSession()->get('standard_stats_date') ? : new \DateTime(),
            'type' => $type,
            'periode' => $periode,
            'export' => $export,
        ];

        $data = $this->getStandardCalculator()->details($params);

        if($export) {
            return new Response($data['csv'], 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_stats_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCStatBundle:Standard:stats_details.html.twig', $data + $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statsStandardTurnoverDetailsRdvAction(Request $request, $type = '', $periode = '')
    {
        $export = $request->query->has('export');
        $params = [
            'date' => $request->getSession()->get('standard_stats_date') ? : new \DateTime(),
            'type' => $type,
            'periode' => $periode,
            'support' => (int)$request->query->get('support'),
            'proprio' => (int)$request->query->get('proprio'),
            'export' => $export,
        ];

        $data = $this->getStandardCalculator()->details($params);

        if($export) {
            return new Response($data['csv'], 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_stats_details_rdv.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCStatBundle:Standard:stats_details_rdv.html.twig', $data + $params);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function supportStandardAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        $request->getSession()->set('standard_stats_date', $date);

        $params = $this->getStandardCalculator()->calculate([
            'date' => $form->get('past_date')->getData(),
            'get_stats' => false,
            'get_support' => true,
            'get_phoning' => false,
        ]);

        return $this->render('KGCStatBundle:Standard:support.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function phoningStandardAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $date = $form->get('past_date')->getData();
        $request->getSession()->set('standard_stats_date', $date);

        $params = $this->getStandardCalculator()->calculate([
            'date' => $form->get('past_date')->getData(),
            'get_stats' => false,
            'get_support' => false,
            'get_phoning' => true,
        ]);

        return $this->render('KGCStatBundle:Standard:phoning.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_VOYANT, ROLE_MANAGER_PHONE, ROLE_VOYANT")
     */
    public function psychicAveragesAction(Request $request, $idconsultant = 0)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');
        $ca_stats = $request->getSession()->get('admin_psychicstats_ca');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_stats' => $ca_stats,
            'get_averages' => true,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Admin:psychic_averages.html.twig',
            $stats + $config
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicProductsAction(Request $request, $idconsultant = 0)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_products' => true,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Admin:psychic_products.html.twig',
            $stats + $config
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicOppositionsAction(Request $request, $idconsultant = 0)
    {
        $details = $request->query->get('details') ?: false;
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_oppos' => true,
            'details' => $details,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        if($details === false) {
            return $this->render('KGCStatBundle:Admin:psychic_oppos.html.twig',
                $stats + $config
            );
        }
        else if($request->query->has('is_turnover') && $request->query->get('is_turnover')) {
            return $this->render('KGCStatBundle:Admin:psychic_specific_ca_details.html.twig', $stats);
        }
        else {
            return $this->render('KGCStatBundle:Admin:psychic_specific_rdv_details.html.twig', [
                'config' => $config,
                'fiches' => $stats['details'],
                'title' => $stats['title']
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicCAAction(Request $request, $idconsultant = 0)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');
        $ca_stats = $request->getSession()->get('admin_psychicstats_ca');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_stats' => $ca_stats,
            'get_ca_stats' => true,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Admin:psychic_ca.html.twig',
            $stats + $config
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicCATableDetailsAction(Request $request, $idconsultant = 0)
    {
        $details = $request->query->get('details');
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_stats' => true,
            'details' => $details,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        if($request->query->has('is_turnover') && $request->query->get('is_turnover')) {
            return $this->render('KGCStatBundle:Admin:psychic_specific_ca_details.html.twig', $stats);
        }
        else {
            return $this->render('KGCStatBundle:Admin:psychic_specific_rdv_details.html.twig', [
                'config' => $config,
                'fiches' => $stats['details'],
                'title' => $stats['title']
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_VOYANT, ROLE_MANAGER_PHONE")
     */
    public function psychicCountsAction(Request $request, $idconsultant = 0)
    {
        $details = $request->query->get('details') ?: false;
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_counts' => true,
            'details' => $details
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        if($details === false) {
            return $this->render('KGCStatBundle:Admin:psychic_counts.html.twig',
                $stats + $config
            );
        }
        else {
            return $this->render('KGCStatBundle:Admin:psychic_specific_rdv_details.html.twig', [
                'config' => $config,
                'fiches' => $stats['details'],
                'title' => $stats['title']
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_VOYANT, ROLE_MANAGER_PHONE, ROLE_VOYANT")
     */
    public function psychicSalaryAction(Request $request, $idconsultant = 0)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');
        $ca_stats = $request->getSession()->get('admin_psychicstats_ca');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_stats' => $ca_stats,
            'get_averages' => true,
            'get_ca_stats' => true,
            'get_counts' => true,
            'get_salary' => true,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Admin:psychic_salary.html.twig',
            $stats + $config
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_VOYANT, ROLE_MANAGER_PHONE, ROLE_VOYANT")
     */
    public function psychicBonusAction(Request $request, $idconsultant = 0)
    {
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $begin = $request->getSession()->get('admin_psychicstats_periodbase_begin');
        $end = $request->getSession()->get('admin_psychicstats_periodbase_end');
        $ca_stats = $request->getSession()->get('admin_psychicstats_ca');

        $config = [
            'begin' => $begin,
            'end' => $end,
            'consultant' => $consultant,
            'get_stats' => $ca_stats,
            'get_averages' => true,
            'get_ca_stats' => true,
            'get_counts' => true,
            'get_bonus' => true,
        ];

        $stats = $this->getConsultantCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Admin:psychic_bonus.html.twig',
            $stats + $config
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE")
     */
    public function pointageCADetailAdminAction(Request $request)
    {
        $date = $request->getSession()->get('ca_detail_date');
        $export = $request->query->has('export');

        $selectDate = $request->query->get('select_date');
        $selectDate = str_replace('-', '/', $selectDate);

        $params = $this->getAdminCalculator()->calculate([
            'date' => $date,
            'get_ca' => true,
            'details' => true,
            'select_date' => $selectDate,
            'select_tpe' => $request->query->get('select_tpe'),
            'export' => $export
        ]);

        if($export) {
            return new Response($params['csv'], 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_details_pointage_ca.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCStatBundle:Admin:roi.details.html.twig',
            $params + [
                'select_date' => $selectDate,
                'select_tpe' => $request->query->get('select_tpe'),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONE")
     */
    public function pointageCAAdminAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $params = $this->getAdminCalculator()->calculate([
            'date' => $form->get('past_date')->getData(),
            'get_ca' => true,
        ]);

        $request->getSession()->set('ca_detail_date', $form->get('past_date')->getData());

        return $this->render('KGCStatBundle:Admin:roi.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_UNPAID_SERVICE, ROLE_MANAGER_PHONE")
     */
    public function unpaidAdminAction(Request $request)
    {
        $form = $this->createFormBuilder()->add('past_date', new PastDateType())->getForm();
        $form->handleRequest($request);

        $params = $this->getAdminCalculator()->calculate([
            'date' => $form->get('past_date')->getData(),
            'get_unpaid' => true,
        ]);

        return $this->render('KGCStatBundle:Admin:unpaid.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_STANDARD, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE")
     */
    public function phoningAdminAction(Request $request)
    {
        $form = $this->createForm(new IntervalDateType());
        $form->handleRequest($request);

        $params = $this->getAdminCalculator()->calculate([
            'date_begin' => $form->get('date_begin')->getData(),
            'date_end' => $form->get('date_end')->getData(),
            'get_phoning' => true,
        ]);

        return $this->render('KGCStatBundle:Admin:phoning.html.twig',
            $params + ['form' => $form->createView()]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_QUALITE, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statsQualiteAction(Request $request, $idconsultant = 0)
    {
        $date = $request->getSession()->get('quality_stats_date') ?: new \DateTime();
        $user = $request->getSession()->get('quality_stats_user');
        if ($user instanceof Utilisateur) {
            $user = $this->getDoctrine()->getEntityManager()->merge($user);
        } else {
            $user = $this->getUser();
        }
        $this->getDoctrine()->getEntityManager()->merge($user);
        $consultant = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur')->findOneById($idconsultant);

        $form = $this->createFormBuilder()
            ->add('date', new PastDateType())
            ->add('user', 'entity', array(
                'class' => 'KGCUserBundle:Utilisateur',
                'property' => 'username',
                'query_builder' => function (EntityRepository $er) {
                    return $er->findAllByMainProfilQB(Profil::QUALITE, true);
                },
                'attr' => array(
                    'class' => 'chosen-select',
                    'data-width' => '90%',
                ),
                'required' => true,
            ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $date = $form->get('date')->getData();
            $user = $form->get('user')->getData();
            $request->getSession()->set('quality_stats_date', $form->get('date')->getData());
            $request->getSession()->set('quality_stats_user', $form->get('user')->getData());
        } elseif ($form->isSubmitted()) {
            $date = new \DateTime();
            $request->getSession()->remove('quality_stats_date');
            $request->getSession()->remove('quality_stats_user');
        }

        $config = [
            'date' => $date,
            'stats' => ['general' => true],
            'user' => $user,
            'consultant' => $consultant,
        ];
        $data = $this->getQualiteCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Qualite:stats.html.twig', $data + $config + [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_QUALITE, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function statsQualiteDetailsAction(Request $request, $idconsultant = 0, $stat = '', $periode = '')
    {
        $uti_rep = $this->getDoctrine()->getManager()->getRepository('KGCUserBundle:Utilisateur');
        $consultant = $uti_rep->findOneById($idconsultant);
        $date = $request->getSession()->get('quality_stats_date') ?: new \DateTime();
        $user = $request->getSession()->get('quality_stats_user') ?: $this->getUser();

        $config = [
            'date' => $date,
            'stats' => [$stat => true],
            'user' => $user,
            'consultant' => $consultant,
            'periode' => $periode,
        ];

        $data = $this->getQualiteCalculator()->calculate($config);

        return $this->render('KGCStatBundle:Qualite:details.html.twig', $config + [
            'fiches' => isset($data['details']) ? $data['details'] : [],
            'stat' => $stat,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE, ROLE_AFFILIATE")
     */
    public function specificAdminAction(Request $request)
    {
        $i = $request->query->get('i');
        $export = $request->query->has('export');
        $tabs = $request->getSession()->get('admin_specific_tabs');
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        $data = $this->getSpecificCalculator()->calculate($tabs[$i] + ['specific_full' => 1, 'export' => $export, 'role' => $role]);

        if($export) {
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_statistiques.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        return $this->render('KGCStatBundle:Admin:specific.html.twig', ['data' => $tabs[$i] + $data, 'table_key' => $i]);
    }

    /**
     * @param Request $request
     * @param $i
     * @param $sort
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function specificSortTabAdminAction(Request $request)
    {
        $i = $request->query->get('i');
        $sort = $request->query->get('sort');
        $tabs = $request->getSession()->get('admin_specific_tabs');
        if(array_key_exists($i, $tabs)) {
            if($sort === $tabs[$i]['sorting_column']) {
                $tabs[$i]['sorting_dir'] = $tabs[$i]['sorting_dir'] == 'ASC' ? 'DESC' : 'ASC';
            }
            else {
                $tabs[$i]['sorting_column'] = $sort;
                $tabs[$i]['sorting_dir'] = 'DESC';
            }

            $request->getSession()->set('admin_specific_tabs', $tabs);
        }
        return $this->redirect($this->generateUrl('kgc_admin_specific'));
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONIST, ROLE_MANAGER_PHONE, ROLE_AFFILIATE")
     */
    public function specificDeleteTabAdminAction(Request $request)
    {
        $i = $request->query->get('i');
        if ($i == "all") {
            $request->getSession()->set('admin_specific_tabs', array());
            $request->getSession()->remove('admin_specific_config');
        } else {
            $tabs = $request->getSession()->get('admin_specific_tabs');
            if (array_key_exists($i, $tabs)) {
                array_splice($tabs, $i, 1);
                $request->getSession()->set('admin_specific_tabs', $tabs);
            }
        }

        return $this->redirect($this->generateUrl('kgc_admin_specific'));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_AFFILIATE")
     */
    public function specificDetailsAction(Request $request)
    {
        $session = $request->getSession();
        $table_config = $session->get('admin_specific_tabs')[$request->query->get('table_key')];

        $config = [
            'columnCode' => $request->query->get('columnCode'),
            'phonist_id' => $request->query->get('phonist_id'),
            'proprio_id' => $request->query->get('proprio_id'),
            'reflex_affiliate_id' => $request->query->get('reflex_affiliate_id'),
            'reflex_source_id' => $request->query->get('reflex_source_id'),
            'consultant_id' => $request->query->get('consultant_id'),
            'website_id' => $request->query->get('website_id'),
            'source_id' => $request->query->get('source_id'),
            'url_id' => $request->query->get('url_id'),
            'codepromo_id' => $request->query->get('codepromo_id'),
            'support_id' => $request->query->get('support_id'),
            'table_key' => $request->query->get('table_key'),
            'export' => $request->query->has('export'),
            'begin' => $table_config['begin'],
            'end' => $table_config['end'],
            'statScope' => $table_config['statScope'],
            'dateType' => $table_config['dateType'],
            'ca_details' => $request->query->has('ca_details') && $request->query->get('ca_details'),
            'rdv_details' => $request->query->has('rdv_details') && $request->query->get('rdv_details'),
        ];

        $data = $this->getSpecificCalculator()->calculate($config);

        if($config['export']) {
            return new Response($data, 200, array(
                'Content-Description' => 'File Transfer',
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="export_statistiques_ca_details.csv"',
                'Content-Tranfser-Encoding' => 'binary'
            ));
        }

        if($config['ca_details']) {
            return $this->render('KGCStatBundle:Admin:specific_ca_details.html.twig', $data + ['config' => $config]);
        }
        else {
            return $this->render('KGCStatBundle:Admin:specific_rdv_details.html.twig', [
                'config' => $config,
                'fiches' => $data['details'],
                'title' => $data['title']
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function generalFullAdminAction(Request $request)
    {
        $begin = $request->getSession()->get('admin_general_periodbase_begin');
        $end = $request->getSession()->get('admin_general_periodbase_end');

        $params = $this->getAdminCalculator()->calculate([
            'date_begin' => $begin,
            'date_end' => $end,
            'get_general_full' => true,
        ]);

        return $this->render('KGCStatBundle:Admin:general.full.html.twig',
            $params
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function generalMonthAdminAction(Request $request)
    {
        $begin = $request->getSession()->get('admin_general_periodbase_begin');
        $end = $request->getSession()->get('admin_general_periodbase_end');

        $params = $this->getAdminCalculator()->calculate([
            'date_begin' => $begin,
            'date_end' => $end,
            'get_general_month' => true,
        ]);

        return $this->render('KGCStatBundle:Admin:general.month.html.twig',
            $params
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function generalWebsiteSupportAdminAction(Request $request)
    {
        $type = $request->query->get('type');
        $id = $request->query->get('id');
        $nature = $request->query->get('nature');
        $group = $request->query->get('group');

        $begin = $request->getSession()->get('admin_general_periodbase_begin');
        $end = $request->getSession()->get('admin_general_periodbase_end');

        if (null === $group) {
            throw new \Exception('You must define a group !');
        }

        $toGet = sprintf('get_general%s%s', '_'.$group, $type ? '_'.$type : '');

        $params = $this->getAdminCalculator()->calculate([
            'date_begin' => $begin,
            'date_end' => $end,
            $toGet => true,
        ]);

        return $this->render('KGCStatBundle:Admin:general.website_support.html.twig',
            $params + [
                'id' => $id,
                'nature' => $nature,
                'group' => $group,
            ]
        );
    }
}
