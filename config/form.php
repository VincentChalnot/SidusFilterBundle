<?php

declare(strict_types=1);

use Sidus\FilterBundle\Form\FormMapperRegistry;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Form\Mapper\ChoiceMapper;
use Sidus\FilterBundle\Form\Mapper\DateRangeMapper;
use Sidus\FilterBundle\Form\Mapper\ExactMapper;
use Sidus\FilterBundle\Form\Mapper\FlagMapper;
use Sidus\FilterBundle\Form\Mapper\NumberMapper;
use Sidus\FilterBundle\Form\Mapper\RegexpMapper;
use Sidus\FilterBundle\Form\Mapper\SortMapper;
use Sidus\FilterBundle\Form\Mapper\TextMapper;
use Sidus\FilterBundle\Form\Type\DateRangeType;
use Sidus\FilterBundle\Form\Type\ListFormType;
use Sidus\FilterBundle\Form\Type\OperatorValueType;
use Sidus\FilterBundle\Form\Type\SortType;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

// Loaded only when symfony/form is installed; removed again by OptionalServicesPass when the form framework is off.
return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autoconfigure()->tag('sidus_filter.form_adapter');

    foreach ([TextMapper::class, RegexpMapper::class, NumberMapper::class, ExactMapper::class, ChoiceMapper::class, DateRangeMapper::class, SortMapper::class] as $mapper) {
        $services->set($mapper);
    }
    foreach (['null', 'not_null', 'not_empty'] as $type) {
        $services->set('sidus_filter.form_mapper.'.$type, FlagMapper::class)->args([$type]);
    }

    $services->set(FormMapperRegistry::class)->args([tagged_iterator('sidus_filter.form_mapper')]);
    $services->set(OperatorValueType::class);
    $services->set(DateRangeType::class);
    $services->set(SortType::class);
    $services->set(ListFormType::class)->args([service(FormMapperRegistry::class), service(FilterTypeRegistry::class)]);
    $services->set(ListFormFactory::class)->public()->args([service('form.factory'), service(FormMapperRegistry::class)]);
};
