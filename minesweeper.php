#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Minesweeper\Command\MinesweeperCommand;
use Symfony\Component\Console\Application;

$command = new MinesweeperCommand();

$application = new Application('minesweeper', '1.0.0');
$application->add($command);
$application->setDefaultCommand($command->getName(), true);

try {
    $application->run();
} catch (Exception $e) {
    echo 'Command execution failure: ' . $e->getMessage();
}
