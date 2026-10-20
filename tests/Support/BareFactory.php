<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A provider without any criterion compiler: every filter is refused at compile time.
 */
class BareFactory implements QueryHandlerFactoryInterface
{
    public static function getProvider(): string
    {
        return 'bare';
    }

    public static function configureListOptions(OptionsResolver $resolver): void
    {
    }

    public static function configureFilterOptions(OptionsResolver $resolver): void
    {
    }

    public function createQueryHandler(ListDefinition $definition, FilterTypeRegistry $filterTypes, ?CountStrategyInterface $countStrategy): QueryHandlerInterface
    {
        throw new \LogicException('not used');
    }
}
