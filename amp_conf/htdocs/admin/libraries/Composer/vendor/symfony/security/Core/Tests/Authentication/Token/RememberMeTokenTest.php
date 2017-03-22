<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Authentication\Token;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Role\Role;

class RememberMeTokenTest extends TestCase
{
    public function testConstructor()
    {
        $user = $this->getUser();
        $token = new RememberMeToken($user, 'fookey', 'foo');

        $this->assertEquals('fookey', $token->getProviderKey());
        $this->assertEquals('foo', $token->getSecret());
        $this->assertEquals(array(new Role('ROLE_FOO')), $token->getRoles());
        $this->assertSame($user, $token->getUser());
        $this->assertTrue($token->isAuthenticated());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorSecretCannotBeNull()
    {
        new RememberMeToken(
            $this->getUser(),
            null,
            null
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorSecretCannotBeEmptyString()
    {
        new RememberMeToken(
            $this->getUser(),
            '',
            ''
        );
    }

    protected function getUser($roles = array('ROLE_FOO'))
    {
        $user = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMock();
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles))
        ;

        return $user;
    }
}
