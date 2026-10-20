<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Sidus\FilterBundle\Criterion\Equals;
use Sidus\FilterBundle\Provider\CriterionCompilerInterface;

final class UninstantiableCompiler implements CriterionCompilerInterface
{
    public function __construct()
    {
        throw new \LogicException('must not be instantiated');
    }

    public static function getProvider(): string
    {
        return 'x';
    }

    public static function getCriterionClass(): string
    {
        return Equals::class;
    }
}
