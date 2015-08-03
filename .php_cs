<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()->in(__DIR__.'/src/');

return Symfony\CS\Config\Config::create()
    ->fixers([
        'syfmony',
        '-phpdoc_params',
        '-empty_return',
        '-phpdoc_separation',
        '-linefeed',
    ])
    ->finder($finder)
;
