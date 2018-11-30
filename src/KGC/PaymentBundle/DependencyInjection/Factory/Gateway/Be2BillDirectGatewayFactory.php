<?php

namespace KGC\PaymentBundle\DependencyInjection\Factory\Gateway;

use Payum\Bundle\PayumBundle\DependencyInjection\Factory\Gateway\Be2BillDirectGatewayFactory as Be2BillBaseGatewayFactory;

class Be2BillDirectGatewayFactory extends Be2BillBaseGatewayFactory
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'be2bill_direct_zol';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPayumGatewayFactoryClass()
    {
        return 'KGC\PaymentBundle\Payum\Be2Bill\Be2BillDirectGatewayFactory';
    }
}
