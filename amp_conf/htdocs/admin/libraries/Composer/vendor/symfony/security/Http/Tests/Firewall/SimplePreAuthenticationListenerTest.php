<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Tests\Firewall;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Firewall\SimplePreAuthenticationListener;
use Symfony\Component\Security\Http\SecurityEvents;

class SimplePreAuthenticationListenerTest extends TestCase
{
    private $authenticationManager;
    private $dispatcher;
    private $event;
    private $logger;
    private $request;
    private $tokenStorage;
    private $token;

    public function testHandle()
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($this->token))
        ;

        $this->authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($this->token))
            ->will($this->returnValue($this->token))
        ;

        $simpleAuthenticator = $this->getMockBuilder('Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface')->getMock();
        $simpleAuthenticator
            ->expects($this->once())
            ->method('createToken')
            ->with($this->equalTo($this->request), $this->equalTo('secured_area'))
            ->will($this->returnValue($this->token))
        ;

        $loginEvent = new InteractiveLoginEvent($this->request, $this->token);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(SecurityEvents::INTERACTIVE_LOGIN), $this->equalTo($loginEvent))
        ;

        $listener = new SimplePreAuthenticationListener($this->tokenStorage, $this->authenticationManager, 'secured_area', $simpleAuthenticator, $this->logger, $this->dispatcher);

        $listener->handle($this->event);
    }

    public function testHandlecatchAuthenticationException()
    {
        $exception = new AuthenticationException('Authentication failed.');

        $this->authenticationManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($this->token))
            ->willThrowException($exception)
        ;

        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo(null))
        ;

        $simpleAuthenticator = $this->getMockBuilder('Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface')->getMock();
        $simpleAuthenticator
            ->expects($this->once())
            ->method('createToken')
            ->with($this->equalTo($this->request), $this->equalTo('secured_area'))
            ->will($this->returnValue($this->token))
        ;

        $listener = new SimplePreAuthenticationListener($this->tokenStorage, $this->authenticationManager, 'secured_area', $simpleAuthenticator, $this->logger, $this->dispatcher);

        $listener->handle($this->event);
    }

    protected function setUp()
    {
        $this->authenticationManager = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();

        $this->request = new Request([], [], [], [], [], []);

        $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')->disableOriginalConstructor()->getMock();
        $this->event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request))
        ;

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $this->tokenStorage = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')->getMock();
        $this->token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
    }

    protected function tearDown()
    {
        $this->authenticationManager = null;
        $this->dispatcher = null;
        $this->event = null;
        $this->logger = null;
        $this->request = null;
        $this->tokenStorage = null;
        $this->token = null;
    }
}
