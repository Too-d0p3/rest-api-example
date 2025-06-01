<?php

namespace App\DependencyInjection\Compiler;

use App\Shared\Security\EntityAccessPolicy;
use App\Shared\Security\PolicyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PolicyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PolicyRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(PolicyRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('app.policy');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['entity'])) {
                    throw new \LogicException("Missing 'entity' attribute for app.policy tag on $id.");
                }

                $entityClass = $attributes['entity'];
                $definition->addMethodCall('register', [$entityClass, new Reference($id)]);
            }
        }
    }
}
