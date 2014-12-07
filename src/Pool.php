<?php

namespace rikanishu\multiprocess;

use rikanishu\multiprocess\exception\NonExecutedException;
use rikanishu\multiprocess\pool\ExecutionContext;

/**
 * Pool
 *
 * This class represents a pool of commands for execution that have to be run parallel
 *
 * @package rikanishu\multiprocess
 */
class Pool
{
    use OptionsTrait;

    /**
     * Limit of time in seconds for execution process.
     * Values are less than zero interpret as unlimited time out.
     *
     * Unlimited by default (-1)
     */
    const OPTION_EXECUTION_TIMEOUT = 'ExecTimeout';

    /**
     * Maximum timeout in seconds for every select poll cycle.
     * This means time for waiting any react of running process and if it has no any react
     * (out text to stdout / stderr or stop execution for example),
     * retry the poll cycle after reading process status.
     *
     * 60 seconds by default
     */
    const OPTION_POLL_TIMEOUT = 'PollTimeout';

    /**
     * Time for poll cycle in microseconds if select call returns false or does not
     * supported by system for proc polling (for example on Windows).
     *
     * Default is 200
     */
    const OPTION_SELECT_USLEEP_TIME = 'SelectUsleepTime';

    /**
     * Blocking mode flag
     *
     * If blocking mode used, process will be stopped for all execution time.
     * If not, current process can r interact with Future to wait results or check execution status
     *
     * Default is false
     */
    const OPTION_BLOCKING_MODE = 'BlockingMode';

    /**
     * Is debug mode enabled
     *
     * False by default
     */
    const OPTION_DEBUG = 'Debug';


    /**
     * Command for execution
     *
     * @var Command[]
     */
    protected $commands = [];

    /**
     * Pool constructor
     *
     * Accept commands in the following formats:
     *
     * ['echo "Some payload"', 'echo "Another payload"']
     * [['echo', '"Some payload"'], ['echo', "Another payload"]]
     * [['echo "Some payload"', $commandOptionsArray], ['echo "Another payload"'], $anotherCommandOptionsArray]
     * [[['echo', '"Some payload"'], $options], [['echo', "Another payload"], $options]]
     * [Command $cmd1, Command $cmd2]
     *
     * @param array $commands
     * @param array $options
     */
    public function __construct($commands, $options = [])
    {
        $commandObjects = [];
        if (!is_array($commands)) {
            $commands = [$commands];
        }
        foreach ($commands as $cmd) {
            $commandObjects[] = $this->createCommandObject($cmd);
        }
        $this->commands = $commandObjects;
        $this->options = $options;
    }

    /**
     * Add commands to commands array
     *
     * @param $cmd
     */
    public function addCommand($cmd)
    {
        $this->commands[] = $this->createCommandObject($cmd);
    }

    /**
     * Return commands array
     *
     * @return array|Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Reset commands array
     */
    public function resetCommands()
    {
        $this->commands = [];
    }

    /**
     * Return count of commands
     *
     * @return int
     */
    public function getCommandsCount()
    {
        return count($this->commands);
    }

    /**
     * Run execution process
     *
     * @throws exception\NonExecutedException
     * @return Future[]
     */
    public function run()
    {
        if (!$this->commands) {
            throw new NonExecutedException('Pool has no execution command');
        }

        $executionContext = $this->createNewExecutionContext();
        return $executionContext->run();
    }

    /**
     * Return array of default options
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return [
            Pool::OPTION_EXECUTION_TIMEOUT => -1,
            Pool::OPTION_POLL_TIMEOUT => 60,
            Pool::OPTION_SELECT_USLEEP_TIME => 200,
            Pool::OPTION_BLOCKING_MODE => false,
            Pool::OPTION_DEBUG => false
        ];
    }

    /**
     * Set execution timeout option
     *
     * @param int $executionTimeout
     */
    public function setExecutionTimeout($executionTimeout)
    {
        $this->setOption(Pool::OPTION_EXECUTION_TIMEOUT, $executionTimeout);
    }

