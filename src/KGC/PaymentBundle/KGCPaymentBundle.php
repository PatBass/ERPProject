<?php

namespace KGC\PaymentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zol\Payum\KlikAndPay\Bridge\Symfony\KlikAndPayGatewayFactory;
use Zol\Payum\Be2BillExtended\Bridge\Symfony\Be2BillExtendedGatewayFactory;
use KGC\PaymentBundle\DependencyInjection\Factory\Gateway\Be2BillDirectGatewayFactory;
use KGC\PaymentBundle\DependencyInjection\Compiler\BuildPaymentFactoryPass;

class KGCPaymentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var  PayumExtension $payumExtension */
        $payumExtension = $container->getExtension('payum');
        $payumExtension->addGatewayFactory(new Be2BillDirectGatewayFactory());
        $payumExtension->addGatewayFactory(new Be2BillExtendedGatewayFactory());
        $payumExtension->addGatewayFactory(new KlikAndPayGatewayFactory());

        $container->addCompilerPass(new BuildPaymentFactoryPass());
    }
}
