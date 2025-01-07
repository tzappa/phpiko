<?php

declare(strict_types=1);

namespace Clear\ACL;

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
