<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\UserBundle\Elastic\Finder;

use FOS\ElasticaBundle\Finder\FinderInterface as ElasticaFinderInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\UserBundle\Elastic\Model\ProspectSearch;
use KGC\UserBundle\Elastic\Repository\RepositoryInterface;
use KGC\RdvBundle\Service\Encryption;

/**
 * Elastic builder for prospect search.
 *
 * Class ProspectFinder
 *
 * @DI\Service("kgc.elastic.prospect.finder")
 */
class ProspectFinder implements FinderInterface
{
    /**
     * The FOSElasticBundle original finder for prospect index.
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
     * Model for prospect search.
     *
     * @var ProspectSearch
     */
    protected $prospectSearch;

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
        $method = 'get' . ucfirst($getter);
        $value = $this->prospectSearch->$method();
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
        $method = 'get' . ucfirst($getter);
        $values = $this->prospectSearch->$method();
        if (!empty($values)) {
            $this->repository->addMultipleFilter($elasticField, $values);
        }
    }

    /**
     * @param ElasticaFinderInterface $finder
     * @param RepositoryInterface $repository
     * @param Encryption $encryptor
     *
     * @throws \Exception
     *
     * @DI\InjectParams({
     *      "finder" = @DI\Inject("fos_elastica.finder.kgestion_idx.prospect"),
     *      "repository" = @DI\Inject("kgc.elastic.prospect.repository"),
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
     * @param ProspectSearch $prospectSearch
     */
    public function setProspectSearch(ProspectSearch $prospectSearch)
    {
        $this->prospectSearch = $prospectSearch;
    }

    /**
     * Return an array of allowed repositories.
     *
     * @return array
     */
    public function getValidRepositories()
    {
        return [
            'prospect',
            'prospect_test',
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
        if (null === $this->prospectSearch) {
            throw new \Exception(sprintf('The prospectSearch attribute must be set !'));
        }

        $idAstro = $this->prospectSearch->getIdAstro();
        if (!empty($idAstro)) {
            $this->repository->addFilter('idAstro', $idAstro);
        }

        $name = $this->prospectSearch->getName();
        if (!empty($name)) {
            $this->repository->addFuzzy('', ['firstname'], $name);
        }

        $mail = $this->prospectSearch->getMail();
        if (!empty($mail)) {
            $this->repository->addFuzzy('', ['email'], $mail);
        }

        $phones = $this->prospectSearch->getPhones();
        if (!empty($phones)) {
            $phones = preg_replace('/[^a-z0-9]+/i', '', $phones);
            $this->repository->addFuzzy('', ['phone'], $phones);
        }

        $birthdate = $this->prospectSearch->getBirthdate();
        if (null !== $birthdate) {
            $this->repository->addDatetimeRangeFilter('birthdayES', $birthdate, $birthdate);
        }
        $created = $this->prospectSearch->getDateBegin();
        if (null !== $created) {
            $this->repository->addDatetimeRangeFilter('createdAtES', $created, $created);
        }
        $this->repository->addSorting([
            '_score' => ['order' => 'DESC'],
            '_id' => ['order' => 'DESC'],
        ]);

        $this->query = $this->repository->getQuery($score);
    }

    public function findQuery(){
        $this->buildQuery();
        return $this->find($this->getQuery());
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
     * @param mixed $query Can be a string, an array or an \Elastica\Query object
     * @param int $limit How many results to get
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
