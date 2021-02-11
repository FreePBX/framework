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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserListenerTest extends TestCase
{
    private $tokenStorage;

    private $userProvider;

    private $userChecker;

    private $accessDecisionManager;

    private $request;

    private $event;

    protected function setUp()
    {
        $this->tokenStorage = new TokenStorage();
        $this->userProvider = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserProviderInterface')->getMock();
        $this->userChecker = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserCheckerInterface')->getMock();
        $this->accessDecisionManager = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface')->getMock();
        $this->request = new Request();
        $this->event = new GetResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $this->request, HttpKernelInterface::MASTER_REQUEST);
    }

    public function testProviderKeyIsRequired()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('$providerKey must not be empty');
        new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, '', $this->accessDecisionManager);
    }

    public function testEventIsIgnoredIfUsernameIsNotPassedWithTheRequest()
    {
        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);

        $this->assertNull($this->event->getResponse());
        $this->assertNull($this->tokenStorage->getToken());
    }

    public function testExitUserThrowsAuthenticationExceptionIfNoCurrentToken()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException');
        $this->tokenStorage->setToken(null);
        $this->request->query->set('_switch_user', '_exit');
        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);
    }

    public function testExitUserThrowsAuthenticationExceptionIfOriginalTokenCannotBeFound()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException');
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', SwitchUserListener::EXIT_VALUE);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);
    }

    public function testExitUserUpdatesToken()
    {
        $originalToken = new UsernamePasswordToken('username', '', 'key', []);
        $this->tokenStorage->setToken(new UsernamePasswordToken('username', '', 'key', [new SwitchUserRole('ROLE_PREVIOUS', $originalToken)]));

        $this->request->query->set('_switch_user', SwitchUserListener::EXIT_VALUE);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);

        $this->assertSame([], $this->request->query->all());
        $this->assertSame('', $this->request->server->get('QUERY_STRING'));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $this->event->getResponse());
        $this->assertSame($this->request->getUri(), $this->event->getResponse()->getTargetUrl());
        $this->assertSame($originalToken, $this->tokenStorage->getToken());
    }

    public function testExitUserDispatchesEventWithRefreshedUser()
    {
        $originalUser = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMock();
        $refreshedUser = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMock();
        $this
            ->userProvider
            ->expects($this->any())
            ->method('refreshUser')
            ->with($originalUser)
            ->willReturn($refreshedUser);
        $originalToken = new UsernamePasswordToken($originalUser, '', 'key');
        $this->tokenStorage->setToken(new UsernamePasswordToken('username', '', 'key', [new SwitchUserRole('ROLE_PREVIOUS', $originalToken)]));
        $this->request->query->set('_switch_user', SwitchUserListener::EXIT_VALUE);

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(SecurityEvents::SWITCH_USER, $this->callback(function (SwitchUserEvent $event) use ($refreshedUser) {
                return $event->getTargetUser() === $refreshedUser;
            }))
        ;

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager, null, '_switch_user', 'ROLE_ALLOWED_TO_SWITCH', $dispatcher);
        $listener->handle($this->event);
    }

    public function testExitUserDoesNotDispatchEventWithStringUser()
    {
        $originalUser = 'anon.';
        $this
            ->userProvider
            ->expects($this->never())
            ->method('refreshUser');
        $originalToken = new UsernamePasswordToken($originalUser, '', 'key');
        $this->tokenStorage->setToken(new UsernamePasswordToken('username', '', 'key', [new SwitchUserRole('ROLE_PREVIOUS', $originalToken)]));
        $this->request->query->set('_switch_user', SwitchUserListener::EXIT_VALUE);

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $dispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager, null, '_switch_user', 'ROLE_ALLOWED_TO_SWITCH', $dispatcher);
        $listener->handle($this->event);
    }

    public function testSwitchUserIsDisallowed()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\AccessDeniedException');
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', 'kuba');

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(false);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);
    }

    public function testSwitchUser()
    {
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);
        $user = new User('username', 'password', []);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', 'kuba');

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')->with('kuba')
            ->willReturn($user);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')->with($user);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);

        $this->assertSame([], $this->request->query->all());
        $this->assertSame('', $this->request->server->get('QUERY_STRING'));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $this->tokenStorage->getToken());
    }

    public function testSwitchUserAlreadySwitched()
    {
        $originalToken = new UsernamePasswordToken('original', null, 'key', ['ROLE_FOO']);
        $alreadySwitchedToken = new UsernamePasswordToken('switched_1', null, 'key', [new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $originalToken)]);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($alreadySwitchedToken);

        $targetUser = new User('kuba', 'password', ['ROLE_FOO', 'ROLE_BAR']);
        $this->request->query->set('_switch_user', 'kuba');

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($originalToken, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);
        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('kuba')
            ->willReturn($targetUser);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')->with($targetUser);

        $listener = new SwitchUserListener($tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager, null, '_switch_user', 'ROLE_ALLOWED_TO_SWITCH', null, false);
        $listener->handle($this->event);

        $this->assertSame([], $this->request->query->all());
        $this->assertSame('', $this->request->server->get('QUERY_STRING'));
        $this->assertSame('kuba', $tokenStorage->getToken()->getUsername());
        $this->assertSame($originalToken, $tokenStorage->getToken()->getRoles()[2]->getSource());
    }

    public function testSwitchUserWorksWithFalsyUsernames()
    {
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);
        $user = new User('username', 'password', []);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', '0');

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')->with('0')
            ->willReturn($user);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')->with($user);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);

        $this->assertSame([], $this->request->query->all());
        $this->assertSame('', $this->request->server->get('QUERY_STRING'));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $this->tokenStorage->getToken());
    }

    public function testSwitchUserKeepsOtherQueryStringParameters()
    {
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);
        $user = new User('username', 'password', []);

        $this->tokenStorage->setToken($token);
        $this->request->query->replace([
            '_switch_user' => 'kuba',
            'page' => 3,
            'section' => 2,
        ]);

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')->with('kuba')
            ->willReturn($user);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')->with($user);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);

        $this->assertSame('page=3&section=2', $this->request->server->get('QUERY_STRING'));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $this->tokenStorage->getToken());
    }

    public function testSwitchUserWithReplacedToken()
    {
        $user = new User('username', 'password', []);
        $token = new UsernamePasswordToken($user, '', 'provider123', ['ROLE_FOO']);

        $user = new User('replaced', 'password', []);
        $replacedToken = new UsernamePasswordToken($user, '', 'provider123', ['ROLE_BAR']);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', 'kuba');

        $this->accessDecisionManager->expects($this->any())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);

        $this->userProvider->expects($this->any())
            ->method('loadUserByUsername')->with('kuba')
            ->willReturn($user);

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(SecurityEvents::SWITCH_USER,
                $this->callback(function (SwitchUserEvent $event) use ($replacedToken, $user) {
                    if ($user !== $event->getTargetUser()) {
                        return false;
                    }
                    $event->setToken($replacedToken);

                    return true;
                }));

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager, null, '_switch_user', 'ROLE_ALLOWED_TO_SWITCH', $dispatcher);
        $listener->handle($this->event);

        $this->assertSame($replacedToken, $this->tokenStorage->getToken());
    }

    public function testSwitchUserThrowsAuthenticationExceptionIfNoCurrentToken()
    {
        $this->expectException('Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException');
        $this->tokenStorage->setToken(null);
        $this->request->query->set('_switch_user', 'username');
        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager);
        $listener->handle($this->event);
    }

    public function testSwitchUserStateless()
    {
        $token = new UsernamePasswordToken('username', '', 'key', ['ROLE_FOO']);
        $user = new User('username', 'password', []);

        $this->tokenStorage->setToken($token);
        $this->request->query->set('_switch_user', 'kuba');

        $this->accessDecisionManager->expects($this->once())
            ->method('decide')->with($token, ['ROLE_ALLOWED_TO_SWITCH'])
            ->willReturn(true);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')->with('kuba')
            ->willReturn($user);
        $this->userChecker->expects($this->once())
            ->method('checkPostAuth')->with($user);

        $listener = new SwitchUserListener($this->tokenStorage, $this->userProvider, $this->userChecker, 'provider123', $this->accessDecisionManager, null, '_switch_user', 'ROLE_ALLOWED_TO_SWITCH', null, true);
        $listener->handle($this->event);

        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $this->tokenStorage->getToken());
        $this->assertFalse($this->event->hasResponse());
    }
}
