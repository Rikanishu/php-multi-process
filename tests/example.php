<?php

require_once (__DIR__ . '/../vendor/autoload.php');


$cmd1 = ['echo', '"Some Command"'];
$cmd2 = 'echo "Another Command"';
$cmd3 = ['echo "$SOME_ENV_VAR" "$PWD"', [
    rikanishu\multiprocess\Command::OPTION_CWD => '/tmp',
    rikanishu\multiprocess\Command::OPTION_ENV => [
        'SOME_ENV_VAR' => 'PWD is:'
    ],
]];
$cmd4 = new rikanishu\multiprocess\Command('cat');
$cmd4->setStdin('hello world');

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3, $cmd4]);
$pool->run();

/* @var $command rikanishu\multiprocess\Command */
foreach ($pool->getCommands() as $command) {
    $res = $command->getExecutionResult();
    echo $res->getExitCode() . " | " . $res->getStdout() . " | " . $res->getStderr() . "\n";
}

/*  Output:
    0 | Some Command |
    0 | Another Command |
    0 | PWD is: /tmp |
    0 | hello world |
*/

/* If you haven't checked isExecuted and get the execution result for non-executed command,
   NonExecutedException will be raised */

$commands = $pool->getCommands();
$commands[0]->getExecutionResult()->getOutput(); // Some Command
$commands[1]->getExecutionResult()->getOutput(); // Another Command
$commands[2]->getExecutionResult()->getOutput(); // PWD is: /tmp
$commands[3]->getExecutionResult()->getOutput(); // hello world

/* And also library provides single command execution */
$command = new rikanishu\multiprocess\Command('echo "Hello World!"');
$command->runBlocking()->getOutput(); // Hello World