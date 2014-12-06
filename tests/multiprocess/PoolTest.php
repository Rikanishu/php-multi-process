<?php

namespace rikanishu\tests\multiprocess;

use PHPUnit_Framework_TestCase;
use rikanishu\multiprocess\Command;
use rikanishu\multiprocess\Future;
use rikanishu\multiprocess\Pool;

class PoolTest extends PHPUnit_Framework_TestCase
{

    public function testExecuteParallel()
    {
        $cmd1 = 'echo "First Command"';
        $cmd2 = 'echo "Second Command"';

        $commands = [$cmd1, $cmd2];

        $pool = new Pool($commands);
        $pool->setExecutionTimeout(20);
        $pool->run();
        $commands = $pool->getCommands();
        $this->assertCount(2, $commands);

        $cmdObject1 = $commands[0];
        $cmdObject2 = $commands[1];

        $this->assertEquals($cmdObject1->getCommand(), $cmd1);
        $this->assertEquals($cmdObject2->getCommand(), $cmd2);

        $this->assertNotNull($cmdObject1->getExecutionResult());
        $this->assertEquals($cmdObject1->getExecutionResult()->getStdout(), "First Command");
        $this->assertEquals($cmdObject1->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmdObject1->getExecutionResult()->getExitCode(), 0);

        $this->assertNotNull($cmdObject2->getExecutionResult());
        $this->assertEquals($cmdObject2->getExecutionResult()->getStdout(), "Second Command");
        $this->assertEquals($cmdObject2->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmdObject2->getExecutionResult()->getExitCode(), 0);
    }

    public function testNonBlockingExecution()
    {
        $cmd1 = 'sleep 5; echo "First Command"';
        $cmd2 = 'sleep 1; echo "Second Command"';

        $pool = new Pool([$cmd1, $cmd2]);
        $pool->setExecutionTimeout(20);
        $futures = $pool->run();
        $commands = $pool->getCommands();
        $this->assertCount(2, $commands);
        $this->assertCount(2, $futures);
        $this->assertTrue($commands[0]->hasFuture());
        $this->assertTrue($commands[1]->hasFuture());
        $this->assertEquals($futures[0], $commands[0]->getFuture());
        $this->assertEquals($futures[1], $commands[1]->getFuture());

        /** @var $cmd1Future Future */
        $cmd1Future = $futures[0];
        /** @var $cmd2Future Future */
        $cmd2Future = $futures[1];

        $result = $cmd2Future->getResult();
        $this->assertEquals($result->getStdout(), "Second Command");
        $this->assertEquals($result->getStderr(), '');
        $this->assertEquals($result->getExitCode(), 0);

        $this->assertFalse($cmd1Future->hasResult());
        $result = $cmd1Future->getResult();
        $this->assertEquals($result->getStdout(), "First Command");
        $this->assertEquals($result->getStderr(), '');
        $this->assertEquals($result->getExitCode(), 0);
    }

    public function testBlockingExecution()
    {
        $cmd1 = 'sleep 5; echo "First Command"';
        $cmd2 = 'sleep 1; echo "Second Command"';

        $pool = new Pool([$cmd1, $cmd2]);
        $pool->setExecutionTimeout(20);
        $pool->setBlockingMode(true);
        $pool->run();
        foreach ($pool->getCommands() as $cmd) {
            $this->assertTrue($cmd->hasExecutionResult());
            $this->assertEquals($cmd->getExecutionResult()->getStderr(), '');
            $this->assertEquals($cmd->getExecutionResult()->getExitCode(), 0);
        }
    }

    public function testConstruct()
    {
        $pool = new Pool('some command');
        $this->assertEquals(count($pool->getCommands()), 1);
        $commands = $pool->getCommands();
        $this->assertEquals($commands[0]->getCommand(), 'some command');

        $pool = new Pool(['some command']);
        $this->assertEquals(count($pool->getCommands()), 1);
        $commands = $pool->getCommands();
        $this->assertEquals($commands[0]->getCommand(), 'some command');

        $pool = new Pool(['some command', 'another command']);
        $this->assertEquals(count($pool->getCommands()), 2);
        $commands = $pool->getCommands();
        $this->assertEquals($commands[0]->getCommand(), 'some command');
        $this->assertEquals($commands[1]->getCommand(), 'another command');

        $pool = new Pool([['some', 'command'], ['another', 'command']]);
        $this->assertEquals(count($pool->getCommands()), 2);
        $commands = $pool->getCommands();
        $this->assertEquals($commands[0]->getCommand(), 'some command');
        $this->assertEquals($commands[1]->getCommand(), 'another command');

        $cmd1 = new Command(['some', 'command']);
        $cmd2 = new Command(['another', 'command']);
        $pool = new Pool([$cmd1, $cmd2]);
        $this->assertEquals(count($pool->getCommands()), 2);
        $commands = $pool->getCommands();
        $this->assertEquals($commands[0]->getCommand(), 'some command');
        $this->assertEquals($commands[1]->getCommand(), 'another command');
    }

    public function testOptions()
    {
        $pool = new Pool('some command', [
            'SomeUserOption' => 'Value'
        ]);
        $this->assertEquals($pool->getOption('SomeUserOption'), 'Value');

        $pool = new Pool('some command', [
            Pool::OPTION_EXECUTION_TIMEOUT => 2000
        ]);
        $this->assertEquals($pool->getExecutionTimeout(), 2000);
        $pool->setExecutionTimeout(4000);
        $this->assertEquals($pool->getExecutionTimeout(), 4000);
        $this->assertEquals($pool->getOption(Pool::OPTION_EXECUTION_TIMEOUT), 4000);
    }

}