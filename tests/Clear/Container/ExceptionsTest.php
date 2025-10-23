<?php

declare(strict_types=1);

namespace Tests\Clear\Container;

use Clear\Container\ContainerException;
use Clear\Container\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Tests PSR-11 exceptions
 */
#[CoversClass(ContainerException::class)]
#[CoversClass(NotFoundException::class)]
class ExceptionsTest extends TestCase
{
    public function testContainerExceptionImplementsPsrContainerExceptionInterface()
    {
        $this->assertTrue(new ContainerException() instanceof ContainerExceptionInterface);
    }

    public function testNotFoundExceptionImplementsPsrContainerNotFoundExceptionInterface()
    {
        $this->assertTrue(new NotFoundException() instanceof NotFoundExceptionInterface);
    }

    public function testNotFoundExceptionExtendsContainerException()
    {
        $this->assertTrue(new NotFoundException() instanceof ContainerException);
    }
}
