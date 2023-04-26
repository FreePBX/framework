<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\UserPassportInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched after authentication has successfully completed.
 *
 * At this stage, the authenticator created a token and
 * generated an authentication success response. Listeners to
 * this event can do actions related to successful authentication
 * (such as migrating the password).
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class LoginSuccessEvent extends Event
{
    private $authenticator;
    private $passport;
    private $authenticatedToken;
    private $previousToken;
    private $request;
    private $response;
    private $firewallName;

    /**
     * @param Passport $passport
     */
    public function __construct(AuthenticatorInterface $authenticator, PassportInterface $passport, TokenInterface $authenticatedToken, Request $request, ?Response $response, string $firewallName, TokenInterface $previousToken = null)
    {
        if (!$passport instanceof Passport) {
            trigger_deprecation('symfony/security-http', '5.4', 'Not passing an instance of "%s" as "$passport" argument of "%s()" is deprecated, "%s" given.', Passport::class, __METHOD__, get_debug_type($passport));
        }

        $this->authenticator = $authenticator;
        $this->passport = $passport;
        $this->authenticatedToken = $authenticatedToken;
        $this->previousToken = $previousToken;
        $this->request = $request;
        $this->response = $response;
        $this->firewallName = $firewallName;
    }

    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    public function getPassport(): PassportInterface
    {
        return $this->passport;
    }

    public function getUser(): UserInterface
    {
        // @deprecated since Symfony 5.4, passport will always have a user in 6.0
        if (!$this->passport instanceof UserPassportInterface) {
            throw new LogicException(sprintf('Cannot call "%s" as the authenticator ("%s") did not set a user.', __METHOD__, \get_class($this->authenticator)));
        }

        return $this->passport->getUser();
    }

    public function getAuthenticatedToken(): TokenInterface
    {
        return $this->authenticatedToken;
    }

    public function getPreviousToken(): ?TokenInterface
    {
        return $this->previousToken;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
