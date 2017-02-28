<?php

namespace malkusch\lock\exception;

/**
 * Failed to release lock.
 *
 * Take this exception very serious. Failing to release a lock might have
 * the potential to introduce deadlocks. Also the critical code was executed
 * i.e. side effects may have happened.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
class LockReleaseException extends MutexException
{

}
