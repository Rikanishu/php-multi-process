<?php

namespace rikanishu\multiprocess;

use rikanishu\multiprocess\exception\ExecutionFailedException;
use rikanishu\multiprocess\exception\NonExecutedException;

/**
 * Command
 *
 * Base process class
 * Represents one command-line process
 *
 *
 * @package rikanishu\multiprocess
 */
class Command
{

    use OptionsTrait;

    /**
     * Env variables array
     *
     * Default is null
     */
    const OPTION_ENV = 'Env';

    /**
     * Current working dir for command
     *
     * Default is null
     */
    const OPTION_CWD = 'Cwd';

    /**
     * Options for proc command
     *
     * Default is null
     */
    const OPTION_PROC = 'Proc';

    /**
     * Process command
     *
     * @var string
     */
    protected $cmd;

    /**
     * Execution future object
     *
     * @var Future
     */
    protected $future;

    /**
     * Create new command
     *
     * @param string|array $cmd
     * @param array $options
     */
    public function __construct($cmd, $options = [])
    {
        $this->initCommand($cmd);
        $this->initOptions($options);
    }

    /**
     * Replace command string to new one
     *
     * @param string $cmd
     */
    public function replaceCommand($cmd)
    {
        $this->cmd = $cmd;
    }

    /**
     * Append string part to current command
     *
     * @param string $part
     */
    public function appendCommand($part)
    {
        $this->cmd .= $part;
    }

    /**
     * Return command string
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->cmd;
    }

    /**
     * Return command execution result
     *
     * Block process and wait result if it's not exists
     *
     * @return ExecutionResult
     */
    public function getExecutionResult()
    {
        return $this->getFuture()->getResult();
    }

    /**
     * Check is execution result exists
     *
     * @return bool
     */
    public function hasExecutionResult()
    {
        return ($this->hasFuture() && $this->getFuture()->hasResult());
    }

    /**
     * Create new non-executed command from existed
     *
     * @return Command
     */
    public function createNewCommand()
    {
        return new Command($this->cmd, $this->options);
    }

    /**
     * Run a single command
     *
     * @param array $poolOptions
     * @return Future
     */
    public function run($poolOptions = [])
    {
        $pool = new Pool([$this], $poolOptions);
        $pool->run();
        return $this->getFuture();
    }

    /**
     * Run a single command with results waiting
     *
     * @param array $poolOptions
     * @return ExecutionResult
     */
    public function runBlocking($poolOptions = [])
    {
        return $this->run($poolOptions)->getResult();
    }

    /**
     * Return current working directory for command
     *
     * @return string|null
     */
    public function getCwdPath()
    {
        return $this->getOption(Command::OPTION_CWD);
    }

    /**
     * Set current working directory for command
     *
     * @param string $cwdPath
     */
    public function setCwdPath($cwdPath)
    {
        $this->setOption(Command::OPTION_CWD, $cwdPath);
    }

    /**
     * Return environment variables array
     *
     * @return array|null
     */
    public function getEnvVariables()
    {
        return $this->getOption(Command::OPTION_ENV);
    }

    /**
     * Set environment variables for command execution
     *
     * @param array $envVariables
     */
    public function setEnvVariables($envVariables)
    {
        $this->setOption(Command::OPTION_ENV, $envVariables);
    }

    /**
     * Return proc options array or null
     *
     * @return array|null
     */
    public function getProcOptions()
    {
        return $this->getOption(Command::OPTION_PROC);
    }

    /**
     * Set proc options
     *
     * @param array $procOptions
     */
    public function setProcOptions($procOptions)
    {
        $this->setOption(Command::OPTION_PROC, $procOptions);
    }

    /**
     * Check if command has a future object
     *
     * @return bool
     */
    public function hasFuture()
    {
        return ($this->future !== null);
    }

    /**
     * Set execution future object
     *
     * @param \rikanishu\multiprocess\Future $future
     */
    public function setFuture($future)
    {
        $this->future = $future;
    }

    /**
     * Return execution future object
     *
     * @throws \Exception
     * @return \rikanishu\multiprocess\Future
     */
    public function getFuture()
    {
        if (!$this->future) {
            throw new \Exception('Future has not assigned yet');
        }
        return $this->future;
    }

    /**
     * String repr of command
     *
     * @return string
     */
    public function __toString()
    {
        return '[ ' . $this->cmd . ' ]';
    }

    /**
     * Prepare command before assign
     *
     * @param $cmd
     * @return string
     */
    protected function initCommand($cmd)
    {
        if (is_array($cmd)) {
            $cmd = implode(' ', $cmd);
        }

        $this->cmd = $cmd;

        return $cmd;
    }

    /**
     * Prepare execution result before assign
     *
     * @param ExecutionResult $executionResult
     * @return ExecutionResult
     */
    protected function prepareExecutionResult($executionResult)
    {
        return $executionResult;
    }
}