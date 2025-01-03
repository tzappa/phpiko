<?php

declare(strict_types=1);

namespace Clear\Container;

use ArrayAccess;
use SplObjectStorage;
use Psr\Container\ContainerInterface;

/**
 * Container that implements PSR-11 ContainerInterface
 */
final class Container implements ArrayAccess, ContainerInterface
{
    /**
     * Table of Definitions
     */
    protected $definitions = array();

    /**
     * Table of returned objects
     */
    protected $objects = array();

    /**
     * Table of generated objects
     */
    protected $calcs = array();

    /**
     * Table of all closures that should always return fresh objects.
     */
    protected $factories;

    /**
     * Table of closures that get() method should always return raw results
     */
    protected $raws;

    /**
     * Table of locked keys that cannot be overridden and deleted
     */
    protected $locks = array();

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
     * @param string $name  Parameter name
     * @param mixed  $value The value to be assigned for the parameter
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Parameter name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Parameter name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Property overloading magic method.
     *
     * @param string $name Parameter name
     */
    public function __unset($name)
    {
        $this->delete($name);
    }

    /**
     * Sets a parameter defined in an unique key ID.
     * You can set objects as a closures.
     *
     * @param string $id    Key Name
     * @param mixed  $value Value or closure function
     */
    public function set($id, $value)
    {
        if (!empty($this->locks[$id])) {
            throw new ContainerException("Cannot override locked key {$id}");
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
     * @throws \Clear\Container\NotFoundException  No entry was found for this identifier.
     * @throws \Clear\Container\ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("No entry was found for the identifier '{$id}'");
        }

        if (is_object($this->definitions[$id]) && method_exists($this->definitions[$id], '__invoke')) {
            if (isset($this->raws[$this->definitions[$id]])) {
                return $this->definitions[$id];
            }

            if (isset($this->factories[$this->definitions[$id]])) {
                return $this->definitions[$id]();
            }

            if ($this->calcs[$id]) {
                return $this->objects[$id];
            }
            $obj = $this->definitions[$id]();
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
    public function factory($idOrClosure)
    {
        if (is_object($idOrClosure) && method_exists($idOrClosure, '__invoke')) {
            $this->factories->attach($idOrClosure);

            return $idOrClosure;
        }

        if (!isset($this->definitions[$idOrClosure])) {
            return null;
        }

        if (is_object($this->definitions[$idOrClosure]) && method_exists($this->definitions[$idOrClosure], '__invoke')) {
            return $this->definitions[$idOrClosure]($this);
        }

        return $this->definitions[$idOrClosure];
    }

    /**
     * Returns a raw definition. Used when a closure is set and
     * you want to get the closure not the result of it.
     *
     * @param mixed $idOrClosure
     *
     * @return mixed Returns whatever it is stored in the key. NULL if
     * nothing is stored.
     */
    public function raw($idOrClosure)
    {
        if (is_object($idOrClosure) and method_exists($idOrClosure, '__invoke')) {
            $this->raws->attach($idOrClosure);

            return $idOrClosure;
        }

        if (!isset($this->definitions[$idOrClosure])) {
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
     * @param string $id
     */
    public function delete($id)
    {
        if (!empty($this->locks[$id])) {
            throw new ContainerException("Cannot delete locked key {$id}");
        }
        if (is_object($this->definitions[$id])) {
            unset($this->factories[$this->definitions[$id]]);
        }
        unset($this->definitions[$id], $this->objects[$id], $this->calcs[$id]);
    }

    /**
     * Lock the key, so it cannot be overwritten.
     * Note that there is no unlock method and will never be!
     *
     * @param string $id
     *
     * @return mixed
     */
    public function lock($id)
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
        $this->set($id, $value);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see get() method
     */
    public function offsetGet(mixed $id): mixed
    {
        return $this->get($id);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see has() method
     */
    public function offsetExists(mixed $id): bool
    {
        return $this->has($id);
    }

    /**
     * Method is needed to implement \ArrayAccess.
     *
     * @see delete() method
     */
    public function offsetUnset(mixed $id): void
    {
        $this->delete($id);
    }
}
