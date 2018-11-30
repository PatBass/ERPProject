<?php

namespace KGC\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildPaymentFactoryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('kgc.payment.factory');

        $taggedServices = $container->findTaggedServiceIds('kgc.payment.gateway');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'add',
                [new Reference($id)]
            );
        }
    }
}
