<?php

require_once (__DIR__ . '/../vendor/autoload.php');

$cmd1 = ['sleep 2; echo', '"Hello World"'];
$cmd2 = ['sleep 4; echo', '"Second Command'];
$cmd3 = new \rikanishu\multiprocess\Command('cat');
$cmd3->setStdin("Something");


$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
$pool->setDebugEnabled(true);
$pool->run();

foreach ($pool->getCommands() as $cmd) {
    print_r($cmd->getExecutionResult());
}