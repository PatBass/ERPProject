<?php

namespace KGC\ClientBundle\Controller;

use KGC\ClientBundle\Elastic\Exporter\CsvExporter;
use KGC\ClientBundle\Elastic\Finder\ClientFinder;
use KGC\ClientBundle\Elastic\Formatter\ClientFormatter;
use KGC\ClientBundle\Elastic\Model\ClientSearch;
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
     * @return ClientFinder
     */
    protected function getClientFinder()
    {
        return $this->get('kgc.elastic.client.finder');
    }

    /**
     * @return CsvExporter
     */
    protected function getCsvExporter()
    {
        return $this->get('kgc.elastic.client.csv_exporter');
    }

    /**
     * @return ClientFormatter
     */
    protected function getClientFormatter()
    {
        return $this->get('kgc.elastic.client.client_formatter');
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_ADMIN_CHAT, ROLE_MANAGER_CHAT, ROLE_MANAGER_PHONIST")
     */
    public function searchClientAction(Request $request)
    {
        $paginator = null;
        $search = new ClientSearch();

        $saver = $this->get('kgc.elastic.rdv.form_saver');
        $saved = $saver->find('elastic_client', 'KGC\ClientBundle\Elastic\Model\ClientSearch');
        $search = null !== $saved ? $saved : $search;

        $searchForm = $this->createForm($this->get('kgc.elastic.client.form'), $search);
        $searchForm->handleRequest($request);
        $data = $searchForm->getData();

        if (null !== $data) {
            $finder = $this->get('kgc.elastic.client.finder');
            $finder->setClientSearch($data);
            $finder->buildQuery();
            $paginatorAdapter = $finder->getAdapter();
            $paginator = new ElasticPaginator($paginatorAdapter, ($data->getPage() ?: 1), ($data->getPageRange() ?: 10));
            $saver->save('elastic_client', $data);
        }

        return $this->render('KGCClientBundle:elastic:list.html.twig', [
            'paginator' => $paginator,
            'form' => $searchForm->createView(),
        ]);
    }


    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Secure(roles="ROLE_STANDARD, ROLE_ADMIN_PHONE, ROLE_QUALITE, ROLE_UNPAID_SERVICE, ROLE_MANAGER_CHAT, ROLE_MANAGER_STANDAR, ROLE_VALIDATION, ROLE_MANAGER_PHONIST, ROLE_DRI, ROLE_J_1, ROLE_PHONISTE, ROLE_PHONING_TODAY, ROLE_MANAGER_PHONE")
     */
    public function searchClientWidgetAction(Request $request)
    {
        $paginator = null;
        $search = new ClientSearch();
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        $tchat = false;
        if ('chat' === $this->get('session')->get('dashboard') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $tchat = true;
        }

        $view = 'client.widget';
        $requestView = $request->query->get('request_view');
        $view = $requestView ?: $view;
        $saver = $this->get('kgc.elastic.client.form_saver');
        if($tchat){
            $saved = $saver->find('elastic_tchat_client_widget', 'KGC\ClientBundle\Elastic\Model\ClientSearch');
            $search = null !== $saved ? $saved : $search;
            $searchForm = $this->createForm($this->get('kgc.elastic.client.form'), $search);
        }else{
            $saved = $saver->find('elastic_client_widget', 'KGC\ClientBundle\Elastic\Model\ClientSearch');
            $search = null !== $saved ? $saved : $search;
            $searchForm = $this->createForm($this->get('kgc.elastic.client.widget.form'), $search);
        }

        $searchForm->handleRequest($request);
        $data = $searchForm->getData();
        if (null !== $data) {
            $finder = $this->get('kgc.elastic.client.finder');
            $finder->setClientSearch($data);
            $finder->buildQuery();
            $paginatorAdapter = $finder->getAdapter();
            $paginator = new ElasticPaginator($paginatorAdapter, ($data->getPage() ?: 1), ($data->getPageRange() ?: 10));
            if($tchat) {
                $saver->save('elastic_tchat_client_widget', $data);
            }else{
                $saver->save('elastic_client_widget', $data);
            }
        }
        $tchat = false;
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        if ('chat' === $this->get('session')->get('dashboard') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $tchat = true;
        }

        return $this->render('KGCRdvBundle:Elastic:' . $view . '.html.twig', [
            'paginator' => $paginator,
            'form' => $searchForm->createView(),
            'tchat' => $tchat
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
    public function exportClientAction(Request $request, $format)
    {
        $saver = $this->get('kgc.elastic.client.form_saver');
        $role = $this->getUser()->getMainprofil()->getRoleKey();
        $tchat = false;
        if ('chat' === $this->get('session')->get('dashboard') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $tchat = true;
        }
        if (null === ($saved = $tchat ? $saver->find('elastic_tchat_client_widget') : $saver->find('elastic_client_widget'))) {
            throw new \Exception('Session empty for advanced search');
        }

        $finder = $this->getClientFinder();
        $finder->setClientSearch($saved);
        $finder->buildQuery();

        $query = $finder->getQuery();
        $exporter = $this->getCsvExporter();

        $formatter = null;
        if ($format == 'client-global') {
            $formatter = $this->getClientFormatter();
        }
        if ($formatter !== null) {
            if ($exporter->export($query, $formatter)) {
                return $exporter->getResponse();
            }
        }

        throw new \Exception('Something goes wrong while exporting...');
    }
}
