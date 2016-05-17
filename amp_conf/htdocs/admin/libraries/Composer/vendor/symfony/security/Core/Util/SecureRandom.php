<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Util;

@trigger_error('The '.__NAMESPACE__.'\SecureRandom class is deprecated since version 2.8 and will be removed in 3.0. Use the random_bytes() function instead.', E_USER_DEPRECATED);

/**
 * A secure random number generator implementation.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * @deprecated since version 2.8, to be removed in 3.0. Use the random_bytes function instead
 */
final class SecureRandom implements SecureRandomInterface
{
    /**
     * {@inheritdoc}
     */
    public function nextBytes($nbBytes)
    {
        return random_bytes($nbBytes);
    }
}
