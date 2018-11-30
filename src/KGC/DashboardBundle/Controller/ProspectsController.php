<?php
namespace KGC\DashboardBundle\Controller;

use KGC\Bundle\SharedBundle\Entity\LandingUser;
use KGC\Bundle\SharedBundle\Entity\TotalLeads;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * ProspectsController.
 *
 * Page de gestion des prospects
 *
 * @category Controller
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ProspectsController extends Controller
{
    /**
     * Méthode leads.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE")
     */
    public function leadsAction()
    {
        $request = $this->get('request');
        $s = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $last_config = $s->get('leads_table') ?: [
            'date' => (new \DateTime())->modify('first day of this month'),
            'type' => LandingUser::EMAIL_TYPE,
        ];
        $date = $last_config['date'];
        $type = $last_config['type'];
        $choice = array(
            LandingUser::EMAIL_TYPE => 'e-mail',
            LandingUser::PHONE_TYPE => 'téléphone',
        );
        $form = $this->createFormBuilder()
            ->add('type', 'choice', array(
                'required' => true,
                'choices' => $choice,
                'choice_attr' => function ($val, $key, $index) {
                    return ['class' => 'submit-onchange'];
                },
                'expanded' => true,
                'data' => isset($last_config['type']) ? $last_config['type'] : $type,
            ))
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'empty_data' => '01/' . (isset($last_config['date']) ? $last_config['date']->format('m/Y') : $date->format('m/Y')),
                'limit-size' => true,
                'attr' => array(
                    'class' => 'date-picker-month submit-onchange',
                ),
            ))
            ->getForm();
        $form['date']->setData((isset($last_config['date']) ? $last_config['date']->modify('first day of this month') : $date->modify('first day of this month')));
        if ($request->getMethod() == 'POST') {
            if ($request->request->has('total', false)) {
                $total = $request->request->get('total', false);
                foreach ($total as $dateRow => $number) {
                    $datetime = new \DateTime($dateRow);
                    $totalObject = $em->getRepository('KGCSharedBundle:TotalLeads')->findOneBy(array('date' => $datetime, 'type' => $type));
                    if(is_null($totalObject)) {
                        $totalObject = new TotalLeads();
                        $totalObject->setDate($datetime);
                        $totalObject->setType($type);
                    }
                    $totalObject->setNumber($number);
                    $em->persist($totalObject);
                }
                $em->flush();
                $this->addFlash('light#ok-light', $date->format('m/Y').' ('.$choice[$type].')'. '--Total MAJ.');
            }
        }
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $date = $form['date']->getData();
            $type = $form['type']->getData();
            $s->set('leads_table', [
                'date' => $date,
                'type' => $type,
            ]);
        }

        $websites = $em->getRepository('KGCSharedBundle:Website')->getWebsitesLeadsOrder();
        $sources = $em->getRepository('KGCRdvBundle:Source')->getSourceLeads();

        $aheader = [];
        $aheader[] = ["label" => "DATE", "class" => "", 'btn' => 0];

        foreach ($websites as $website) {
            $websiteSources = $em->getRepository('KGCSharedBundle:LandingUser')->getSourcesOfWebsite($date, $website, $sources, $type);
            if (strtoupper($website->getLibelle()) == "MYASTRO") {
                $aheader[] = ["label" => strtoupper($website->getLibelle()), "class" => "header-color-pink", 'btn' => count($websiteSources) ? 1 : 0, 'website' => $website->getId()];
            } else {
                $aheader[] = ["label" => strtoupper($website->getLibelle()), "class" => "header-color-purple", 'btn' => count($websiteSources) ? 1 : 0, 'website' => $website->getId()];
            }
            foreach ($websiteSources as $source) {
                $color = "";
                switch (strtolower($source->getCode())) {
                    case "adwords":
                        $color = "header-color-green";
                        break;
                    case "facebook_adds":
                        $color = "header-color-blue";
                        break;
                    case "naturel":
                        $color = "header-color-pink";
                        break;
                }
                $aheader[] = ["label" => strtoupper($source->getLabel()), "class" => "hide " . $color . " source_website_" . $website->getId(), 'btn' => 0];
            }
        }
        $aheader[] = ["label" => "GLOBAL JOUR", "class" => "header-color-orange", 'btn' => 0];
        $aheader[] = ["label" => "TOTAL INSERE", "class" => "header-color-blue2", 'btn' => 0];
        $list = $em->getRepository('KGCSharedBundle:LandingUser')->getLeadsByDays($date, $websites, $sources, $type);
        $aTotal = array('websites' => [], 'GLOBAL' => 0);
        foreach ($list as $l) {
            foreach ($l['websites'] as $websiteName => $aWebsite) {
                if(!array_key_exists($websiteName, $aTotal['websites'])) {
                    $aTotal['websites'][$websiteName] = ['sources' => [], 'value' => 0, 'id' => $aWebsite['object']->getId()];
                }
                $aTotal['websites'][$websiteName]['value'] += $aWebsite['value'];
                foreach ($aWebsite['sources'] as $sourceName => $aSource) {
                    if(!array_key_exists($sourceName, $aTotal['websites'][$websiteName]['sources'])) {
                        $aTotal['websites'][$websiteName]['sources'][$sourceName] = 0;
                    }
                    $aTotal['websites'][$websiteName]['sources'][$sourceName] += $aSource['value'];
                }
            }
            $aTotal['GLOBAL'] += $l['GLOBAL'];
        }
        return $this->render('KGCDashboardBundle:Prospects:leads.html.twig', array(
            'type' => $type,
            'date' => $date,
            'form' => $form->createView(),
            'header' => $aheader,
            'listeLandingusers' => $list,
            'listeTotal' => $aTotal
        ));
    }
}
