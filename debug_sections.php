<?php

require_once 'vendor/autoload.php';

use Clear\Config\Parser\Ini;

$parser = new Ini();
$iniString = <<<INI
[general]
key = value

[database]
type = mysql
port = 3306
name = clear

[api]
version = 1.1
log[enabled] = 1
log[level] = debug
INI;

$arr = $parser->fromString($iniString);
var_dump($arr);
var_dump(isset($arr['api']['log']['enabled']));
var_dump($arr['api']['log']['enabled'] ?? 'NOT SET');
