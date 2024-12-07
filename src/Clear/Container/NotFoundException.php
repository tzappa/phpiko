<?php declare(strict_types=1);
/**
 * Container NotFound Exception that implements PSR-11 NotFoundExceptionInterface
 *
 * @package Clear
 */

namespace Clear\Container;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
