<?php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();
$kernel = $GLOBALS['kernel'];
$application = new Application($kernel);
$application->run($input);
