<?php

namespace KGC\RdvBundle\Elastic\Finder;

use FOS\ElasticaBundle\Finder\FinderInterface as ElasticaFinderInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\RdvBundle\Elastic\Model\RdvSearch;
use KGC\RdvBundle\Elastic\Repository\RepositoryInterface;
use KGC\RdvBundle\Service\Encryption;

/**
 * Elastic builder for RDV search.
 *
 * Class RDVRepository
 *
 * @DI\Service("kgc.elastic.rdv.finder")
 */
class RdvFinder implements FinderInterface
{
    /**
     * The FOSElasticBundle original finder for RDV index.
     *
     * @var FinderInterface
     */
    protected $finder;

    /**
     * Our custom elastic query builder repository.
     *
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var Encryption
     */
    protected $encryptor;

    /**
     * Model for rdv search.
     *
     * @var RdvSearch
     */
    protected $rdvSearch;

    /**
     * @var string
     */
    protected $query;

    /**
     * @param $getter
     * @param $elasticField
     */
    protected function addFilter($getter, $elasticField)
    {
        $method = 'get'.ucfirst($getter);
        $value = $this->rdvSearch->$method();
        if (null !== $value) {
            $this->repository->addFilter($elasticField, $value);
        }
    }

    /**
     * @param $getter
     * @param $elasticField
     */
    protected function addMultipleFilter($getter, $elasticField)
    {
        $method = 'get'.ucfirst($getter);
        $values = $this->rdvSearch->$method();
        if (!empty($values)) {
            $this->repository->addMultipleFilter($elasticField, $values);
        }
    }

    /**
     * @param ElasticaFinderInterface $finder
     * @param RepositoryInterface     $repository
     * @param Encryption              $encryptor
     *
     * @throws \Exception
     *
     * @DI\InjectParams({
     *      "finder" = @DI\Inject("fos_elastica.finder.kgestion_idx.rdv"),
     *      "repository" = @DI\Inject("kgc.elastic.rdv.repository"),
     *      "encryptor" = @DI\Inject("kgc.rdv.encryption.service"),
     * })
     */
    public function __construct(ElasticaFinderInterface $finder, RepositoryInterface $repository, Encryption $encryptor)
    {
        $this->finder = $finder;
        $this->repository = $repository;
        $this->encryptor = $encryptor;

        if (!$this->hasValidRepository()) {
            throw new \Exception(
                sprintf(
                    'Invalid repository name "%s", allowed repositories are "%s"',
                    $this->repository->getName(),
                    implode(', ', $this->getValidRepositories())
                )
            );
        }
    }

    /**
     * @param RdvSearch $rdvSearch
     */
    public function setRdvSearch(RdvSearch $rdvSearch)
    {
        $this->rdvSearch = $rdvSearch;
    }

    /**
     * Return an array of allowed repositories.
     *
     * @return array
     */
    public function getValidRepositories()
    {
        return [
            'rdv',
            'rdv_test',
        ];
    }

    /**
     * Check if the repository is valid.
     *
     * @return bool
     */
    public function hasValidRepository()
    {
        return in_array(
            $this->repository->getName(),
            $this->getValidRepositories()
        );
    }

