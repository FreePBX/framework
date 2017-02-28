<?php

namespace malkusch\lock\exception;

/**
 * Failed to acquire lock.
 *
 * This exception implies that the critical code was not executed, or at
 * least had no side effects.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class LockAcquireException extends MutexException
{

}
