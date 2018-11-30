<?php

namespace KGC\PaymentBundle\Payum\Be2Bill;

use Payum\Be2Bill\Action\NotifyNullAction;
use Payum\Be2Bill\Action\NotifyAction;
use Payum\Be2Bill\Be2BillDirectGatewayFactory as Be2BillBaseGatewayFactory;
use Payum\Core\Bridge\Spl\ArrayObject;

class Be2BillDirectGatewayFactory extends Be2BillBaseGatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        parent::populateConfig($config);

        $config->defaults(array(
            'payum.factory_name' => 'be2bill_direct_zol',
            'payum.factory_title' => 'Be2Bill Direct Zol',

            'payum.action.notify_null' => new NotifyNullAction(),
            'payum.action.notify' => new NotifyAction(),
        ));
    }
}
