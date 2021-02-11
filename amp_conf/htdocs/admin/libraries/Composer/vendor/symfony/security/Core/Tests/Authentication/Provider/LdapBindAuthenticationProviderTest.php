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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @requires extension ldap
 */
class LdapBindAuthenticationProviderTest extends TestCase
{
    public function testEmptyPasswordShouldThrowAnException()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\BadCredentialsException');
        $this->expectExceptionMessage('The presented password must not be empty.');
        $userProvider = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserProviderInterface')->getMock();
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $userChecker = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', '', 'key'));
    }

    public function testNullPasswordShouldThrowAnException()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\BadCredentialsException');
        $this->expectExceptionMessage('The presented password must not be empty.');
        $userProvider = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserProviderInterface')->getMock();
        $ldap = $this->getMockBuilder('Symfony\Component\Ldap\LdapInterface')->getMock();
        $userChecker = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', null, 'key'));
    }

    public function testBindFailureShouldThrowAnException()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\BadCredentialsException');
        $this->expectExceptionMessage('The presented password is invalid.');
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('bind')
            ->willThrowException(new ConnectionException())
        ;
        $userChecker = $this->getMockBuilder(UserCheckerInterface::class)->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', 'bar', 'key'));
    }

    public function testRetrieveUser()
    {
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('foo')
        ;
        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();

        $userChecker = $this->getMockBuilder(UserCheckerInterface::class)->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'retrieveUser');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, 'foo', new UsernamePasswordToken('foo', 'bar', 'key'));
    }

    public function testQueryForDn()
    {
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();

        $collection = new \ArrayIterator([new Entry('')]);

        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->with('foo', '')
            ->willReturn('foo')
        ;
        $ldap
            ->expects($this->once())
            ->method('query')
            ->with('{username}', 'foobar')
            ->willReturn($query)
        ;
        $userChecker = $this->getMockBuilder(UserCheckerInterface::class)->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $provider->setQueryString('{username}bar');
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', 'bar', 'key'));
    }

    public function testEmptyQueryResultShouldThrowAnException()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\BadCredentialsException');
        $this->expectExceptionMessage('The presented username is invalid.');
        $userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();

        $collection = $this->getMockBuilder(CollectionInterface::class)->getMock();

        $query = $this->getMockBuilder(QueryInterface::class)->getMock();
        $query
            ->expects($this->once())
            ->method('execute')
            ->willReturn($collection)
        ;

        $ldap = $this->getMockBuilder(LdapInterface::class)->getMock();
        $ldap
            ->expects($this->once())
            ->method('query')
            ->willReturn($query)
        ;
        $userChecker = $this->getMockBuilder(UserCheckerInterface::class)->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $provider->setQueryString('{username}bar');
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', 'bar', 'key'));
    }
}
