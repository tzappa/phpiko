<?php declare(strict_types=1);
/**
 * Container Exception that implements PSR-11 Container Exception Interface
 *
 * @package Clear
 */

namespace Clear\Container;

use Psr\Container\ContainerExceptionInterface;
use Exception;

class ContainerException extends Exception implements ContainerExceptionInterface
{
}
