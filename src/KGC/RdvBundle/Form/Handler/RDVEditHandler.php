<?php

// src/KGC/RdvBundle/Form/Handler/RDVEditHandler.php


namespace KGC\RdvBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * RDVEditHandler.
 *
 * Traitement du formulaire d'accès aux fonctions d'édition du RDV
 *
 * @category Form/Handler
 *
 * @author Laurene Dourdin <2aurene@gmail.com>
 */
class RDVEditHandler
{
    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var Symfony\Component\Form\Form
     */
    protected $form;

    /**
     * Constructeur.
     *
     *
     * @param Symfony\Component\Form\Form              $form    formulaire à traiter
     * @param Symfony\Component\HttpFoundation\Request $request requête http
     * @param Doctrine\Common\Persistence\AbstractManagerRegistry entity manager
     */
    public function __construct(Form $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

    /**
     * Méthode process : traitement, renvoi le tableau de configuration du form RDV.
     *
     *
     * @return array tableau de configuration du form RDV
     */
    public function process()
    {
        $params = array();
        $this->form->handleRequest($this->request);
        foreach ($this->form->all() as $field) {
            if ($field->getData()) {
                $params[$field->getName()] = $field->getConfig()->getOption('value');
            }
        }

        return $params;
    }
}
