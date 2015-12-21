<?php

namespace Hackzilla\PasswordGenerator\Generator;

use Hackzilla\PasswordGenerator\Model\Option\Option;
use Hackzilla\PasswordGenerator\RandomGenerator\RandomGeneratorInterface;

abstract class AbstractPasswordGenerator implements PasswordGeneratorInterface
{
    private $randomGenerator;
    private $options = array();
    private $parameters = array();

    /**
     * Generate $count number of passwords
     *
     * @param integer $count Number of passwords to return
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function generatePasswords($count = 1)
    {
        if (!is_int($count)) {
            throw new \InvalidArgumentException('Expected integer');
        } else if ($count < 0) {
            throw new \InvalidArgumentException('Expected positive integer');
        }

        $passwords = array();

        for ($i = 0; $i < $count; $i++) {
            $passwords[] = $this->generatePassword();
        }

        return $passwords;
    }

    /**
     * Set password generator option
     *
     * @param string $option
     * @param array $optionSettings
     *
     * @return $this
     */
    public function setOption($option, $optionSettings)
    {
        $type = isset($optionSettings['type']) ? $optionSettings['type'] : '';

        $this->options[$option] = Option::createFromType($type, $optionSettings);

        if ($this->options[$option] === null) {
            throw new \InvalidArgumentException('Invalid Option Type');
        }

        return $this;
    }

    /**
     * Remove Option
     *
     * @param $option
     *
     * @return $this
     */
    public function removeOption($option)
    {
        unset($this->options[$option]);

        return $this;
    }

    /**
     * Get option
     *
     * @param $option
     *
     * @return null
     */
    public function getOption($option)
    {
        if (!isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     * Set password generator option value
     *
     * @param string $option
     * @param $value
     *
     * @return $this
     */
    public function setOptionValue($option, $value)
    {
        if (!isset($this->options[$option])) {
            throw new \InvalidArgumentException('Invalid Option');
        }

        $this->options[$option]->setValue($value);

        return $this;
    }

    /**
     * Get option value
     *
     * @param $option
     *
     * @return mixed
     */
    public function getOptionValue($option)
    {
        if (!isset($this->options[$option])) {
            throw new \InvalidArgumentException('Invalid Option');
        }

        return $this->options[$option]->getValue();
    }

    /**
     * @param string $parameter
     * @param mixed $value
     *
     * @return $this
     */
    public function setParameter($parameter, $value)
    {
        $this->parameters[$parameter] = $value;

        return $this;
    }

    /**
     * @param string $parameter
     * @param mixed $default
     *
     * @return null|mixed
     */
    public function getParameter($parameter, $default = null)
    {
        if (!isset($this->parameters[$parameter])) {
            return $default;
        }

        return $this->parameters[$parameter];
    }

    /**
     * Possible options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set source of randomness
     *
     * @param RandomGeneratorInterface $randomGenerator
     *
     * @return $this
     */
    public function setRandomGenerator(RandomGeneratorInterface $randomGenerator)
    {
        $this->randomGenerator = $randomGenerator;

        return $this;
    }

    /**
     * Generate a random value
     * Fallback to mt_rand if none provided
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public function randomInteger($min, $max)
    {
        if ($this->randomGenerator) {
            return $this->randomGenerator->randomInteger($min, $max);
        }

        return mt_rand($min, $max);
    }
}
