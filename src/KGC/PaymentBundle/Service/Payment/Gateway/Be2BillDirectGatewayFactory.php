<?php

namespace KGC\PaymentBundle\Service\Payment\Gateway;

use Payum\Be2Bill\Action\ConvertPaymentAction;
use Payum\Be2Bill\Action\CaptureAction;
use Payum\Be2Bill\Action\NotifyAction;
use Payum\Be2Bill\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class Be2BillDirectGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritdoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults(array(
            'payum.factory_name' => 'be2bill_direct_zol',
            'payum.factory_title' => 'Be2Bill Direct (Zol version)',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.notify' => new NotifyAction(),
        ));

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'identifier' => '',
                'password' => '',
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array('identifier', 'password');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    array(
                        'identifier' => $config['identifier'],
                        'password' => $config['password'],
                        'sandbox' => $config['sandbox'],
                    ),
                    $config['payum.http_client']
                );
            };
        }
    }
}
