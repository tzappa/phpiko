<?php

declare(strict_types=1);

namespace Clear\ACL;

use ArrayObject;
use InvalidArgumentException;

/**
 * ACL Permission Collection Class.
 * A store for one or more Permissions
 */
final class PermissionCollection extends ArrayObject
{
    public function __construct($input = array(), int $flags = 0, string $iteratorClass = 'ArrayIterator')
    {
        foreach ($input as $permission) {
            if (!($permission instanceof PermissionInterface)) {
                throw new InvalidArgumentException('Object implementing PermissionInterface expected');
            }
        }
        parent::__construct($input, $flags, $iteratorClass);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        if (!($value instanceof PermissionInterface)) {
            throw new InvalidArgumentException('Object implementing PermissionInterface expected');
        }

        parent::offsetSet($key, $value);
    }
}
