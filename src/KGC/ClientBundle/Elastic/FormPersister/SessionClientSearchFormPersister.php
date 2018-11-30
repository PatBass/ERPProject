<?php

/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:57
 */

namespace KGC\ClientBundle\Elastic\FormPersister;

use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class SessionClientSearchFormPersister.
 *
 * @DI\Service("kgc.elastic.client.session.form_persister")
 */
class SessionClientSearchFormPersister implements ClientSearchFormPersisterInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @param SessionInterface $session
     * @param LoggerInterface $logger
     *
     * @DI\InjectParams({
     *      "session" = @DI\Inject("session"),
     *      "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(SessionInterface $session, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * @param $key
     * @param $data
     *
     * @return mixed
     */
    public function persist($key, $data)
    {
        $this->logger->debug('Persisting information into session', [$key, $data]);
        $this->session->set($key, $data);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $data = $this->session->get($key);
        $this->logger->debug('Getting information from session', [$key, $data]);

        return $data;
    }
}
