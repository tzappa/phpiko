<?php

declare(strict_types=1);

namespace Clear\Config;

use function is_array;
use function is_file;
use function pathinfo;

final class Factory
{
    public static function create($data): ConfigInterface
    {
        if (is_array($data)) {
            return new DotConfig($data);
        }

        if (is_file($data)) {
            $pathinfo = pathinfo($data);

            if (!isset($pathinfo['extension'])) {
                throw new Exception\ConfigException('File w/o extension cannot be auto loaded');
            }

            $ext = $pathinfo['extension'];

            if ('ini' === $ext) {
                $parser = new Parser\Ini();
            } elseif ('json' === $ext) {
                $parser = new Parser\Json();
            } else {
                throw new Exception\ConfigException("Parser unavailable for file with extension {$ext}");
            }
            $arr = $parser->fromFile($data);

            return new DotConfig($arr);
        }

        throw new Exception\ConfigException('Unavailable file');
    }
}
