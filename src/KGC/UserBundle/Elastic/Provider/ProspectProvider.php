<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:43
 */

namespace KGC\UserBundle\Elastic\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Elastica\Client;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Provider\ProviderInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class ProspectProvider.
 *
 * @DI\Service("kgc.elastic.prospect.provider")
 * @DI\Tag("fos_elastica.provider", attributes = {"index" = "kgestion_idx", "type" = "prospect"})
 */
class ProspectProvider implements ProviderInterface
{
    /**
     * @var ObjectPersisterInterface
     */
    protected $objectPersister;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ObjectPersisterInterface $objectPersister
     * @param Client $client
     * @param ManagerRegistry $doctrine
     *
     * @DI\InjectParams({
     *      "objectPersister" = @DI\Inject("fos_elastica.object_persister.kgestion_idx.prospect"),
     *      "client" = @DI\Inject("fos_elastica.client.default"),
     *      "doctrine" = @DI\Inject("doctrine"),
     * })
     */
    public function __construct(ObjectPersisterInterface $objectPersister, Client $client, ManagerRegistry $doctrine)
    {
        $this->objectPersister = $objectPersister;
        $this->client = $client;
        $this->doctrine = $doctrine;
    }

    /**
     * Insert the repository objects in the type index.
     *
     * @param \Closure $loggerClosure
     * @param array $options
     */
    public function populate(\Closure $loggerClosure = null, array $options = array())
    {
        $lastid = isset($options['offset']) ? intval($options['offset']) : 0;
        $batchSize = isset($options['batch_size']) ? intval($options['batch_size']) : 500;

        $em = $this->doctrine->getManager();
        $repository = $em->getRepository('KGCSharedBundle:LandingUser');
        $queryBuilder = $repository->createQueryBuilderIndex('prospect')
            ->orderBy('prospect.id', 'ASC')
            ->setMaxResults($batchSize)
            ->andWhere('prospect.id > :fromid AND (prospect.origin LIKE :myastro OR prospect.origin LIKE :dri)');

        gc_enable();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->client->setLogger(new \Psr\Log\NullLogger());

        do {
            // Reset start time
            if ($loggerClosure) {
                $stepStartTime = microtime(true);
            }

            // Retrieve data
            $objects = $queryBuilder
                ->setParameter('fromid', $lastid)
                ->setParameter('myastro', 'myastro')
                ->setParameter('dri', 'dri')
                ->getQuery()
                ->getResult();
            $count = count($objects);
            if ($count === 0) {
                break;
            }

            // Convert and save objects
            $this->objectPersister->insertMany($objects);

            // Log
            if ($loggerClosure) {
                $objectsPerSecond = $count / (microtime(true) - $stepStartTime);
                $loggerClosure(null, null, sprintf('lastid %s, %d for this bulk, %d objects/s %s', $lastid, $count, $objectsPerSecond, $this->getMemoryUsage()));
            }

            // Save last id for query
            $lastid = end($objects)->getId();

            foreach ($objects as $o) {
                $em->detach($o);
            }
            $em->clear();
            unset($objects);
            gc_collect_cycles();
        } while (true);
    }

    /**
     * Get string with RAM usage information (current and peak).
     *
     * @return string
     */
    protected function getMemoryUsage()
    {
        $memory = round(memory_get_usage() / (1024 * 1024), 0); // to get usage in Mo
        $memoryMax = round(memory_get_peak_usage() / (1024 * 1024)); // to get max usage in Mo
        $message = '(RAM : current=' . $memory . 'Mo peak=' . $memoryMax . 'Mo)';

        return $message;
    }
}
