<?php

namespace rikanishu\multiprocess;

/**
 * Class Result
 *
 * Represents process execution result (output / exit code)
 *
 * @package rikanishu\multiprocess
 */
class ExecutionResult
{
    /**
     * Process STDOUT output
     *
     * @var string
     */
    protected $stdin = '';

    /**
     * Process STDERR output
     *
     * @var string
     */
    protected $stderr = '';

    /**
     * Process exit code
     *
     * @var int
     */
    protected $exitCode = 0;

    /**
     * @param int $exitCode
     * @param string $stdin
     * @param string $stderr
     */
    public function __construct($exitCode, $stdin, $stderr)
    {
        $this->exitCode = $exitCode;
        $this->stdin = $stdin;
        $this->stderr = $stderr;
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
     * Return process stdin
     *
     * @return string
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * Alias for getStdin()
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->getStdin();
    }
}