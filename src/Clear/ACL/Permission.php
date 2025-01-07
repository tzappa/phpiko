<?php

declare(strict_types=1);

namespace Clear\ACL;

use InvalidArgumentException;

/**
 * ACL Permission Class.
 * It consists of two parameters - operation and the object to which that operation is applicable.
 * It has an ID also.
 *
 * @example Operation "Edit" and the object "User"
 */
final class Permission implements PermissionInterface
{
    private $id;
    private $object;
    private $operation;

    public function __construct(int $id, string $object, string $operation)
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Permission ID must be positive');
        }
        $object = trim($object);
        if ($object === '') {
            throw new InvalidArgumentException('Object cannot be empty');
        }
        $operation = trim($operation);
        if ($operation === '') {
            throw new InvalidArgumentException('Operation cannot be empty');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $object)) {
            throw new InvalidArgumentException('Object contains invalid characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $operation)) {
            throw new InvalidArgumentException('Operation contains invalid characters');
        }
        $this->id = $id;
        $this->object = $object;
        $this->operation = $operation;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
