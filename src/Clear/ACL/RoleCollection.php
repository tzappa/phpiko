<?php

declare(strict_types=1);

namespace Clear\ACL;

use ArrayObject;
use InvalidArgumentException;

/**
 * ACL Roles Collection Class.
 * A store for several roles.
 */
final class RoleCollection extends ArrayObject
{
    public function __construct(array $input = array(), int $flags = 0, string $iteratorClass = 'ArrayIterator')
    {
        foreach ($input as $role) {
            if (!($role instanceof RoleInterface)) {
                throw new InvalidArgumentException('Object implementing RoleInterface expected');
            }
        }
        parent::__construct($input, $flags, $iteratorClass);
    }

    public function offsetSet($key, mixed $value): void
    {
        if (!($value instanceof RoleInterface)) {
            throw new InvalidArgumentException('Object implementing RoleInterface expected');
        }

        parent::offsetSet($key, $value);
    }
}
