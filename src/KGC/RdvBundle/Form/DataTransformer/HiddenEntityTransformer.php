<?php

// src/KGC/RdvBundle/Form/DataTransformer/HiddenEentityTransformer.php
namespace KGC\RdvBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class HiddenEntityTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $repo;

    /**
     * @var string
     */
    private $field;

    /**
     * @param EntityManager $em
     */
    public function __construct($repo, $field = 'id')
    {
        $this->repo = $repo;
        $this->field = $field;
    }

    /**
     * Transforms an entity (issue) to his id.
     *
     * @param Entity|null $object
     *
     * @return int
     */
    public function transform($object)
    {
        if (null === $object) {
            return '';
        }
        $method = 'get'.ucfirst($this->field);

        return $object->$method();
    }

    /**
     * Transforms an int (id) to the wanted object.
     *
     * @param int $id
     *
     * @return Entity|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }
        $object = $this->repo->findOneBy([$this->field => $id]);

        if (null === $object) {
            throw new TransformationFailedException(sprintf(
                'Erreur de transformation de lʼidentifiant "%s%" en objet : '.get_class($this->repo),
                $id
            ));
        }

        return $object;
    }
}
