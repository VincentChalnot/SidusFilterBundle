<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes the services of an optional adapter whose framework side is not enabled (symfony/form installed but the
 * form framework disabled), so that the container compiles without it.
 */
final class OptionalServicesPass implements CompilerPassInterface
{
    public const string FORM_ADAPTER_TAG = 'sidus_filter.form_adapter';

    public function process(ContainerBuilder $container): void
    {
        if ($container->has('form.factory')) {
            return;
        }
        foreach (array_keys($container->findTaggedServiceIds(self::FORM_ADAPTER_TAG)) as $id) {
            $container->removeDefinition($id);
        }
    }
}
