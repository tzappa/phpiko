<?php declare(strict_types=1);
/**
 * Fake Template
 * Parses all assigned variables as JSON string
 * Usually used in Unit Tests
 *
 * @package PHPiko
 */

namespace PHPiko\Template;

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

    public function registerFunction(string $name, callable $function): self
    {
        return $this;
    }
}
