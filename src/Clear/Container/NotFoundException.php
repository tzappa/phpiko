<?php

declare(strict_types=1);

namespace Clear\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Container NotFound Exception that implements PSR-11 NotFoundExceptionInterface
 */
final class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
