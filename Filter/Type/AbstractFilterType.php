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

use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Generic filter type
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
abstract class AbstractFilterType implements FilterTypeInterface
{
    public function __construct(
        protected string $name,
        protected string $formType,
        protected array $formOptions = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFormType(QueryHandlerInterface $queryHandler, FilterInterface $filter): string
    {
        return $this->formType;
    }

    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        return array_merge(
            $this->formOptions,
            $filter->getFormOptions()
        );
    }
}
