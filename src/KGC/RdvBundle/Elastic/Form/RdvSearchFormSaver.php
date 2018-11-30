<?php

namespace KGC\RdvBundle\Elastic\Form;

use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\SerializerInterface;
use KGC\RdvBundle\Elastic\FormPersister\RdvSearchFormPersisterInterface;

/**
 * Class RdvSearchFormSerializer.
 *
 * @DI\Service("kgc.elastic.rdv.form_saver")
 */
class RdvSearchFormSaver
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var RdvSearchFormPersisterInterface
     */
    protected $formPersister;

    /**
     * @param RdvSearchFormPersisterInterface $formPersister
     * @param SerializerInterface             $serializer
     *
     * @DI\InjectParams({
     *      "formPersister" = @DI\Inject("kgc.elastic.rdv.session.form_persister"),
     *      "serializer" = @DI\Inject("jms_serializer"),
     * })
     */
    public function __construct(RdvSearchFormPersisterInterface $formPersister, SerializerInterface $serializer)
    {
        $this->formPersister = $formPersister;
        $this->serializer = $serializer;
    }

    /**
     * @param $identifier
     * @param $search
     */
    public function save($identifier, $search)
    {
        $data = $this->serializer->serialize($search, 'json');
        $this->formPersister->persist($identifier, $data);
    }

    /**
     * @param $identifier
     * @param null $entityFQCN
     *
     * @return array|\JMS\Serializer\scalar|object|void
     */
    public function find($identifier, $entityFQCN = null)
    {
        $data = $this->formPersister->get($identifier);
        if (null !== $data) {
            return $this->serializer->deserialize(
                $data,
                $entityFQCN ?: 'KGC\RdvBundle\Elastic\Model\RdvSearch',
                'json'
            );
        }

        return;
    }
}
