<?php declare(strict_types=1);
/**
 * Twig template engine wrapper
 *
 * @package PHPiko
 */

namespace PHPiko\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\Extension\DebugExtension;

use function str_ends_with;

final class TwigTemplate implements TemplateInterface
{
    /**
     * Template engine
     *
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * Twig template object
     *
     * @var \Twig\Template
     */
    private $tpl;

    /**
     * Registry for all parameters to be injected in Twig on parse
     *
     * @var array
     */
    private $registry = [];

    /**
     * Template file extension
     *
     * @var string
     */
    private $extension = '.twig';

    /**
     * Constructor
     *
     * @param string        $path        Template search path
     * @param string|false  $cachePath   The place where twig cache files should be stored
     * @param bool          $debugMode   Set to true in dev environment to ease development
     * @param bool|null     $autoReload  Whether to enable automatic template recompilation; null defaults to $debugMode
     */
    public function __construct(string $path, string|false $cachePath, bool $debugMode, ?bool $autoReload = null)
    {
        $loader = new FilesystemLoader($path);
        $this->twig = new Environment($loader, [
            'debug'       => $debugMode,
            'cache'       => $cachePath,
            'auto_reload' => is_null($autoReload) ? $debugMode : $autoReload,
        ]);

        if ($debugMode) {
            // to var_dump($arr) in Twig:
            $this->twig->addExtension(new DebugExtension());
            // NOTE: debug must be true to enable this extension
            // {{ dump(arr) }}
        }
    }

    /**
     * Configure the template file extension
     * 
     * @param string $extension The file extension to use for templates (e.g., '.twig')
     * 
     * @return self Returns the current instance for method chaining
     */
    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $name): self
    {
        // check the name for the extension
        if (!str_ends_with($name, $this->extension)) {
            $name .= $this->extension;
        }
        $this->tpl = $this->twig->load($name);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function assign(string $key, $value): self
    {
        $this->registry[$key] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(): string
    {
        return $this->tpl->render($this->registry);
    }

    /**
     * {@inheritDoc}
     */
    public function registerFunction(string $name, callable $callback): self
    {
        $this->twig->addFunction(new TwigFunction($name, $callback));

        return $this;
    }
}
