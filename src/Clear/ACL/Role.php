<?php

declare(strict_types=1);

namespace Clear\ACL;

/**
 * ACL Role Interface.
 * A Role consists of:
 *     - ID
 *     - Name
 *     - Permission(s)
 */
final class Role implements RoleInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Clear\ACL\PermissionCollection
     */
    private $permissions;

    /**
     * @var callable Callback function to fetch permissions in a role
     */
    private $discovery;

    /**
     * Role instance constructor.
     * Permissions list can be pre-fetched or can provide a
     * callback method to fetch them on demand.
     *
     * @param int $id
     * @param string $name
     * @param \Clear\ACL\PermissionCollection|callable|null $permissions
     * @throws \InvalidArgumentException if name is empty or permissions is invalid
     */
    public function __construct(int $id, string $name, PermissionCollection|callable|null $permissions)
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Role name cannot be empty');
        }
        $this->id = $id;
        $this->name = $name;
        // if the callback method was passed,
        // the permissions are not know for now
        if (is_callable($permissions)) {
            $this->discovery = $permissions;
            $permissions = null;
        }
        $this->permissions = $permissions;
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(string $name): RoleInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPermissions(): PermissionCollection
    {
        if (is_null($this->permissions) && $this->discovery) {
            $this->permissions = ($this->discovery)($this->id);
        }

        // fallback if no discovery, or discovery returns NULL
        if (is_null($this->permissions)) {
            $this->permissions = new PermissionCollection;
        }

        return $this->permissions;
    }
}
