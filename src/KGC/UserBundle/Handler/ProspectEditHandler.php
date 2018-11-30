<?php

namespace KGC\UserBundle\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * ProspectEditHandler.
 *
 * Traitement du formulaire d'accès aux fonctions d'édition du prospect
 *
 * @category Form/Handler
 *
 * @author Nicolas Mendez <nicolas.kgcom@gmail.com>
 */
class ProspectEditHandler
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
     * @param Symfony\Component\Form\Form $form formulaire à traiter
     * @param Symfony\Component\HttpFoundation\Request $request requête http
     * @param Doctrine\Common\Persistence\AbstractManagerRegistry entity manager
     */
    public function __construct(Form $form, Request $request)
    {
        $this->form = $form;
        $this->request = $request;
    }

    /**
     * Méthode process : traitement, renvoi le tableau de configuration du form prospect.
     *
     *
     * @return array tableau de configuration du form prospect
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
