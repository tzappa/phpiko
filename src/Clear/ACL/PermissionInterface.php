<?php

declare(strict_types=1);

namespace Clear\ACL;

/**
 * ACL Permission Interface.
 * It consists of two parameters - operation and the object to which that operation is applicable.
 * It has an ID also.
 *
 * @example Operation "Edit" and the object "User"
 */
interface PermissionInterface
{
    /**
     * Get permission's ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * The name of the object for which the permission is
     *
     * @return string
     */
    public function getObject(): string;

    /**
     * Operation that can be performed on that object
     *
     * @return string
     */
    public function getOperation(): string;
}