    /**
     * Set poll timeout option
     *
     * @see Pool::OPTION_POLL_TIMEOUT
     * @param int $pollTimeout
     */
    public function setPollTimeout($pollTimeout)
    {
        $this->setOption(Pool::OPTION_POLL_TIMEOUT, $pollTimeout);
    }

    /**
     * Set select usleep time
     *
     * @see Pool::OPTION_SELECT_USLEEP_TIME
     * @param int $usleepTime
     */
    public function setSelectUsleepTime($usleepTime)
    {
        $this->setOption(Pool::OPTION_SELECT_USLEEP_TIME, $usleepTime);
    }

    /**
     * Set debug enabled option
     *
     * @see Pool::OPTION_DEBUG
     * @param bool $isDebugEnabled
     */
    public function setDebugEnabled($isDebugEnabled)
    {
        $this->setOption(Pool::OPTION_DEBUG, $isDebugEnabled);
    }

    /**
     * Set blocking mode flag
     *
     * @see Pool::OPTION_BLOCKING_MODE
     * @param bool $isBlockingEnabled
     */
    public function setBlockingMode($isBlockingEnabled)
    {
        $this->setOption(Pool::OPTION_BLOCKING_MODE, $isBlockingEnabled);
    }


    /**
     * Return execution time option
     *
     * @see Pool::OPTION_EXECUTION_TIMEOUT
     * @return int
     */
    public function getExecutionTimeout()
    {
        return $this->getOption(Pool::OPTION_EXECUTION_TIMEOUT);
    }

    /**
     * Return poll timeout option
     *
     * @see Pool::OPTION_POLL_TIMEOUT
     * @return int
     */
    public function getPollTimeout()
    {
        return $this->getOption(Pool::OPTION_POLL_TIMEOUT);
    }

    /**
     * Return usleep time for select
     *
     * @see Pool::OPTION_SELECT_USLEEP_TIME
     * @return int
     */
    public function getSelectUsleepTime()
    {
        return $this->getOption(Pool::OPTION_SELECT_USLEEP_TIME);
    }

    /**
     * Return debug enabled option
     *
     * @see Pool::OPTION_DEBUG
     * @return bool
     */
    public function isDebugEnabled()
    {
        return ($this->getOption(Pool::OPTION_DEBUG) === true);
    }

    /**
     * Set blocking mode flag
     *
     * @see Pool::OPTION_BLOCKING_MODE
     * @return bool
     */
    public function isBlockingModeEnabled()
    {
        return ($this->getOption(Pool::OPTION_BLOCKING_MODE) === true);
    }

    /**
     * This method resolve the poll method - select / timeout
     * It depends from OS select supporting
     *
     * @return bool
     */
    public function isSelectSupported()
    {
        return (!(strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN'));
    }

    /**
     * Create command object for command string / command array
     *
     * @param mixed $cmd
     * @return Command
     */
    protected function createCommandObject($cmd)
    {
        if ($cmd instanceof Command) {
            return $cmd;
        }

        if (is_array($cmd) && count($cmd) == 2) {
            $cmdText = reset($cmd);
            $cmdOptions = next($cmd);
            if (is_string($cmdOptions)) {
                $cmdOptions = [];
            } else {
                $cmd = $cmdText;
            }
        } else {
            $cmdOptions = [];
        }

        return $this->createNewCommand($cmd, $cmdOptions);
    }

    /**
     * Create new Command instance from passed arguments
     *
     * @param string $cmd
     * @param array $cmdOptions
     * @return Command
     */
    protected function createNewCommand($cmd, $cmdOptions = [])
    {
        return new Command($cmd, $cmdOptions);
    }

    /**
     * Return new PoolExecutionContext instance linked with this pool
     *
     * @return ExecutionContext
     */
    protected function createNewExecutionContext()
    {
        return new ExecutionContext($this);
    }
}