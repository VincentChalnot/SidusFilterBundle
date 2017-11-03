<?php

namespace Sidus\FilterBundle\Factory;

use Sidus\FilterBundle\Filter\FilterInterface;

/**
 * Base logic for all filter factories
 */
interface FilterFactoryInterface
{
    /**
     * @param string $code
     * @param array  $configuration
     *
     * @return FilterInterface
     */
    public function createFilter(string $code, array $configuration): FilterInterface;

    /**
     * @return string
     */
    public function getProvider(): string;
}
