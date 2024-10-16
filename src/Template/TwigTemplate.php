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
     * Constructor
     *
     * @var string $path Template search path
     * @var string $cachePath The place where twig cache files should be stored
     * @var boolean $debugMode Set to true in dev environment to ease development
     */
    public function __construct(string $path, $cachePath, bool $debugMode, ?bool $autoReload = null)
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
     * @inheritdoc
     */
    public function load(string $name): self
    {
        $this->tpl = $this->twig->load($name.'.twig');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function assign(string $key, $value): self
    {
        $this->registry[$key] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function parse(): string
    {
        return $this->tpl->render($this->registry);
    }

    /**
     * @inheritdoc
     */
    public function registerFunction(string $name, callable $callback): self
    {
        $this->twig->addFunction(new TwigFunction($name, $callback));

        return $this;
    }
}