    public function buildQuery($score = 1)
    {
        if (null === $this->rdvSearch) {
            throw new \Exception(sprintf('The rdvSearch attribute must be set !'));
        }

        $this->addFilter('Id', 'id');
        $this->addFilter('IdAstro', 'idAstro');

        //add forfaits
        $this->addMultipleFilter('Tags', 'etiquettesES');
        $this->addMultipleFilter('Forfaits', 'forfaitsES');
        $this->addMultipleFilter('Classements', 'classementES');
        $this->addMultipleFilter('States', 'etatES');
        $this->addMultipleFilter('Tpes', 'tpeES');
        $this->addMultipleFilter('Psychics', 'voyantES');
        $this->addMultipleFilter('Consultants', 'consultantES');
        $this->addMultipleFilter('Codepromos', 'codepromoES');
        $this->addMultipleFilter('Supports', 'supportES');
        $this->addMultipleFilter('Sources', 'sourceES');
        $this->addMultipleFilter('Websites', 'websiteES');
        $this->addMultipleFilter('FormUrls', 'formUrlES');

        $name = $this->rdvSearch->getName();
        if (!empty($name)) {
            $a = explode(' ', $name);
            $this->repository->addParentFuzzy('client', ['nomPrenom'], $name);

            $this->repository->addParentFuzzy('client', ['nom'], $a[0]);
            $this->repository->addParentFuzzy('client', ['prenom'], $a[0]);

            if (isset($a[1])) {
                $this->repository->addParentFuzzy('client', ['nom'], $a[1]);
                $this->repository->addParentFuzzy('client', ['prenom'], $a[1]);
            }
        }

        $id = $this->rdvSearch->getId();
        if (!empty($id)) {
            $this->repository->addFilter('id', $id);
        }

        $phones = $this->rdvSearch->getPhones();
        if (!empty($phones)) {
            $phones = preg_replace('/[^a-z0-9]+/i', '', $phones);
            $this->repository->addFuzzy('', ['numtel1ES', 'numtel2ES'], $phones);
        }

        $card = $this->rdvSearch->getCard();
        if (null !== $card) {
            $card = $this->encryptor->encrypt($card);
            $this->repository->addWildcard('cbsES', $card);
        }

        // Date management
        $dateBegin = $this->rdvSearch->getDateBegin();
        $dateEnd = $this->rdvSearch->getDateEnd();
        $dateType = $this->rdvSearch->getDateType();
        if ((null !== $dateBegin || null !== $dateEnd) && null !== $dateType) {
            $fields = [
                RdvSearch::DATE_CONSULTATION => 'dateConsultationES',
                RdvSearch::DATE_FOLLOW => 'dateContactES',
                RdvSearch::DATE_LAST_RECEIPT => 'dateLastEncaissementES',
                RdvSearch::DATE_NEXT_RECEIPT => 'dateNextEncaissementES'
            ];
            $this->repository->addDatetimeRangeFilter($fields[$dateType], $dateBegin, $dateEnd);
        }

        $birthdate = $this->rdvSearch->getBirthdate();
        if (null !== $birthdate) {
            $this->repository->addParentDatetimeRangeFilter('client', 'dateNaissanceES', $birthdate, $birthdate);
        }

        $mail = $this->rdvSearch->getMail();
        if (null !== $mail) {
            $this->repository->addParentFuzzy('client', ['mail'], $mail);
        }

        $timeMin = $this->rdvSearch->getTimeMin();
        $timeMax = $this->rdvSearch->getTimeMax();
        if (null !== $timeMin || null !== $timeMax) {
            $this->repository->addNestedNumericRangeFilter('tarification', 'temps', $timeMin, $timeMax);
        }

        $amountMin = $this->rdvSearch->getAmountMin();
        $amountMax = $this->rdvSearch->getAmountMax();
        if (null !== $amountMin || null !== $amountMax) {
            $this->repository->addNestedNumericRangeFilter('tarification', 'montantTotal', $amountMin, $amountMax);
        }

        $order = $this->rdvSearch->getOrderBy();
        $sort = $this->rdvSearch->getSortDirection();
        $this->repository->addSorting([
            $order => ['order' => $sort],
            'dateConsultationES' => ['order' => 'desc'],
        ]);

        $this->query = $this->repository->getQuery($score);

    }

    /**
     * @return mixed
     */
    public function getAdapter()
    {
        return $this->finder->findPaginated($this->query)->getAdapter();
    }

    /**
     * Searches for query results within a given limit.
     *
     * @param mixed $query   Can be a string, an array or an \Elastica\Query object
     * @param int   $limit   How many results to get
     * @param array $options
     *
     * @return array results
     */
    public function find($query, $limit = null, $options = array())
    {
        $adapter = $this->getAdapter();
        return $adapter->getSlice(0, 10);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
}
