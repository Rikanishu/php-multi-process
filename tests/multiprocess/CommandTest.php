<?php


namespace rikanishu\tests\multiprocess;

use PHPUnit_Framework_TestCase;
use rikanishu\multiprocess\Command;

class CommandTest extends PHPUnit_Framework_TestCase
{
    public function testNonBlockingExecution()
    {
        $cmd = new Command('sleep 5; echo "Some Command"');

        $this->assertFalse($cmd->hasFuture());
        $future = $cmd->run();
        $this->assertTrue($cmd->hasFuture());
        $this->assertInstanceOf('\rikanishu\multiprocess\Future', $future);
        $this->assertEquals($cmd->getFuture(), $future);
        $this->assertFalse($future->hasResult());
        $this->assertFalse($future->isExecuted());
        $executionResult = $future->getResult();
        $this->assertNotNull($executionResult);

        $this->assertEquals($executionResult->getStdout(), "Some Command");
        $this->assertEquals($executionResult->getStderr(), '');
        $this->assertEquals($executionResult->getExitCode(), 0);
    }

    public function testBlockingExecution()
    {
        $cmd = new Command('sleep 5; echo "Some Command"');
        $executionResult = $cmd->runBlocking();
        $this->assertInstanceOf('\rikanishu\multiprocess\ExecutionResult', $executionResult);
        $this->assertTrue($cmd->getFuture()->isExecuted());
        $this->assertEquals($executionResult->getStdout(), "Some Command");
        $this->assertEquals($executionResult->getStderr(), '');
        $this->assertEquals($executionResult->getExitCode(), 0);
    }

    public function testExecutionFailed()
    {
        $cmd = new Command('echo "Execution Failed');
        $cmd->run();
        $this->assertTrue($cmd->hasFuture());
        $this->assertFalse($cmd->getFuture()->isExecuted());
        $this->assertNotNull($cmd->getFuture()->getResult());
        $this->assertTrue($cmd->getFuture()->isExecuted());

        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertNotEmpty($cmd->getExecutionResult()->getStderr());
        $this->assertNotEquals($cmd->getExecutionResult()->getExitCode(), 0);
    }

    public function testExecutionResult()
    {
        $cmd = new Command('echo "Some Command"');

        try {
            $cmd->getExecutionResult();
            $this->fail("Exception is not raised");
        } catch (\Exception $e) {

        }

        $cmd->run();
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), "Some Command");
        $this->assertEquals($cmd->getExecutionResult()->getStderr(), '');
        $this->assertEquals($cmd->getExecutionResult()->getExitCode(), 0);
    }

    public function testCwd()
    {
        $cmd = new Command('echo "$PWD"');
        $cmd->setCwdPath('/tmp');
        $cmd->runBlocking();

        $this->assertTrue($cmd->hasExecutionResult());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), '/tmp');
    }

    public function testStdin()
    {
        $cmd = new Command('cat');
        $cmd->setStdin("hello world");
        $cmd->runBlocking();

        $this->assertTrue($cmd->hasExecutionResult());
        $this->assertNotNull($cmd->getExecutionResult());
        $this->assertEquals($cmd->getExecutionResult()->getStdout(), 'hello world');
    }

    public function testEnvVar()
    {
        $cmd = new Command('echo "$MULTIPROCESS_SOME_VAR"');
        $cmd->setEnvVariables([
            'MULTIPROCESS_SOME_VAR' => 'MultiProcess-Test'
        ]);
        $cmd->runBlocking();
        $this->assertTrue($cmd->hasExecutionResult());
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
        $cmd->runBlocking();
        $this->assertTrue($cmd->hasExecutionResult());
        $newCmd = $cmd->createNewCommand();
        $this->assertEquals($newCmd->getCommand(), 'echo "Hello World!"');
        $this->assertFalse($newCmd->hasFuture());
        $this->assertEquals($newCmd->getCwdPath(), '/tmp');
    }
}