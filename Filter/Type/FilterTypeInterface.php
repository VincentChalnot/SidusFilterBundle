<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Filter\Type;

use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Base logic common to all filter types
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
#[AutoconfigureTag('sidus.filter_type')]
interface FilterTypeInterface
{
    public function getName(): string;

    public function getFormType(QueryHandlerInterface $queryHandler, FilterInterface $filter): string;

    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void;

    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array;

    public static function getProvider(): string;
}
