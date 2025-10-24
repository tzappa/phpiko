<?php

declare(strict_types=1);

namespace Clear\Container;

use ArrayAccess;
use SplObjectStorage;
use Psr\Container\ContainerInterface;

/**
 * Container that implements PSR-11 ContainerInterface
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Container implements ArrayAccess, ContainerInterface
{
    /**
     * Table of Definitions
     *
     * @var array<string, mixed>
     */
    protected array $definitions = [];

    /**
     * Table of returned objects
     *
     * @var array<string, mixed>
     */
    protected array $objects = [];

    /**
     * Table of generated objects
     *
     * @var array<string, bool>
     */
    protected array $calcs = [];

    /**
     * Table of all closures that should always return fresh objects.
     *
     * @var SplObjectStorage<object, mixed>
     */
    protected SplObjectStorage $factories;

    /**
     * Table of closures that get() method should always return raw results
     *
     * @var SplObjectStorage<object, mixed>
     */
    protected SplObjectStorage $raws;

    /**
     * Table of locked keys that cannot be overridden and deleted
     *
     * @var array<string, bool>
     */
    protected array $locks = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->factories = new SplObjectStorage();
        $this->raws = new SplObjectStorage();
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name  Key Name
     * @param mixed  $value The value to be assigned for the key
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Key Name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Key Name
     *
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Key Name
     */
    public function __unset(string $name): void
    {
        $this->delete($name);
    }

    /**
     * Sets a parameter defined in an unique key ID.
     * You can set objects as a closures.
     *
     * @param string $id   Identifier of the entry to set
     * @param mixed $value Value or closure function
     *
     * @throws ContainerException If the key is locked
     */
    public function set(string $id, mixed $value): void
    {
        if (!empty($this->locks[$id])) {
            throw new ContainerException("Cannot override locked key '{$id}'");
        }
        $this->definitions[$id] = $value;
        $this->calcs[$id] = false;
        // unset on override
        unset($this->objects[$id]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     *
     * @return mixed Entry.
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("No entry was found for the identifier '{$id}'");
        }

        if (is_object($this->definitions[$id]) && method_exists($this->definitions[$id], '__invoke')) {
            $definition = $this->definitions[$id];
            if (isset($this->raws[$definition])) {
                return $definition;
            }

            if (isset($this->factories[$definition])) {
                return $definition();
            }

            if ($this->calcs[$id]) {
                return $this->objects[$id];
            }
            $obj = $definition();
            $this->objects[$id] = $obj;
            $this->calcs[$id] = true;

            return $obj;
        }

        return $this->definitions[$id];
    }

    /**
     * Gets or sets callable to return fresh objects.
     * If a callable is given, then it sets that the get() method always
     * to return new objects. If an string (key ID's) is given, then it
     * will return new object.
     *
     * @param mixed $idOrClosure
     *
     * @return mixed
     */
    public function factory(mixed $idOrClosure): mixed
    {
        if (is_object($idOrClosure) && method_exists($idOrClosure, '__invoke')) {
            $this->factories->attach($idOrClosure);

            return $idOrClosure;
        }

        if (!is_string($idOrClosure) || !isset($this->definitions[$idOrClosure])) {
            return null;
        }

        $definition = $this->definitions[$idOrClosure];
        if (
            is_object($definition)
            && method_exists($definition, '__invoke')
        ) {
            return $definition($this);
        }

        return $definition;
    }

    /**
     * Returns a raw definition. Used when a closure is set and
     * you want to get the closure not the result of it.
     *
     * @param mixed $idOrClosure Identifier or closure
     *
     * @return mixed
     */
    public function raw(mixed $idOrClosure): mixed
    {
        if (is_object($idOrClosure) and method_exists($idOrClosure, '__invoke')) {
            $this->raws->attach($idOrClosure);

            return $idOrClosure;
        }

        if (!is_string($idOrClosure) || !isset($this->definitions[$idOrClosure])) {
            return null;
        }

        return $this->definitions[$idOrClosure];
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $id Key name
     */
    public function delete(string $id): void
    {
        if (!empty($this->locks[$id])) {
            throw new ContainerException("Cannot delete locked key '{$id}'");
        }
        if (is_object($this->definitions[$id])) {
            $definition = $this->definitions[$id];
            unset($this->factories[$definition]);
        }
        unset($this->definitions[$id], $this->objects[$id], $this->calcs[$id]);
    }

    /**
     * Lock the key, so it cannot be overwritten.
     * Note that there is no unlock method and will never be!
     *
     * @param string $id Key name
     *
     * @return void
     */
    public function lock(string $id): void
    {
        $this->locks[$id] = true;
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see set() method
     */
    public function offsetSet(mixed $id, mixed $value): void
    {
        if (!is_string($id)) {
            throw new ContainerException('Key must be a string');
        }
        $this->set($id, $value);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see get() method
     */
    public function offsetGet(mixed $id): mixed
    {
        // @phpstan-ignore-next-line
        if (!is_string($id)) {
            throw new ContainerException('Key must be a string');
        }
        return $this->get($id);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see has() method
     */
    public function offsetExists(mixed $id): bool
    {
        // @phpstan-ignore-next-line
        if (!is_string($id)) {
            throw new ContainerException('Key must be a string');
        }
        return $this->has($id);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see delete() method
     */
    public function offsetUnset(mixed $id): void
    {
        // @phpstan-ignore-next-line
        if (!is_string($id)) {
            throw new ContainerException('Key must be a string');
        }
        $this->delete($id);
    }
}
