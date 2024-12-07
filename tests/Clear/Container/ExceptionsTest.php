<?php

declare(strict_types=1);


namespace Tests\Clear\Container;

use Clear\Container\ContainerException;
use Clear\Container\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Tests PSR-11 exceptions
 */
class ExceptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testContainerExceptionImplementsPsrContainerExceptionInterface()
    {
        $this->assertTrue(new ContainerException instanceof ContainerExceptionInterface);
    }

    public function testNotFoundExceptionImplementsPsrContainerNotFoundExceptionInterface()
    {
        $this->assertTrue(new NotFoundException instanceof NotFoundExceptionInterface);
    }

    public function testNotFoundExceptionExtendsContainerException()
    {
        $this->assertTrue(new NotFoundException instanceof ContainerException);
    }
}
