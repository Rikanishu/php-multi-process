<?php

namespace rikanishu\multiprocess;

use rikanishu\multiprocess\exception\ExecutionTimeoutException;

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
     * Command for execution
     *
     * @var Command[]
     */
    protected $commands = [];

    /**
     * Timelimit for process execution
     *
     * Unlimited by default (-1)
     */
    const OPTION_EXECUTION_TIMEOUT = 'ExecTimeout';

    /**
     * Timelimit for poll each process
     *
     * 60 seconds by default
     */
    const OPTION_POLL_TIMEOUT = 'PollTimeout';

    /**
     * Time in microseconds to sleep when select return false or not supported by
     * system
     *
     * Default is 200
     */
    const OPTION_SELECT_USLEEP_TIME = 'SelectUsleepTime';

    /**
     * Is debug mode enabled
     *
     * False by default
     */
    const OPTION_DEBUG = 'Debug';

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
     * @return Command[]
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
     * Return list of commands with state executed
     *
     * @return Command[]
     */
    public function getExecutedCommands()
    {
        $result = [];
        foreach ($this->commands as $command) {
            if ($command->isExecuted()) {
                $result[] = $command;
            }
        }
        return $result;
    }

    /**
     * Run execution process
     *
     * @throws exception\ExecutionTimeoutException
     */
    public function run()
    {
        if (!$this->commands) {
            return;
        }

        $executionTimeout = $this->getExecutionTimeout();
        $pollTimeout = $this->getPollTimeout();
        $isSelectSupported = $this->isSelectSupported();
        $selectUsleepTime = $this->getSelectUsleepTime();

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $procs = [];
        $readStreams = [];

        foreach ($this->commands as $commandNum => $command) {
            $pipes = [];
            $process = proc_open(
                $command->getCommand(), $descriptors, $pipes,
                $command->getCwdPath(), $command->getEnvVariables()
            );

            $procs[$commandNum] = [
                'process' => $process,
                'pipes' => $pipes,
                'cmd' => $command
            ];

            $command->setState(Command::STATE_EXECUTE_NOW);

            $readStreams['stdin' . $commandNum] = $pipes[1];
            $readStreams['stderr' . $commandNum] = $pipes[2];

            $this->debug("Run process: " . $command->getCommand());
        }

        $startTime = time();
        $read = $readStreams;
        $write = $except = null;
        while ($procs) {
            $selectResult = false;
            if ($isSelectSupported) {
                $selectResult = stream_select($read, $write, $except, $pollTimeout);
            }
            if ($selectResult === false) {
                usleep($selectUsleepTime);
            }
            if ($executionTimeout > 0 && (time() - $startTime) >= $executionTimeout) {
                foreach ($procs as $proc) {
                    proc_close($proc['process']);
                }
                throw new ExecutionTimeoutException('Execution timeout has expired');
            }
            $this->debug('Read streams');
            $this->debug($readStreams);
            foreach ($procs as $procNum => $proc) {
                $status = proc_get_status($proc['process']);
                $this->debug($status);
                if (!$status['running']) {
                    $stdin = stream_get_contents($proc['pipes'][1]);
                    $stderr = stream_get_contents($proc['pipes'][2]);
                    /** @var $cmd Command */
                    $cmd = $proc['cmd'];
                    $exitCode = (isset($status['exitcode'])) ? $status['exitcode'] : 0;
                    $executionResult = new ExecutionResult($exitCode, $stdin, $stderr);
                    $cmd->setState(Command::STATE_EXECUTED);
                    $cmd->setExecutionResult($executionResult);
                    proc_close($proc['process']);
                    unset($procs[$procNum]);
                    if (isset($readStreams['stdin' . $procNum])) {
                        unset($readStreams['stdin' . $procNum]);
                    }
                    if (isset($readStreams['stderr' . $procNum])) {
                        unset($readStreams['stderr' . $procNum]);
                    }
                }
            }
            $read = $readStreams;
            $this->debug("Poll... " . count($procs) . " procs");
        }
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
        $this->getOption(Pool::OPTION_EXECUTION_TIMEOUT, $executionTimeout);
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
     * This method resolve the poll method - select / timeout
     * It depends from OS select supporting
     *
     * @return bool
     */
    protected function isSelectSupported()
    {
        return (!(strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN'));
    }

    /**
     * Print to output debug info if debug mode enabled
     *
     * @param $debugInfo
     */
    protected function debug($debugInfo)
    {
        if ($this->isDebugEnabled()) {
            print_r($debugInfo);
            echo "\r\n";
        }
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
            if ($cmd->isExecuted()) {
                $cmd = $cmd->createNewCommand();
            }
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

        return new Command($cmd, $cmdOptions);
    }
}