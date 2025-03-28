<?php 

declare(strict_types=1);

namespace Clear\Template;

/**
 * Template Interface.
 * Used to make wrappers around different template engines like Twig, Smarty or even STE (Simplete) or plain PHP (HTML) files
 * Only basic but mandatory methods are defined
 */
interface TemplateInterface
{
    /**
     * Loads the template with a given name.
     * The name MAY not include the path and SHOULD not include the extension.
     * This way different template engines can be used - .twig, .html (for smarty), .php
     *
     * @var string $name
     * @return self Returns the current instance for method chaining
     * @throws \RuntimeException If the template file is not found
     */
    public function load(string $name): self;

    /**
     * Sets a dynamic variable to be used in the template
     *
     * @param string $key
     * @param mixed $value
     * @return self Returns the current instance for method chaining
     */
    public function assign(string $key, $value): self;

    /**
     * Parses the template and returns the content.
     *
     * @return string
     */
    public function parse(): string;

    /**
     * Adds a custom function to the template engine defined by name.
     *
     * @param string $name
     * @param callable $function
     * @return self Returns the current instance for method chaining
     */
    public function registerFunction(string $name, callable $function): self;
}
