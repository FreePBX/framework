<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Encoder;

use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;

trigger_deprecation('symfony/security-core', '5.3', 'The "%s" class is deprecated, use "%s" instead.', BasePasswordEncoder::class, CheckPasswordLengthTrait::class);

/**
 * BasePasswordEncoder is the base class for all password encoders.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Symfony 5.3, use CheckPasswordLengthTrait instead
 */
abstract class BasePasswordEncoder implements PasswordEncoderInterface
{
    public const MAX_PASSWORD_LENGTH = 4096;

    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $encoded): bool
    {
        return false;
    }

    /**
     * Demerges a merge password and salt string.
     *
     * @return array An array where the first element is the password and the second the salt
     */
    protected function demergePasswordAndSalt(string $mergedPasswordSalt)
    {
        if (empty($mergedPasswordSalt)) {
            return ['', ''];
        }

        $password = $mergedPasswordSalt;
        $salt = '';
        $saltBegins = strrpos($mergedPasswordSalt, '{');

        if (false !== $saltBegins && $saltBegins + 1 < \strlen($mergedPasswordSalt)) {
            $salt = substr($mergedPasswordSalt, $saltBegins + 1, -1);
            $password = substr($mergedPasswordSalt, 0, $saltBegins);
        }

        return [$password, $salt];
    }

    /**
     * Merges a password and a salt.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function mergePasswordAndSalt(string $password, ?string $salt)
    {
        if (empty($salt)) {
            return $password;
        }

        if (false !== strrpos($salt, '{') || false !== strrpos($salt, '}')) {
            throw new \InvalidArgumentException('Cannot use { or } in salt.');
        }

        return $password.'{'.$salt.'}';
    }

    /**
     * Compares two passwords.
     *
     * This method implements a constant-time algorithm to compare passwords to
     * avoid (remote) timing attacks.
     *
     * @return bool
     */
    protected function comparePasswords(string $password1, string $password2)
    {
        return hash_equals($password1, $password2);
    }

    /**
     * Checks if the password is too long.
     *
     * @return bool
     */
    protected function isPasswordTooLong(string $password)
    {
        return \strlen($password) > static::MAX_PASSWORD_LENGTH;
    }
}
