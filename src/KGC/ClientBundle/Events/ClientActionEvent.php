<?php

// src/KGC/ClientBundle/Events/ClientActionEvent.php
namespace KGC\ClientBundle\Events;

use KGC\Bundle\SharedBundle\Entity\Client;
use KGC\Bundle\SharedBundle\Entity\Website;
use KGC\UserBundle\Entity\Utilisateur;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Form;

/**
 * ClientActionEvent
 * Evènement d'action sur client.
 *
 * @category Events
 *
 * @author Laurène Dourdin <2aurene@gmail.com>
 */
class ClientActionEvent extends Event
{
    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Client
     */
    protected $client;

    /**
     * @var \KGC\Bundle\SharedBundle\Entity\Website
     */
    protected $website;

    /**
     * @var Symfony\Component\Form\Form
     */
    protected $form;

    /**
     * @var \KGC\UserBundle\Entity\Utilisateur
     */
    protected $user;

    /**
     * @param Client $client
     * @param Utilisateur $user
     * @param Form|null   $form
     */
    public function __construct(Client $client, Utilisateur $user, Form $form = null, $website = null)
    {
        $this->client = $client;
        $this->form = $form;
        $this->user = $user;
        $this->website = $website;
    }

    /**
     * @return \KGC\Bundle\SharedBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return \KGC\Bundle\SharedBundle\Entity\Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Symfony\Component\Form\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param \KGC\Bundle\SharedBundle\Entity\Client $client
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Client $client
     */
    public function setClient(Client $client)
    {
        return $this->client = $client;
    }
    /**
     * @param \KGC\Bundle\SharedBundle\Entity\Website $website
     *
     * @return \KGC\Bundle\SharedBundle\Entity\Website $website
     */
    public function setWebsite(Website $website)
    {
        return $this->website = $website;
    }

    /**
     * @return Utilisateur
     */
    public function getUser()
    {
        return $this->user;
    }
}
