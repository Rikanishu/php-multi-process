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
     * State for not executed process
     */
    const STATE_NOT_EXECUTED = 0;

    /**
     * State for process that executing just now
     */
    const STATE_EXECUTE_NOW = 1;

    /**
     * Get this status when process is executed and output is gained
     */
    const STATE_EXECUTED = 2;

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
     * Process command
     *
     * @var string
     */
    protected $cmd;

    /**
     * Current process execution state
     *
     * @see STATE_* Const
     * @var int
     */
    protected $state;

    /**
     * Result of the execution if process executed successfully
     *
     * @var ExecutionResult
     */
    protected $executionResult;

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

        $this->state = Command::STATE_NOT_EXECUTED;
    }

    /**
     * @param string $cmd
     */
    public function replaceCommand($cmd)
    {
        $this->cmd = $cmd;
    }

    /**
     * @param string $part
     */
    public function appendCommand($part)
    {
        $this->cmd .= $part;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->cmd;
    }


    /**
     * @param ExecutionResult $executionResult
     * @throws \Exception
     */
    public function setExecutionResult($executionResult)
    {
        if (!$this->isExecuted()) {
            throw new NonExecutedException('Set execution result for non-executed process. Switch state to executed first');
        }
        $this->executionResult = $this->prepareExecutionResult($executionResult);
    }

    /**
     * @throws \Exception
     * @return ExecutionResult
     */
    public function getExecutionResult()
    {
        if (!$this->isExecuted()) {
            throw new NonExecutedException('Get execution result for non-executed process. Check state of execution first');
        }
        return $this->executionResult;
    }

    /**
     * Return current process state
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Switch current process state
     *
     * @param int $newState
     */
    public function setState($newState)
    {
        $this->state = $newState;
    }

    /**
     * Return true if process already executed
     *
     * @return bool
     */
    public function isExecuted()
    {
        return ($this->state === Command::STATE_EXECUTED);
    }

    /**
     * Return true if process executing just now
     *
     * @return bool
     */
    public function isExecuteNow()
    {
        return ($this->state === Command::STATE_EXECUTE_NOW);
    }

    /**
     * Return true if process is not executed yet
     *
     * @return bool
     */
    public function isNotExecuted()
    {
        return ($this->state === Command::STATE_NOT_EXECUTED);
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
     * @throws exception\ExecutionFailedException
     * @return ExecutionResult
     */
    public function run($poolOptions = [])
    {
        $pool = new Pool([$this], $poolOptions);
        $pool->run();
        if (!$this->isExecuted()) {
            throw new ExecutionFailedException('Command is not executed');
        }
        return $this->getExecutionResult();
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