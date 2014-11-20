<?php

namespace rikanishu\tests\multiprocess;

use PHPUnit_Framework_TestCase;
use rikanishu\multiprocess\Command;
use rikanishu\multiprocess\Pool;

class PoolTest extends PHPUnit_Framework_TestCase
{

    public function testExecuteParallel()
    {
        $cmd1 = ['echo', '"First Command"'];
        $cmd2 = ['echo', '"Second Command"'];

        $commands = [$cmd1, $cmd2];

        $pool = new Pool($commands);
        $pool->setExecutionTimeout(20);
        $pool->run();
        $executedCommands = $pool->getExecutedCommands();
        $this->assertEquals(count($executedCommands), count($commands));
        $cmdObject1 = $executedCommands[0];
        $this->assertNotNull($cmdObject1->getExecutionResult());
        $this->assertEquals($cmdObject1->getExecutionResult()->getStdout(), "First Command");
        $this->assertEquals($cmdObject1->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmdObject1->getExecutionResult()->getExitCode(), 0);

        $cmdObject2 = $executedCommands[1];
        $this->assertNotNull($cmdObject2->getExecutionResult());
        $this->assertEquals($cmdObject2->getExecutionResult()->getStdout(), "Second Command");
        $this->assertEquals($cmdObject2->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmdObject2->getExecutionResult()->getExitCode(), 0);
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