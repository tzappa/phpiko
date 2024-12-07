<?php

declare(strict_types=1);

namespace Clear\Config;

/**
 * Repository Interface defines methods for a configuration reader.
 */
interface ConfigInterface
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get the specified configuration value. Return $default if there is such element.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);
}
