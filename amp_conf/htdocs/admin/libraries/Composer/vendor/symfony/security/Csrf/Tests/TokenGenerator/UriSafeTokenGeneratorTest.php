<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Csrf\Tests\TokenGenerator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriSafeTokenGeneratorTest extends TestCase
{
    const ENTROPY = 1000;

    /**
     * A non alpha-numeric byte string.
     *
     * @var string
     */
    private static $bytes;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $random;

    /**
     * @var UriSafeTokenGenerator
     */
    private $generator;

    public static function setUpBeforeClass()
    {
        self::$bytes = base64_decode('aMf+Tct/RLn2WQ==');
    }

    protected function setUp()
    {
        $this->generator = new UriSafeTokenGenerator(self::ENTROPY);
    }

    protected function tearDown()
    {
        $this->random = null;
        $this->generator = null;
    }

    public function testGenerateToken()
    {
        $token = $this->generator->generateToken();

        $this->assertTrue(ctype_print($token), 'is printable');
        $this->assertStringNotMatchesFormat('%S+%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S/%S', $token, 'is URI safe');
        $this->assertStringNotMatchesFormat('%S=%S', $token, 'is URI safe');
    }
}
