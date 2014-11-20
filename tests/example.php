<?php

require_once (__DIR__ . '/../vendor/autoload.php');


$cmd1 = ['echo', '"Some Command"'];
$cmd2 = 'echo "Another Command"';
$cmd3 = ['echo "$SOME_ENV_VAR" "$PWD"', [
    rikanishu\multiprocess\Command ::OPTION_CWD => '/tmp',
    rikanishu\multiprocess\Command ::OPTION_ENV =>  [
        'SOME_ENV_VAR' => 'PWD is:'
    ],
]];

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
$pool->run();

/* @var $command rikanishu\multiprocess\Command */
foreach ($pool->getCommands() as $command) {
    if ($command->isExecuted()) {
        $res = $command->getExecutionResult();
        echo $res->getExitCode() . " | " . $res->getStdout() . " | " . $res->getStderr() . "\n";
    }
}

/*  Output:
    0 | Some Command |
    0 | Another Command |
    0 | PWD is: /tmp |
*/

/* Or you just can use getExecutedCommands method instead of checking */
foreach ($pool->getExecutedCommands() as $command) {
    $res = $command->getExecutionResult();
    echo $res->getExitCode() . " | " . $res->getStdout() . " | " . $res->getStderr() . "\n";
}

/* If you haven't checked isExecuted and get the execution result for non-executed command,
   NonExecutedException will be raised */

$commands = $pool->getCommands();
$commands[0]->getExecutionResult()->getOutput(); // Some Command
$commands[1]->getExecutionResult()->getOutput(); // Another Command
$commands[2]->getExecutionResult()->getOutput(); // PWD is: /tmp