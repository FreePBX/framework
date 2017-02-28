<?php

namespace malkusch\lock\util;

use malkusch\lock\exception\TimeoutException;

/**
 * Repeats executing a code until it was successful.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 * @internal
 */
class Loop
{
    
    /**
     * @var int The timeout in seconds.
     */
    private $timeout;
    
    /**
     * @var bool True while code should be repeated.
     */
    private $looping;
    
    /**
     * Sets the timeout.
     *
     * The default is 3 seconds.
     *
     * @param int $timeout The timeout in seconds.
     * @throws \LengthException The timeout must be greater than 0.
     */
    public function __construct($timeout = 3)
    {
        if ($timeout <= 0) {
            throw new \LengthException("The timeout must be greater than 0. '$timeout' was given");
        }
        $this->timeout = $timeout;
    }
    
    /**
     * Notifies that this was the last iteration.
     */
    public function end()
    {
        $this->looping = false;
    }
    
    /**
     * Repeats executing a code until it was succesful.
     *
     * The code has to be designed in a way that it can be repeated without any
     * side effects. When execution was successful it should notify that event
     * by calling {@link Loop::end()}. I.e. the only side effects
     * of the code may happen after a successful execution.
     *
     * If the code throws an exception it will stop repeating the execution.
     *
     * @param callable $code The executed code block.
     * @return mixed The return value of the executed block.
     *
     * @throws \Exception The execution block threw an exception.
     * @throws TimeoutException The timeout was reached.
     */
    public function execute(callable $code)
    {
        $this->looping = true;
        $minWait = 100;
        $timeout = microtime(true) + $this->timeout;
        for ($i = 0; $this->looping && microtime(true) < $timeout; $i++) {
            $result = call_user_func($code);
            if (!$this->looping) {
                break;
            }
            $min    = $minWait * pow(2, $i);
            $max    = $min * 2;
            $usleep = rand($min, $max);
            
            usleep($usleep);
        }

        if (microtime(true) >= $timeout) {
            throw new TimeoutException("Timeout of $this->timeout seconds exceeded.");
        }

        return $result;
    }
}
