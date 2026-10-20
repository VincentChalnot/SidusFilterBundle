<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Exception;

/**
 * A list definition that cannot work: raised at container compile time (or cache warm-up), never on a request.
 */
final class ConfigurationException extends \LogicException
{
}
