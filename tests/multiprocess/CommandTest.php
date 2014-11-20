<?php


namespace rikanishu\tests\multiprocess;

use PHPUnit_Framework_TestCase;
use rikanishu\multiprocess\Command;

class CommandTest extends PHPUnit_Framework_TestCase
{
    public function testExecution()
    {
        $cmd = new Command('echo "Some Command"');
        $this->assertFalse($cmd->isExecuted());
        $this->assertFalse($cmd->isExecuteNow());
        $this->assertTrue($cmd->isNotExecuted());
        $cmd->run();
        $this->assertTrue($cmd->isExecuted());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), "Some Command");
        $this->assertEquals($cmd->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmd->getExecutionResult()->getExitCode(), 0);

        $cmd = new Command('echo "Execution Failed');
        $cmd->run();
        $this->assertTrue($cmd->isExecuted());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertNotEmpty($cmd->getExecutionResult()->getStderr());
        $this->assertNotEquals($cmd->getExecutionResult()->getExitCode(), 0);
    }

    public function testCwd()
    {
        $cmd = new Command('echo "$PWD"');
        $cmd->setCwdPath('/tmp');
        $cmd->run();
        $this->assertTrue($cmd->isExecuted());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), '/tmp');
    }

    public function testEnvVar()
    {
        $cmd = new Command('echo "$MULTIPROCESS_SOME_VAR"');
        $cmd->setEnvVariables([
            'MULTIPROCESS_SOME_VAR' => 'MultiProcess-Test'
        ]);
        $cmd->run();
        $this->assertTrue($cmd->isExecuted());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), 'MultiProcess-Test');
    }

    public function testConstruct()
    {
        $cmd = new Command(['echo', '"Hello World!"']);
        $this->assertEquals($cmd->getCommand(), 'echo "Hello World!"');

        $cmd = new Command('echo ');
        $cmd->appendCommand('"Hello World!"');
        $this->assertEquals($cmd->getCommand(), 'echo "Hello World!"');
        $cmd->replaceCommand('echo "Another Command"');
        $this->assertEquals($cmd->getCommand(), 'echo "Another Command"');
    }

    public function testCreateNewCommand()
    {
        $cmd = new Command(['echo', '"Hello World!"'], [
            Command::OPTION_CWD => '/tmp'
        ]);
        $this->assertEquals($cmd->getCommand(), 'echo "Hello World!"');
        $cmd->run();
        $this->assertTrue($cmd->isExecuted());
        $newCmd = $cmd->createNewCommand();
        $this->assertEquals($newCmd->getCommand(), 'echo "Hello World!"');
        $this->assertTrue($newCmd->isNotExecuted());
        $this->assertEquals($newCmd->getCwdPath(), '/tmp');
    }
}