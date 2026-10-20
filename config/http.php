<?php

declare(strict_types=1);

use Sidus\FilterBundle\Http\ListInputValueResolver;
use Sidus\FilterBundle\Http\ListRequestParser;
use Sidus\FilterBundle\Http\ListResponseFactory;
use Sidus\FilterBundle\Http\ListResultNormalizer;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults()->autoconfigure();

    $services->set(ListRequestParser::class)->public()->args([
        param('sidus_filter.http.filters_key'),
        param('sidus_filter.http.sort_key'),
        param('sidus_filter.http.page_key'),
        param('sidus_filter.http.per_page_key'),
    ]);
    $services->set(ListResultNormalizer::class)->public();
    $services->set(ListResponseFactory::class)->public()->args([service(ListResultNormalizer::class), param('sidus_filter.http.strict')]);
    $services->set(ListInputValueResolver::class)->args([service(ListRequestParser::class), service(ListDefinitionRegistry::class)]);
};
