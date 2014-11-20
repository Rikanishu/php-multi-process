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
    protected $stdout = '';

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
     * @param string $stdout
     * @param string $stderr
     */
    public function __construct($exitCode, $stdout, $stderr)
    {
        $this->exitCode = $exitCode;
        $this->stdout = $stdout;
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
     * @param bool $trim
     * @return string
     */
    public function getStderr($trim = true)
    {
        if ($this->stderr && $trim) {
            return trim($this->stderr);
        }
        return $this->stderr;
    }

    /**
     * Return process stdout
     *
     * @param bool $trim
     * @return string
     */
    public function getStdout($trim = true)
    {
        if ($this->stdout && $trim) {
            return trim($this->stdout);
        }
        return $this->stdout;
    }

    /**
     * Alias for getStdout()
     *
     * @param bool $trim
     * @return string
     */
    public function getOutput($trim = true)
    {
        return $this->getStdout($trim);
    }
}