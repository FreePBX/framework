<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;

class AnonymousAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $provider = $this->getProvider('foo');

        $this->assertTrue($provider->supports($this->getSupportedToken('foo')));
        $this->assertFalse($provider->supports($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')));
    }

    public function testAuthenticateWhenTokenIsNotSupported()
    {
        $provider = $this->getProvider('foo');

        $this->assertNull($provider->authenticate($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testAuthenticateWhenSecretIsNotValid()
    {
        $provider = $this->getProvider('foo');

        $provider->authenticate($this->getSupportedToken('bar'));
    }

    public function testAuthenticate()
    {
        $provider = $this->getProvider('foo');
        $token = $this->getSupportedToken('foo');

        $this->assertSame($token, $provider->authenticate($token));
    }

    protected function getSupportedToken($secret)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\AnonymousToken', array('getSecret'), array(), '', false);
        $token->expects($this->any())
              ->method('getSecret')
              ->will($this->returnValue($secret))
        ;

        return $token;
    }

    protected function getProvider($secret)
    {
        return new AnonymousAuthenticationProvider($secret);
    }
}
