<?php

namespace KGC\RdvBundle\Controller;

use Elastica\Search;
use KGC\RdvBundle\Elastic\Exporter\CsvExporter;
use KGC\RdvBundle\Elastic\Finder\RDVFinder;
use KGC\RdvBundle\Elastic\Formatter\ArrayFormatter;
use KGC\RdvBundle\Elastic\Formatter\RdvFormatter;
use KGC\RdvBundle\Elastic\Model\RdvSearch;
use KGC\RdvBundle\Elastic\Paginator\ElasticPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Class ElasticController.
 */
class ElasticController extends Controller
{
    /**
     * @return RDVFinder
     */
    protected function getRdvFinder()
    {
        return $this->get('kgc.elastic.rdv.finder');
    }

    /**
     * @return ArrayFormatter
     */
    protected function getArrayFormatter()
    {
        $formatter = $this->get('kgc.elastic.rdv.array_formatter');
        $formatter->setFormatter($this->getRdvFormatter());

        return $formatter;
    }

    /**
     * @return RdvFormatter
     */
    protected function getRdvFormatter()
    {
        return $this->get('kgc.elastic.rdv.rdv_formatter');
    }

    /**
     * @return RdvFormatter
     */
    protected function getCRMRdvFormatter()
    {
        return $this->get('kgc.elastic.rdv.crmrdv_formatter');
    }

    /**
     * @return CsvExporter
     */
    protected function getCsvExporter()
    {
        return $this->get('kgc.elastic.rdv.csv_exporter');
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
    public function exportConsultationAction(Request $request, $format)
    {
        $saver = $this->get('kgc.elastic.rdv.form_saver');

        if (null === ($saved = $saver->find('rdv.search-advanced_search'))) {
            throw new \Exception('Session empty for advanced search');
        }

        $finder = $this->getRdvFinder();
        $finder->setRdvSearch($saved);
        $finder->buildQuery();

        $query = $finder->getQuery();
        $exporter = $this->getCsvExporter();

        $formatter = null;
        if ($format == 'rdv-crm') {
            $formatter = $this->getCRMRdvFormatter();
        } elseif ($format == 'rdv-global') {
            $formatter = $this->getRdvFormatter();
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
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_MANAGER_PHONE, ROLE_QUALITE, ROLE_UNPAID_SERVICE, ROLE_VALIDATION, ROLE_MANAGER_CHAT, ROLE_MANAGER_STANDAR, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1, ROLE_PHONISTE, ROLE_PHONING_TODAY")
     */
    public function searchConsultationAction(Request $request)
    {
        $paginator = null;
        $rdvSearch = new RdvSearch();

        $view = 'rdv';
        $requestView = $request->query->get('request_view');
        $view = $requestView ?: $view;
        $sessionKey = 'advanced_search';
        $sessionKey = $requestView ? $requestView . '-' . $sessionKey : $sessionKey;

        $saver = $this->get('kgc.elastic.rdv.form_saver');
        $saved = $saver->find($sessionKey);
        $rdvSearch = null !== $saved ? $saved : $rdvSearch;

        $rdvSearchForm = $this->createForm($this->get('kgc.elastic.rdv.form'), $rdvSearch);
        $rdvSearchForm->handleRequest($request);
        $data = $rdvSearchForm->getData();
        if (null !== $data) {
            $finder = $this->getRdvFinder();
            $finder->setRdvSearch($data);
            $finder->buildQuery(null);
            $paginatorAdapter = $finder->getAdapter();
            $page = $data->getPage() ?: 1;
            $pageRange = $data->getPageRange() ?: 10;
            $paginator = new ElasticPaginator($paginatorAdapter, $page, $pageRange);
            $saver->save($sessionKey, $data);
        }

        return $this->render('KGCRdvBundle:Elastic:' . $view . '.html.twig', [
            'paginator' => $paginator,
            'form' => $rdvSearchForm->createView(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_MANAGER_PHONIST, ROLE_PHONISTE, ROLE_PHONING_TODAY")
     */
    public function searchPhonisteAction(Request $request)
    {
        $paginator = null;
        $rdvSearch = new RdvSearch();

        $view = 'phoniste.search';
        $requestView = $request->query->get('request_view');
        $view = $requestView ?: $view;
        $sessionKey = 'advanced_phoniste_search';
        $sessionKey = $requestView ? $requestView . '-' . $sessionKey : $sessionKey;

        $saver = $this->get('kgc.elastic.rdv.form_saver');
        $saved = $saver->find($sessionKey);
        $rdvSearch = null !== $saved ? $saved : $rdvSearch;

        $rdvSearchForm = $this->createForm($this->get('kgc.elastic.rdv.id.form'), $rdvSearch);
        $rdvSearchForm->handleRequest($request);
        $data = $rdvSearchForm->getData();
        if (null !== $data) {
            $finder = $this->getRdvFinder();
            $finder->setRdvSearch($data);
            $finder->buildQuery(null);
            $paginatorAdapter = $finder->getAdapter();
            $page = $data->getPage() ?: 1;
            $pageRange = $data->getPageRange() ?: 10;
            $paginator = new ElasticPaginator($paginatorAdapter, $page, $pageRange);
            $saver->save($sessionKey, $data);
        }

        return $this->render('KGCRdvBundle:Elastic:' . $view . '.html.twig', [
            'paginator' => $paginator,
            'form' => $rdvSearchForm->createView(),
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

        $name = $params['client']['nom'];
        $firstname = $params['client']['prenom'];
        $mail = $params['client']['mail'];
        $phones = preg_replace('/[^a-z0-9]+/i', '', $params['numtel1']);

        $rdvSearch = new RdvSearch();
        $rdvSearch->setName(sprintf('%s %s', $name, $firstname));
        $rdvSearch->setMail($mail);
        $rdvSearch->setPhones($phones);

        $finder = $this->getRdvFinder();
        $finder->setRdvSearch($rdvSearch);
        $finder->buildQuery();
        $paginatorAdapter = $finder->getAdapter();
        $page = $rdvSearch->getPage() ?: 1;
        $pageRange = $rdvSearch->getPageRange() ?: 20;
        $paginator = new ElasticPaginator($paginatorAdapter, $page, $pageRange);

        return $this->render('KGCRdvBundle:Elastic:similarity.results.html.twig', [
            'paginator' => $paginator,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function similarityAction(Request $request, $collapsed = false)
    {
        return $this->render('KGCRdvBundle:Elastic:similarity.html.twig', [
            'collapsed' => $collapsed,
        ]);
    }
}
