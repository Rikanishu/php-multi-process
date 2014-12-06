<?php

namespace rikanishu\multiprocess;

use rikanishu\multiprocess\exception\NonExecutedException;
use rikanishu\multiprocess\pool\ExecutionContext;

/**
 * Future execution promise
 *
 * Represents link between command and execution process
 *
 * @package rikanishu\multiprocess
 */
class Future
{
    /**
     * Current process execution state
     *
     * @see STATE_* Const
     * @var int
     */
    protected $executed = false;

    /**
     * Result of the execution if process executed successfully
     *
     * @var ExecutionResult
     */
    protected $result;

    /**
     * Execution context for this future
     *
     * @var ExecutionContext
     */
    protected $context;

    /**
     * Future base command
     *
     * @var Command
     */
    protected $command;

    /**
     * Create a future object
     *
     * @param ExecutionContext $context
     * @param Command $command
     */
    public function __construct($command, $context)
    {
        $this->context = $context;
        $this->command = $command;
    }

    /**
     * Block process for execution command
     */
    public function waitExecution()
    {
        $this->context->waitExecution($this->command);
    }

    /**
     * Resolve future with execution result
     *
     * @param ExecutionResult $executionResult
     */
    public function resolve($executionResult)
    {
        $this->result = $executionResult;
        $this->executed = true;
    }

    /**
     * Return true if process already executed
     *
     * @return bool
     */
    public function isExecuted()
    {
        return ($this->executed === true);
    }

    /**
     * Alias for isExecuted
     *
     * @return bool
     */
    public function hasResult()
    {
        return $this->isExecuted();
    }

    /**
     * Return result or block for execution
     *
     * @throws exception\NonExecutedException
     * @return ExecutionResult
     */
    public function getResult()
    {
        if (!$this->result) {
            if (!$this->isExecuted()) {
                $this->waitExecution();
                if (!$this->isExecuted()) {
                    throw new NonExecutedException('Command has not executed after waiting: ' . $this->command);
                }
            } else {
                throw new NonExecutedException('Executed command without result: ' . $this->command);
            }
        }

        return $this->result;
    }
}