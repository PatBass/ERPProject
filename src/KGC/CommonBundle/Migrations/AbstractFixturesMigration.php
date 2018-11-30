<?php

namespace KGC\CommonBundle\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\EntityManager;
use FOS\ElasticaBundle\Doctrine\Listener;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractFixturesMigration.
 */
abstract class AbstractFixturesMigration extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Doctrine\DBAL\Migrations\OutputWriter
     */
    protected $outputWriter;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        $listeners = $this->entityManager->getEventManager()->getListeners();
        $evm = $this->entityManager->getEventManager();
        foreach ($listeners as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof Listener) {
                    $evm->removeEventListener(
                        ['postLoad', 'postUpdate', 'postPersist', 'preRemove', 'preFlush', 'postFlush'],
                        $listener
                    );
                }
            }
        }
    }

    public function __construct(Version $version)
    {
        $this->outputWriter = $version->getConfiguration()->getOutputWriter();
        parent::__construct($version);
    }

    /**
     * Print msg.
     *
     * @param $msg
     */
    protected function log($msg)
    {
        $this->outputWriter->write($msg);
    }

    /**
     * @param $table
     * @param $set
     * @param $where
     */
    protected function updateExisting($table, $set, $where)
    {
        foreach ($set as $id => $params) {
            $this->log(sprintf('    Updating %s where <comment>%s = %s</comment>', $table, $where, $id));
            $qb = $this->entityManager->createQueryBuilder();
            $qb->update($table, 't');

            foreach ($params as $name => $value) {
                $qb->set('t.'.$name, ':'.$name.'value')->setParameter($name.'value', $value);
            }

            $qb->where('t.'.$where.' = :id')->setParameter('id', $id);

            $qb->getQuery()->execute();
        }
    }
}
