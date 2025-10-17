<?php

declare(strict_types=1);

namespace Clear\Template;

use function json_encode;

/**
 * Fake Template
 * Parses all assigned variables as JSON string
 * Usually used in Unit Tests
 */
class FakeTemplate implements TemplateInterface
{
    /**
     * To check witch file is loaded
     *
     * @var string
     */
    public $loadedTemplate;

    /**
     * For check what variable data was given to template
     *
     * @var array
     */
    public $container = [];

    /**
     * {@inheritDoc}
     */
    public function load(string $name): self
    {
        $this->loadedTemplate = $name;
        $this->container = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function assign(string $key, $value): self
    {
        $this->container[$key] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(): string
    {
        return json_encode($this->container);
    }

    /**
     * {@inheritDoc}
     */
    public function registerFunction(string $name, callable $function): self
    {
        return $this;
    }
}
