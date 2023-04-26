<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Lock\Strategy;

/**
 * ConsensusStrategy is a StrategyInterface implementation where strictly more than 50% items should be successful.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class ConsensusStrategy implements StrategyInterface
{
    public function isMet(int $numberOfSuccess, int $numberOfItems): bool
    {
        return $numberOfSuccess > ($numberOfItems / 2);
    }

    public function canBeMet(int $numberOfFailure, int $numberOfItems): bool
    {
        return $numberOfFailure < ($numberOfItems / 2);
    }
}
