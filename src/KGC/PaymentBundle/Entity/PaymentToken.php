<?php

namespace KGC\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Token;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="payment_token")
 * @ORM\Entity
 */
class PaymentToken extends Token
{
    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;
}
