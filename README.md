# php-multi-process #
PHP library for parallel shell commands execution
[![Build Status](https://travis-ci.org/Rikanishu/php-multi-process.svg?branch=master)](https://travis-ci.org/Rikanishu/php-multi-process)
-----

### Installation via composer: ###
Either run

```
php composer.phar require rikanishu/php-multi-process "*"
```

or add

```
"rikanishu/php-multi-process": "*"
```

to the ```require``` section of your ```composer.json``` file.

### Example: ###

```php
$cmd1 = ['echo', '"Some Command"'];
$cmd2 = 'echo "Another Command"';
$cmd3 = ['echo "$SOME_ENV_VAR" "$PWD"', [
    rikanishu\multiprocess\Command::OPTION_CWD => '/tmp',
    rikanishu\multiprocess\Command::OPTION_ENV =>  [
        'SOME_ENV_VAR' => 'PWD is:'
    ],
]];

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
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
*/

$commands = $pool->getCommands();
$commands[0]->getExecutionResult()->getOutput(); // Some Command
$commands[1]->getExecutionResult()->getOutput(); // Another Command
$commands[2]->getExecutionResult()->getOutput(); // PWD is: /tmp

/* And also library provides single command execution */
$command = new rikanishu\multiprocess\Command('echo "Hello World!"');
$command->runBlocking()->getOutput(); // Hello World
```

### Description ###

This library is designed to execute single / multiple parallel process on blocking / non-blocking mode. The library provides an interface for convenient configuring external process execution. The basic concepts are Pool and Command objects. Pool is a set of commands that creates and delegates execution to ExecutionContext, which build Process objects for each command and run it. Command object represents single external command.

The library uses proc_* API and select sys call provides by standard PHP library. It has no external depending.

#### Usage ####

General usage scenario suggests that you pass some commands to a Pool instance which will be executed parallel on blocking / non-blocking mode depend on params passed on. Here is example of non-blocking execution of three commands:

```php
$cmd1 = ['echo', '"Some Command"'];
$cmd2 = 'echo "Another Command"';
$cmd3 = ['echo "$SOME_ENV_VAR" "$PWD"', [
    rikanishu\multiprocess\Command::OPTION_CWD => '/tmp',
    rikanishu\multiprocess\Command::OPTION_ENV =>  [
        'SOME_ENV_VAR' => 'PWD is:'
    ],
]];

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
$pool->run();

/* @var $command rikanishu\multiprocess\Command */
foreach ($pool->getCommands() as $command) {
    $res = $command->getExecutionResult();
    echo $res->getExitCode() . " | " . $res->getStdout() . " | " . $res->getStderr() . "\n";
}

```

``` $cmd1 ```, ``` $cmd2 ``` and  ``` $cmd3 ``` show different format of commands applied by Pool.  You can also pass instance of Command right away because Pool implicitly convert shell command text to Command instances and check input data for this class first.


```php
…
$cmd4 = new rikanishu\multiprocess\Command(‘echo instance’);
…

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3, $cmd4]);
$pool->run();
```

The Pool’s ``` run() ``` method returns Futures array that represents each command, e.g. ``` $future[3] ```  represents ``` $cmd4 ``` from example above. You can always use ``` $command->getFuture() ``` method to get current future for executed command. Non executed command does not have Future and if you call ``` getFuture() ``` for non-executed Command, Exception will be raised. You can call ``` hasFuture() ``` for checking when your code does not know, is command running or not.

```php
$cmd1 = ['echo', '"Some Command"'];
$cmd2 = 'echo "Another Command"';
$cmd3 = new rikanishu\multiprocess\Command(‘echo instance’);

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
$futures = $pool->run();
$commands = $pool->getCommands();

print_r(count($commands)); // Count of command objects always equals passed shell commands. Pool raise an exception on creation step if command has invalid format.
print_r($cmd3 == $commands[2]); // Equals
print_r($commands[1]->hasFuture()); // True, command is running
print_r($commands[1]->getFuture() == $futures[1]); // Equals
print_r($commands[1]->getFuture()->getResult()); // Block and waiting execution result
//Or you can block process directly by alias command method
print_r($command[1]->getExecutionResult()); // Alias of Future’s getResult()

```

On non blocking mode ``` $pool->run() ``` call creates process array and returns control to called procedure. The process will execute at the meantime in background and when you call Future’s ``` getResult() ``` method, it blocks execution process while result has not received or time limit has not expired. You can always check Future’s ``` hasResult() ``` if you want to avoid blocking and continue your useful calculating process.


```php
$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3]);
$futures = $pool->run();
…
//doing some useful work
…
if ($furures[1]->hasResult()) {
…
}
//doing another useful work
…
//block and waiting data finally
$result = $futures[1]->getResult();
```

And also if you need to run only single command you can use command method ``` run() ``` directly. It will create new Pool with single command and return Future. Use method ``` runBlocking() ``` to run execution in blocking mode and receive execution result.

```php
$command = new rikanishu\multiprocess\Command('echo "Hello World!"');
$command->runBlocking()->getOutput(); // Hello World
```

Or
```php
$command = new rikanishu\multiprocess\Command('echo "Hello World!"');
$future = $command->run();
…
// doing some useful work
…
$future->getResult()->getOutput(); // Hello World
```

#### Pool options ####

Pool takes options array as second parameter. You can set some options to Pool by two ways:
 - Pass an array of options as second param to Pool construct:

```php
$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2, $cmd3], [
	rikanishu\multiprocess\Pool::OPTION_EXECUTION_TIMEOUT => 120,
	rikanishu\multiprocess\Pool::OPTION_BLOCKING_MODE => true
]);

```
 - Set param after the object creating:
```php
$pool->setExecutionTimeout(120);
$pool->setBlockingMode(true);
```

##### Options list #####

 - ``` OPTION_EXECUTION_TIMEOUT ``` - Limit of time in seconds for execution process. Values are less than zero interpret as unlimited time out. Default is -1 (has no limits). Alias method for option is ``` $pool->setExecutionTimeout(200) ```.

 - ``` OPTION_POLL_TIMEOUT ``` - Maximum timeout in seconds for every select poll cycle. This means time for waiting any react of running process and if it has no any react (out text to stdout / stderr or stop execution for example), retry the poll cycle after reading process status. Default value is 60 seconds. Alias method for option is ``` $pool->setPollTimeout(120) ```.

 - ``` OPTION_SELECT_USLEEP_TIME ``` - Time for poll cycle in microseconds  if select call returns false or does not supported by system for proc polling (for example on Windows). Default is 200 microseconds. Alias method for option is ``` $pool->setSelectUsleepTime(400) ```.

 - ``` OPTION_DEBUG ``` - Debug mode. Enables some output debug messages for execution. Default is false. Alas for this method is ``` $pool->setDebugEnabled(true) ```

 - ``` OPTION_BLOCKING_MODE ``` - Blocking mode flag. If blocking mode used, process will be stopped for all execution time. In other case, current process can interact with Future to wait results or check execution status. Default is false, non-blocking mode. Alias for this method is ``` $pool->setBlockingMode(true) ```.

#### Command options ####

You can configure Command same as Pool by two ways:
```php
$command = new rikanishu\multiprocess\Command(‘echo ‘Hello $NAME’’, [
	rikanishu\multiprocess\Command::OPTIONS_ENV => [
		‘NAME’ => ‘WORLD’,
	],
	rikanishu\multiprocess\Command::OPTIONS_CWD => ‘/tmp’
]);
```
Or
```php
$command->setEnvVariables([
	‘NAME’ => ‘WORLD’,
]);
$command->setCwdPath(‘/tmp’);
```

And also you can control options when you pass shell command to Pool construct as array second element:

```php
$cmd1 = ‘echo Hello!’;
$cmd2 = ['echo "$SOME_ENV_VAR" "$PWD"', [
    rikanishu\multiprocess\Command::OPTION_CWD => '/tmp',
    rikanishu\multiprocess\Command::OPTION_ENV =>  [
        'SOME_ENV_VAR' => 'PWD is:'
    ],
]];

$pool = new rikanishu\multiprocess\Pool([$cmd1, $cmd2]);
$pool->run();

```

##### Options list #####

 - ``` OPTION_ENV ``` - Environment variables list for command. Default is null. Alias method for this option is ``` $command->setEnvVariables([]) ```.

 - ``` OPTION_CWD ``` - Current working directory for command. Default is null and CWD inherits from parent process. Alias method for this option is ``` $command->setCwdPath(‘/tmp’) ```.

 - ``` OPTION_PROC ``` - Options for proc_open command. See full list in [PHP Documentation](http://php.net/manual/en/function.proc-open.php). Alias method for this option is ``` $command->setProcOptions([]) ```.

