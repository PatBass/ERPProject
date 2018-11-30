<?php

namespace KGC\ClientBundle\Elastic\Event;

use KGC\Bundle\SharedBundle\Entity\Client;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CsvExporter.
 */
class ClientEvent extends Event
{
    const REFRESH = 'refresh';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
