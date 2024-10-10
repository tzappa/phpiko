<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko;

use PHPiko\Container\Container;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = new Container();
$app->set('config', require dirname(__DIR__) . '/config/config.php');

dump($app->config);
