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

use Symfony\Component\Form\Util\FormUtil;
use Nelmio\Alice\ORMInterface;
use Nelmio\Alice\ReaderInterface;

/**
 * Loads fixtures from an array or php file
 *
 * The php code if $data is a file has access to $loader->fake() to
 * generate data and must return an array of the format below.
 *
 * The array format must follow this example:
 *
 *     array(
 *         'Namespace\Class' => array(
 *             'name' => array(
 *                 'property' => 'value',
 *                 'property2' => 'value',
 *             ),
 *             'name2' => array(
 *                 [...]
 *             ),
 *         ),
 *     )
 */
class Base implements ReaderInterface
{
    public function readFixtures($data) {
        if (!is_array($data)) {
            // $loader is defined to give access to $loader->fake() in the included file's context
            $loader = $this;
            $filename = $data;
            $includeWrapper = function () use ($filename, $loader) {
                ob_start();
                $res = include $filename;
                ob_end_clean();

                return $res;
            };
	    put_file_contents("php://stderr", "Loading fixtures files...\n");
            $data = $includeWrapper();
            if (!is_array($data)) {
                throw new \UnexpectedValueException('Included file "'.$filename.'" must return an array of data');
            }
        }
	return $data;
    }
}
