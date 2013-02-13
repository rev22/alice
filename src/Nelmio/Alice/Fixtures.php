<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice;

use Doctrine\Common\Persistence\ObjectManager;

class Fixtures
{
    private static $readers = array();

    /**
     * Loads a fixture file into an object container
     *
     * @param string|array $file      filename, glob mask (e.g. *.yml) or array of filenames to load data from, or data array
     * @param object       $container object container
     * @param array        $options   available options:
     *                                - providers: an array of additional faker providers
     *                                - locale: the faker locale
     *                                - seed: a seed to make sure faker generates data consistently across
     *                                  runs, set to null to disable
     *                                - clear: clears the tables for the objects before loading them
     *                                - nophp: do not allow php in yaml files
     */
    public static function load($files, $container, array $options = array())
    {
        $defaults = array(
            'locale' => 'en_US',
            'providers' => array(),
            'seed' => 1,
	    'clear' => false,
	    'nophp' => false
        );
        $options = array_merge($defaults, $options);

        if ($container instanceof ObjectManager) {
            $persister = new ORM\Doctrine($container);
        } else {
            throw new \InvalidArgumentException('Unknown container type '.get_class($container));
        }

        // glob strings to filenames
        if (!is_array($files)) {
            $files = glob($files);
        }

        // wrap the data array in an array of one data array
        if (!is_string(current($files))) {
            $files = array($files);
        }

        $objects = array();
        foreach ($files as $file) {
            if (is_string($file) && preg_match('{\.ya?ml(\.php)?$}', $file)) {
                $reader = self::getReader('Yaml', $options);
            } elseif ((is_string($file) && preg_match('{\.php$}', $file)) || is_array($file)) {
                $reader = self::getReader('Base', $options);
            } else {
                throw new \InvalidArgumentException('Unknown file/data type: '.gettype($file).' ('.json_encode($file).')');
            }

            $l = $reader->readFixtures($file, array('nophp' => $options['nophp']));

	    foreach ($l as $class => $instances) {
		foreach ($instances as $name => $spec) {
		    if (array_key_exists($class, $objects)) {
			if (array_key_exists($name, $objects[$class])) {
			    throw new \Exception("$file: object $name already defined");
			}
		    } else {
			$objects[$class] = array();
		    }
		    $objects[$class][$name] = $spec;
		}
	    }
        }

	$objects = (new \Nelmio\Alice\Loader\Creator($persister))->create($objects);

        if ($options["clear"]) {
            $classes;
            foreach ($objects as $object) { $classes[get_class($object)] = 1; };
            // $output->writeln('Deleting current database objects for classes: ' . implode(', ', array_keys($classes)));
            foreach (array_keys($classes) as $class) {
                // $manager->createQueryBuilder()->delete(array('u'))->from($class, 'u')->getQuery()->getResults();
                $container->createQueryBuilder()->delete($class)->getQuery()->execute();
            }
            // $manager->flush();
        }
        
        return $objects;
    }

    private static function getReader($class, $options)
    {
        if (!isset(self::$readers[$class])) {
            $fqcn = 'Nelmio\Alice\Loader\\'.$class;
            self::$readers[$class] = new $fqcn($options['locale'], $options['providers'], $options['seed']);
        }

        return self::$readers[$class];
    }
}
