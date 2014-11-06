<?php

return Symfony\CS\Config\Config::create()
    ->fixers(array('ordered_use'))
    ->finder(Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('Symfony/CS/Tests/Fixtures')
    ->notName('phar-stub.php')
    ->notName('ShortTagFixerTest.php')
    ->in(__DIR__)
);
