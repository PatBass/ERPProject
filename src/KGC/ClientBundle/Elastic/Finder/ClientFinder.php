<?php

namespace KGC\ClientBundle\Elastic\Finder;

use FOS\ElasticaBundle\Finder\FinderInterface as ElasticaFinderInterface;
use JMS\DiExtraBundle\Annotation as DI;
use KGC\ClientBundle\Elastic\Model\ClientSearch;
use KGC\RdvBundle\Elastic\Repository\RepositoryInterface;
use KGC\RdvBundle\Service\Encryption;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @DI\Service("kgc.elastic.client.finder")
 */
class ClientFinder implements FinderInterface
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
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * Model for rdv search.
     *
     * @var ClientSearch
     */
    protected $clientSearch;

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
        $value = $this->clientSearch->$method();
        if (null !== $value) {
            $this->repository->addFilter($elasticField, $value);
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
     *      "finder" = @DI\Inject("fos_elastica.finder.kgestion_idx.client"),
     *      "repository" = @DI\Inject("kgc.elastic.rdv.repository"),
     *      "encryptor" = @DI\Inject("kgc.rdv.encryption.service"),
     *      "security"        = @DI\Inject("security.token_storage")
     * })
     */
    public function __construct(ElasticaFinderInterface $finder, RepositoryInterface $repository, Encryption $encryptor, TokenStorageInterface $security)
    {
        $this->finder = $finder;
        $this->repository = $repository;
        $this->encryptor = $encryptor;
        $this->security = $security;

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
     * Get a user from the Security Token Storage.
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see TokenInterface::getUser()
     */
    public function getUser()
    {
        if (!$this->security) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->security->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    /**
     * @param ClientSearch $clientSearch
     */
    public function setClientSearch(ClientSearch $clientSearch)
    {
        $this->clientSearch = $clientSearch;
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
        if (null === $this->clientSearch) {
            throw new \Exception(sprintf('The clientSearch attribute must be set !'));
        }

        $hasData = false;
        $role = $this->getUser()->getMainprofil()->getRoleKey();

        $name = $this->clientSearch->getName();
        if (!empty($name)) {
            $hasData = true;
            $a = explode(' ', $name);
            $this->repository->addFuzzy('', ['nomPrenom'], $name);

            $this->repository->addFuzzy('', ['nom'], $a[0]);
            $this->repository->addFuzzy('', ['prenom'], $a[0]);

            if (isset($a[1])) {
                $this->repository->addFuzzy('', ['nom'], $a[1]);
                $this->repository->addFuzzy('', ['prenom'], $a[1]);
            }
        }

        $birthdate = $this->clientSearch->getBirthdate();
        if (null !== $birthdate) {
            $hasData = true;
            $this->repository->addDatetimeRangeFilter('dateNaissanceES', $birthdate, $birthdate);
        }

        $dateBegin = $this->clientSearch->getDateCreationBegin();
        $dateEnd = $this->clientSearch->getDateCreationEnd();
        if (null !== $dateBegin || null !== $dateEnd) {
            $hasData = true;
            $this->repository->addDatetimeRangeFilter('dateCreationES', $dateBegin, $dateEnd);
        }

        $mail = $this->clientSearch->getMail();
        if (null !== $mail) {
            $hasData = true;
            $this->repository->addFuzzy('', ['mail'], $mail);
        }

        if ((isset($_SESSION['_sf2_attributes']['dashboard']) && $_SESSION['_sf2_attributes']['dashboard']=='chat') || in_array($role, ['admin_chat', 'manager_chat'])) {
            $formula = $this->clientSearch->getFormula();
            if (!empty($formula)) {
                $hasData = true;
                $this->repository->addWildcard('formulasES', $formula);
            }

            $psychic = $this->clientSearch->getPsychic();
            if (!empty($psychic)) {
                $hasData = true;
                $this->repository->addWildcard('psychicsES', $psychic);
            }

            $origin = $this->clientSearch->getOrigin();
            if (!empty($origin)) {
                $hasData = true;
                $this->repository->addFilter('origin', $origin);
            }

            $source = $this->clientSearch->getSource();
            if (!empty($source)) {
                $hasData = true;
                $this->repository->addFilter('sourceES', $source);
            }
        }

        $card = $this->clientSearch->getCard();
        if (null !== $card) {
            $hasData = true;
            $card = $this->encryptor->encrypt($card);
            $this->repository->addWildcard('cbsES', $card);
        }

        $phones = $this->clientSearch->getPhones();
        if (!empty($phones)) {
            $hasData = true;
            $this->repository->addFuzzy('', ['phonesES'], $phones);
        }

        if($hasData){
            if ((isset($_SESSION['_sf2_attributes']['dashboard']) && $_SESSION['_sf2_attributes']['dashboard']=='chat') || in_array($role, ['admin_chat', 'manager_chat'])) {
                $this->repository->addFilter('tchatES', 1);
            }else{
                $this->repository->addFilter('consultationES', 1);
            }
        }

        $order = $this->clientSearch->getOrderBy();
        $sort = $this->clientSearch->getSortDirection();
        $this->repository->addSorting([
            $order => ['order' => $sort],
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
