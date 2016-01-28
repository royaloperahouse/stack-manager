<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

$loader = require __DIR__ . '/../vendor/autoload.php';

/**
 * @var ClassLoader $loader
 */
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
