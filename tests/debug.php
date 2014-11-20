<?php

require_once (__DIR__ . '/../vendor/autoload.php');

$cmd1 = ['echo', '"Hello World"'];
$cmd2 = ['echo', '"Second Command'];


$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2]);
$pool->setDebugEnabled(true);
$pool->run();

foreach ($pool->getExecutedCommands() as $cmd) {
    print_r($cmd->getExecutionResult());
}