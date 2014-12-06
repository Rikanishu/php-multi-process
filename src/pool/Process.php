<?php

namespace rikanishu\multiprocess\pool;

use rikanishu\multiprocess\Command;
use rikanishu\multiprocess\exception\ExecutionFailedException;

/**
 * Runned process from pool
 *
 * @package rikanishu\multiprocess\pool
 */
class Process
{
    /**
     * Executed command
     *
     * @var \rikanishu\multiprocess\Command
     */
    protected $command;

    /**
     * Proc resource
     *
     * @var resource
     */
    protected $proc;

    /**
     * Proc pipe
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * True if process running
     *
     * @var bool
     */
    protected $isRunning = false;

    /**
     * Process exit code
     *
     * @var int
     */
    protected $exitCode = 0;

    /**
     * Process output stdout
     *
     * @var string
     */
    protected $stdout = '';

    /**
     * Process output stderr
     *
     * @var string
     */
    protected $stderr = '';

    /**
     * Create a pool process
     *
     * @param Command $cmd
     */
    public function __construct(Command $cmd)
    {
        $this->command = $cmd;
    }

    /**
     * Run command
     *
     * @throws \rikanishu\multiprocess\exception\ExecutionFailedException
     */
    public function run()
    {
        $command = $this->command;
        if (!$command->getCommand()) {
            throw new ExecutionFailedException("Can't run empty command");
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $this->proc = @proc_open(
            $command->getCommand(), $descriptors, $this->pipes,
            $command->getCwdPath(), $command->getEnvVariables(),
            $command->getProcOptions()
        );

        if ($this->proc === false) {
            throw new ExecutionFailedException('Proc open failed on' . $command);
        }

        $this->isRunning = true;
    }

    /**
     * Close process pipes
     */
    public function close()
    {
        if ($this->proc) {
            @proc_close($this->proc);
            $this->proc = null;
        }
    }

    /**
     * Return proc execution status
     *
     * @return array
     */
    public function readStatus()
    {
        if (!$this->isRunning) {
            return [];
        }

        $procStatus = @proc_get_status($this->proc);
        if (!$procStatus['running']) {
            $this->stdout = stream_get_contents($this->pipes[1]);
            $this->stderr = stream_get_contents($this->pipes[2]);
            $this->exitCode = (isset($procStatus['exitcode'])) ? $procStatus['exitcode'] : 0;
            $this->isRunning = false;
        }

        return $procStatus;
    }

    /**
     * Return command for execution
     *
     * @return \rikanishu\multiprocess\Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Return pipes array for proc
     *
     * @return array
     */
    public function getPipes()
    {
        return $this->pipes;
    }

    /**
     * Return proc resource
     *
     * @return resource
     */
    public function getProc()
    {
        return $this->proc;
    }

    /**
     * Check if process completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return ($this->isRunning === false);
    }

    /**
     * Return process stderr
     *
     * @return string
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * Return process stdout
     *
     * @return string
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * Return process exit code
     *
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

}