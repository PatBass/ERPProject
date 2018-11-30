<?php
/**
 * Created by PhpStorm.
 * User: niko
 * Date: 17/08/2016
 * Time: 11:55
 */

namespace KGC\ClientBundle\Elastic\Form;

use JMS\DiExtraBundle\Annotation as DI;
use JMS\Serializer\SerializerInterface;
use KGC\ClientBundle\Elastic\FormPersister\ClientSearchFormPersisterInterface;

/**
 * Class ClientSearchFormSaver.
 *
 * @DI\Service("kgc.elastic.client.form_saver")
 */
class ClientSearchFormSaver
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var ClientSearchFormPersisterInterface
     */
    protected $formPersister;

    /**
     * @param ClientSearchFormPersisterInterface $formPersister
     * @param SerializerInterface $serializer
     *
     * @DI\InjectParams({
     *      "formPersister" = @DI\Inject("kgc.elastic.client.session.form_persister"),
     *      "serializer" = @DI\Inject("jms_serializer"),
     * })
     */
    public function __construct(ClientSearchFormPersisterInterface $formPersister, SerializerInterface $serializer)
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
                $entityFQCN ?: 'KGC\ClientBundle\Elastic\Model\ClientSearch',
                'json'
            );
        }

        return;
    }
}