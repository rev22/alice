<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Loader;

use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Loads fixtures from a yaml file
 *
 * The yaml file can contain PHP which will be executed before it is parsed as yaml.
 * PHP in the yaml file has access to $loader->fake() to generate data
 *
 * The general format of the file must follow this example:
 *
 *     Namespace\Class:
 *         name:
 *             property: value
 *             property2: value
 *         name2:
 *             [...]
 */
class Yaml extends Base
{
    /**
     * {@inheritDoc}
     */
    public function readFixtures($file, $nophp = false)
    {
	if ($nophp) {
	    file_put_contents("/dev/stderr", "Loading YAML fixture files...\n");
	    $yaml = file_get_contents($file);
            return YamlParser::parse($yaml);
	    // return $data;
	}

        ob_start();
        $loader = $this;
        $includeWrapper = function () use ($file, $loader) {
            return include $file;
        };
        $data = $includeWrapper();
        if (true !== $data) {
            $yaml = ob_get_clean();
	    file_put_contents("/dev/stderr", "Loading YAML fixture files...\n");
            $data = YamlParser::parse($yaml);
        }

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Yaml files must parse to an array of data');
        }

        return $data;
    }
}
