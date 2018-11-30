<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:41
 */

namespace KGC\UserBundle\Controller;

use Elastica\Search;
use KGC\UserBundle\Elastic\Exporter\CsvExporter;
use KGC\UserBundle\Elastic\Finder\ProspectFinder;
use KGC\RdvBundle\Elastic\Formatter\ArrayFormatter;
use KGC\UserBundle\Elastic\Form\ProspectDRIType;
use KGC\UserBundle\Elastic\Formatter\ProspectFormatter;
use KGC\UserBundle\Elastic\Model\ProspectSearch;
use KGC\RdvBundle\Elastic\Paginator\ElasticPaginator;
use mageekguy\atoum\reports\asynchronous\vim;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ElasticController extends Controller
{

    /**
     * @return ProspectFinder
     */
    protected function getProspectFinder()
    {
        return $this->get('kgc.elastic.prospect.finder');
    }

    /**
     * @return CsvExporter
     */
    protected function getCsvExporter()
    {
        return $this->get('kgc.elastic.prospect.csv_exporter');
    }

    /**
     * @return ProspectFormatter
     */
    protected function getProspectFormatter()
    {
        return $this->get('kgc.elastic.prospect.prospect_formatter');
    }

    /**
     * @param string $vue
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_QUALITE, ROLE_ADMIN_PHONE, ROLE_PHONISTE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_STANDAR, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1")
     */
    public function searchProspectAction($vue = "standard", Request $request)
    {
        $paginator = null;
        $search = new ProspectSearch();
        $saver = $this->get('kgc.elastic.prospect.form_saver');
        $saved = $saver->find('elastic_prospect', 'KGC\UserBundle\Elastic\Model\ProspectSearch');
        $search = null !== $saved ? $saved : $search;

        $prospectSearchForm = $this->createForm($this->get('kgc.elastic.prospect.form'), $search);
        $prospectSearchForm->handleRequest($request);
        $data = $prospectSearchForm->getData();
        if (null !== $data) {
            $finder = $this->get('kgc.elastic.prospect.finder');
            $finder->setProspectSearch($data);
            $finder->buildQuery();
            $paginatorAdapter = $finder->getAdapter();
            $page = $data->getPage() ?: 1;
            $paginator = new ElasticPaginator($paginatorAdapter, $page, 50);
            $saver->save("elastic_prospect", $data);
        }

        return $this->render($vue == "phonist" ? 'KGCDashboardBundle:Dashboard:phoniste.html.twig' : 'KGCUserBundle:Elastic:' . $vue . '.html.twig', [
            'paginator' => $paginator,
            'form' => $prospectSearchForm->createView(),
        ]);
    }


    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_MANAGER_PHONE, ROLE_MANAGER_PHONIST")
     */
    public function exportProspectAction(Request $request, $format)
    {
        $saver = $this->get('kgc.elastic.prospect.form_saver');

        if (null === ($saved = $saver->find('elastic_prospect'))) {
            throw new \Exception('Session empty for advanced search');
        }

        $finder = $this->getProspectFinder();
        $finder->setProspectSearch($saved);
        $finder->buildQuery();

        $query = $finder->getQuery();
        $exporter = $this->getCsvExporter();

        $formatter = null;
        if ($format == 'prospect-global') {
            $formatter = $this->getProspectFormatter();
        }
        if ($formatter !== null) {
            if ($exporter->export($query, $formatter)) {
                return $exporter->getResponse();
            }
        }

        throw new \Exception('Something goes wrong while exporting...');
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function similarityAction(Request $request, $collapsed = false)
    {
        return $this->render('KGCUserBundle:Elastic:similarity.html.twig', [
            'collapsed' => $collapsed,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function searchSimilarityAction(Request $request)
    {
        $params = $request->request->get('kgc_RdvBundle_rdv');

        $idProspect = (!empty($params['idProspect'])) ? $params['idProspect'] : null;
        $firstname = $params['client']['prenom'];
        $mail = $params['client']['mail'];
        $phones = preg_replace('/[^a-z0-9]+/i', '', $params['numtel1']);

        $aArray = array();
        if(!empty($idProspect)) {
            $aArray['idProspect'] = $idProspect;
        }
        if(!empty($firstname)) {
            $aArray['firstname'] = '%'.$firstname.'%'; 
        }
        if(!empty($mail)) {
            $aArray['mail'] = '%'.$mail.'%';
        }
        if(!empty($phones)) {
            $aArray['phone'] = '%'.$phones.'%';
        }

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository("KGCSharedBundle:LandingUser")->getSimilary($aArray);

        $results = $qb->getQuery()->getResult();
        foreach($results as $prospect){
            $changed = false;
            if (is_null($prospect->getWebsite())) {
                $website = $em->getRepository('KGCSharedBundle:Website')->getWebsiteByAssociationName($prospect->getMyastroWebsite(), false);
                $prospect->setWebsite($website);
                $changed = true;
            }
            if (is_null($prospect->getSourceConsult())) {
                $source = $em->getRepository('KGCRdvBundle:Source')->getSourceByAssociationName($prospect->getMyastroSource());
                $prospect->setSourceConsult($source);
                $changed = true;
            }
            if (is_null($prospect->getCodePromo())) {
                $codePromo = $em->getRepository('KGCRdvBundle:CodePromo')->findOneByCode(strtoupper($prospect->getMyastroPromoCode()));
                $prospect->setCodePromo($codePromo);
                $changed = true;
            }
            if (is_null($prospect->getVoyant())) {
                $voyant = $em->getRepository('KGCUserBundle:Voyant')->findOneByNom($prospect->getMyastroPsychic());
                $prospect->setVoyant($voyant);
                $changed = true;
            }
            if (is_null($prospect->getSupport())) {
                $support = $em->getRepository('KGCRdvBundle:Support')->findOneByLibelle($prospect->getMyastroSupport());
                $prospect->setSupport($support);
                $changed = true;
            }
            if ((is_null($prospect->getFormurl()) && !is_null($prospect->getWebsite()) && !is_null($prospect->getSourceConsult()))) {
                $find = ['label' => strtolower($prospect->getMyastroUrl())];
                if (!empty($website)) {
                    $find['website'] = $website;
                }
                if (!empty($source)) {
                    $find['source'] = $source;
                }
                $formurl = $em->getRepository('KGCRdvBundle:FormUrl')->findOneBy($find);
                $prospect->setFormurl($formurl);
                $changed = true;
            }
            $client = $em->getRepository('KGCSharedBundle:LandingUser')->getProspectClient($prospect);
            if (!empty($client)) {
                $prospect->setClient($client);
                $changed = true;
            }
            if ($changed) {
                $em->persist($prospect);
                $em->flush();
            }
        }

        return $this->render('KGCUserBundle:Elastic:similarity.results.html.twig', [
            'results' => $results,
            'pager' => null
        ]);
    }
}