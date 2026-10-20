<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Provider\CountStrategyInterface;
use Sidus\FilterBundle\Provider\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Provider\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class FakeFactory implements QueryHandlerFactoryInterface
{
    public static function getProvider(): string
    {
        return 'fake';
    }

    public static function configureListOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('entity');
        $resolver->setAllowedTypes('entity', 'string');
    }

    public static function configureFilterOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['case_insensitive' => 'lower']);
        $resolver->setAllowedValues('case_insensitive', ['lower', 'ilike']);
    }

    public function createQueryHandler(ListDefinition $definition, FilterTypeRegistry $filterTypes, ?CountStrategyInterface $countStrategy): QueryHandlerInterface
    {
        throw new \LogicException('not used');
    }
}
