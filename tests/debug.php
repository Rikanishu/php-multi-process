<?php

require_once (__DIR__ . '/../vendor/autoload.php');

$cmd1 = ['sleep 2; echo', '"Hello World"'];
$cmd2 = ['sleep 4; echo', '"Second Command'];


$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2]);
$pool->setDebugEnabled(true);
$pool->run();

foreach ($pool->getCommands() as $cmd) {
    print_r($cmd->getExecutionResult());
}