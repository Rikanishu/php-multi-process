<?php

namespace rikanishu\multiprocess;

/**
 * OptionsTrait
 *
 * Used in Command and Execution classes for options storing
 *
 * @package rikanishu\multiprocess
 */
trait OptionsTrait
{
    /**
     * Object options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Initialize object with new options
     *
     * @param $options
     */
    public function initOptions($options)
    {
        if (is_array($options)) {
            $this->options = array_replace($this->getDefaultOptions(), $options);
        }
    }

    /**
     * Set new option
     *
     * @param string $optionName
     * @param mixed $optionValue
     */
    public function setOption($optionName, $optionValue)
    {
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Return one option if it exists, else null
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        return (isset($this->options[$optionName])) ? $this->options[$optionName] : null;
    }

    /**
     * Check if options exists and it's not null
     *
     * @param string $optionName
     * @return bool
     */
    public function hasOption($optionName)
    {
        return (isset($this->options[$optionName]));
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Replace all options with new array
     *
     * @param $newOptions
     * @return mixed
     */
    public function replaceOptions($newOptions)
    {
        return $this->options = $newOptions;
    }

    /**
     * Return array of default options
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return [];
    }
}