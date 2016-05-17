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

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\RememberMeListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\SecurityEvents;

class RememberMeListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnCoreSecurityDoesNotTryToPopulateNonEmptyTokenStorage()
    {
        list($listener, $tokenStorage) = $this->getListener();

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')))
        ;

        $tokenStorage
            ->expects($this->never())
            ->method('setToken')
        ;

        $this->assertNull($listener->handle($this->getGetResponseEvent()));
    }

    public function testOnCoreSecurityDoesNothingWhenNoCookieIsSet()
    {
        list($listener, $tokenStorage, $service) = $this->getListener();

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue(null))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue(new Request()))
        ;

        $this->assertNull($listener->handle($event));
    }

    public function testOnCoreSecurityIgnoresAuthenticationExceptionThrownByAuthenticationManagerImplementation()
    {
        list($listener, $tokenStorage, $service, $manager) = $this->getListener();

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')))
        ;

        $service
            ->expects($this->once())
            ->method('loginFail')
        ;

        $exception = new AuthenticationException('Authentication failed.');
        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException($exception))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue(new Request()))
        ;

        $listener->handle($event);
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Authentication failed.
     */
    public function testOnCoreSecurityIgnoresAuthenticationOptionallyRethrowsExceptionThrownAuthenticationManagerImplementation()
    {
        list($listener, $tokenStorage, $service, $manager) = $this->getListener(false, false);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')))
        ;

        $service
            ->expects($this->once())
            ->method('loginFail')
        ;

        $exception = new AuthenticationException('Authentication failed.');
        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException($exception))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue(new Request()))
        ;

        $listener->handle($event);
    }

    public function testOnCoreSecurity()
    {
        list($listener, $tokenStorage, $service, $manager) = $this->getListener();

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($token))
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue(new Request()))
        ;

        $listener->handle($event);
    }

    public function testSessionStrategy()
    {
        list($listener, $tokenStorage, $service, $manager, , $dispatcher, $sessionStrategy) = $this->getListener(false, true, true);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($token))
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $session = $this->getMock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->will($this->returnValue(true))
        ;

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->once())
            ->method('hasSession')
            ->will($this->returnValue(true))
        ;

        $request
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request))
        ;

        $sessionStrategy
            ->expects($this->once())
            ->method('onAuthentication')
            ->will($this->returnValue(null))
        ;

        $listener->handle($event);
    }

    public function testSessionIsMigratedByDefault()
    {
        list($listener, $tokenStorage, $service, $manager, , $dispatcher, $sessionStrategy) = $this->getListener(false, true, false);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($token))
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $session = $this->getMock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->will($this->returnValue(true))
        ;
        $session
            ->expects($this->once())
            ->method('migrate')
        ;

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request
            ->expects($this->any())
            ->method('hasSession')
            ->will($this->returnValue(true))
        ;

        $request
            ->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($session))
        ;

        $event = $this->getGetResponseEvent();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request))
        ;

        $listener->handle($event);
    }

    public function testOnCoreSecurityInteractiveLoginEventIsDispatchedIfDispatcherIsPresent()
    {
        list($listener, $tokenStorage, $service, $manager, , $dispatcher) = $this->getListener(true);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $service
            ->expects($this->once())
            ->method('autoLogin')
            ->will($this->returnValue($token))
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $manager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $event = $this->getGetResponseEvent();
        $request = new Request();
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request))
        ;

        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                SecurityEvents::INTERACTIVE_LOGIN,
                $this->isInstanceOf('Symfony\Component\Security\Http\Event\InteractiveLoginEvent')
            )
        ;

        $listener->handle($event);
    }

    protected function getGetResponseEvent()
    {
        return $this->getMock('Symfony\Component\HttpKernel\Event\GetResponseEvent', array(), array(), '', false);
    }

    protected function getFilterResponseEvent()
    {
        return $this->getMock('Symfony\Component\HttpKernel\Event\FilterResponseEvent', array(), array(), '', false);
    }

    protected function getListener($withDispatcher = false, $catchExceptions = true, $withSessionStrategy = false)
    {
        $listener = new RememberMeListener(
            $tokenStorage = $this->getTokenStorage(),
            $service = $this->getService(),
            $manager = $this->getManager(),
            $logger = $this->getLogger(),
            $dispatcher = ($withDispatcher ? $this->getDispatcher() : null),
            $catchExceptions,
            $sessionStrategy = ($withSessionStrategy ? $this->getSessionStrategy() : null)
        );

        return array($listener, $tokenStorage, $service, $manager, $logger, $dispatcher, $sessionStrategy);
    }

    protected function getLogger()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    protected function getManager()
    {
        return $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface');
    }

    protected function getService()
    {
        return $this->getMock('Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface');
    }

    protected function getTokenStorage()
    {
        return $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
    }

    protected function getDispatcher()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    private function getSessionStrategy()
    {
        return $this->getMock('\Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface');
    }
}
