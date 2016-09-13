<?php

namespace rikanishu\multiprocess\pool;

use rikanishu\multiprocess\Command;
use rikanishu\multiprocess\exception\ExecutionTimeoutException;
use rikanishu\multiprocess\ExecutionResult;
use rikanishu\multiprocess\Future;
use rikanishu\multiprocess\Pool;


/**
 * Execution context for pool
 *
 * Every running pool has one execution context
 * that contains execution status for all command and global
 * execution options
 * @package rikanishu\multiprocess
 */
class ExecutionContext
{
    /**
     * Timeout for execution in seconds
     *
     * @var int
     */
    protected $executionTimeout;

    /**
     * Timeout for every poll cycle
     *
     * @var int
     */
    protected $pollTimeout;

    /**
     * Usleep time if select is not supported
     *
     * @var int
     */
    protected $usleepTime;

    /**
     * Support select polling
     *
     * @var bool
     */
    protected $isSelectSupported = true;

    /**
     * True if used blocking mode
     *
     * @var bool
     */
    protected $isBlockingMode = false;

    /**
     * True if debug mode enabled
     *
     * @var bool
     */
    protected $isDebugEnabled = false;

    /**
     * Active pool process
     *
     * @var Process[]
     */
    protected $procs = [];

    /**
     * Execution start time
     *
     * @var int
     */
    protected $executionStartTime;

    /**
     * Create new execution context from pool options
     *
     * @param Pool $pool
     */
    public function __construct($pool)
    {
        $this->executionTimeout = $pool->getExecutionTimeout();
        $this->pollTimeout = $pool->getPollTimeout();
        $this->usleepTime = $pool->getSelectUsleepTime();
        $this->isSelectSupported = $pool->isSelectSupported();
        $this->isBlockingMode = $pool->isBlockingModeEnabled();
        $this->isDebugEnabled = $pool->isDebugEnabled();
        foreach ($pool->getCommands() as $command) {
            $this->procs[] = new Process($command);
        }
    }

    /**
     * Run process
     *
     * @return Future[]
     */
    public function run()
    {
        $futures = [];
        foreach ($this->procs as $process) {
            $this->debug('Run process: ' . $process->getCommand());
            $process->run();
            $future = new Future($process->getCommand(), $this);
            $process->getCommand()->setFuture($future);
            $futures[] = $future;
        }
        $this->executionStartTime = time();
        if ($this->isBlockingMode) {
            $this->waitExecution();
        }
        return $futures;
    }

    /**
     * Wait execution for target command or for all commands
     *
     * @param Command $targetCommand
     * @throws ExecutionTimeoutException
     */
    public function waitExecution($targetCommand = null)
    {
        $readStreams = [];
        foreach ($this->procs as $procNum => $proc) {
            $pipes = $proc->getPipes();
            $readStreams['stdin' . $procNum] = $pipes[1];
            $readStreams['stderr' . $procNum] = $pipes[2];
        }
        $read = $readStreams;
        $write = $excepted = null;
        $isTargetExecuted = false;
        while ($this->procs && !$isTargetExecuted) {

            $this->debug('Wait execution');
            $selectResult = false;
            if ($this->isSelectSupported) {
                $this->debug('Use stream select');
                $this->debug($readStreams);
                $this->debug('Poll timeout: '. $this->pollTimeout);
                $selectResult = stream_select($read, $write, $excepted, $this->pollTimeout);
            }

            if ($selectResult === false) {
                $this->debug('Run usleep for: ' . $this->usleepTime);
                usleep($this->usleepTime);
            }

            if ($this->executionTimeout > 0 && (time() - $this->executionStartTime) >= $this->executionTimeout) {
                $this->debug('Execution timeout has expired, try to kill processes');
                foreach ($this->procs as $proc) {
                    $proc->close();
                }
                throw new ExecutionTimeoutException('Execution timeout has expired');
            }

            $this->debug('Read statuses');
            foreach ($this->procs as $procNum => $proc) {
                $status = $proc->readStatus();
                $this->debug($status);
                if ($proc->isCompleted()) {
                    $result = new ExecutionResult($proc->getExitCode(), $proc->getStdout(), $proc->getStderr());
                    $proc->getCommand()->getFuture()->resolve($result);
                    $proc->close();
                    unset($this->procs[$procNum]);
                    if (isset($readStreams['stdin' . $procNum])) {
                        unset($readStreams['stdin' . $procNum]);
                    }
                    if (isset($readStreams['stderr' . $procNum])) {
                        unset($readStreams['stderr' . $procNum]);
                    }
                    if ($targetCommand && $proc->getCommand() == $targetCommand) {
                        $isTargetExecuted = true;
                    }
                }
            }
            $read = $readStreams;
        }
    }


    /**
     * Print to output debug info if debug mode enabled
     *
     * @param $debugInfo
     */
    protected function debug($debugInfo)
    {
        if ($this->isDebugEnabled) {
            print_r($debugInfo);
            echo "\r\n";
        }
    }

}