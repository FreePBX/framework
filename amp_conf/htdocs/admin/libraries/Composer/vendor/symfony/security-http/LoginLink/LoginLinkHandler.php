<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\LoginLink;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;
use Symfony\Component\Security\Core\Signature\SignatureHasher;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\LoginLink\Exception\ExpiredLoginLinkException;
use Symfony\Component\Security\Http\LoginLink\Exception\InvalidLoginLinkException;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class LoginLinkHandler implements LoginLinkHandlerInterface
{
    private $urlGenerator;
    private $userProvider;
    private $options;
    private $signatureHasher;

    public function __construct(UrlGeneratorInterface $urlGenerator, UserProviderInterface $userProvider, SignatureHasher $signatureHasher, array $options)
    {
        $this->urlGenerator = $urlGenerator;
        $this->userProvider = $userProvider;
        $this->signatureHasher = $signatureHasher;
        $this->options = array_merge([
            'route_name' => null,
            'lifetime' => 600,
        ], $options);
    }

    public function createLoginLink(UserInterface $user, Request $request = null): LoginLinkDetails
    {
        $expires = time() + $this->options['lifetime'];
        $expiresAt = new \DateTimeImmutable('@'.$expires);

        $parameters = [
            // @deprecated since Symfony 5.3, change to $user->getUserIdentifier() in 6.0
            'user' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername(),
            'expires' => $expires,
            'hash' => $this->signatureHasher->computeSignatureHash($user, $expires),
        ];

        if ($request) {
            $currentRequestContext = $this->urlGenerator->getContext();
            $this->urlGenerator->setContext(
                (new RequestContext())
                    ->fromRequest($request)
                    ->setParameter('_locale', $request->getLocale())
            );
        }

        try {
            $url = $this->urlGenerator->generate(
                $this->options['route_name'],
                $parameters,
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } finally {
            if ($request) {
                $this->urlGenerator->setContext($currentRequestContext);
            }
        }

        return new LoginLinkDetails($url, $expiresAt);
    }

    public function consumeLoginLink(Request $request): UserInterface
    {
        $userIdentifier = $request->get('user');

        if (!$hash = $request->get('hash')) {
            throw new InvalidLoginLinkException('Missing "hash" parameter.');
        }
        if (!$expires = $request->get('expires')) {
            throw new InvalidLoginLinkException('Missing "expires" parameter.');
        }

        try {
            $this->signatureHasher->acceptSignatureHash($userIdentifier, $expires, $hash);

            // @deprecated since Symfony 5.3, change to $this->userProvider->loadUserByIdentifier() in 6.0
            if (method_exists($this->userProvider, 'loadUserByIdentifier')) {
                $user = $this->userProvider->loadUserByIdentifier($userIdentifier);
            } else {
                trigger_deprecation('symfony/security-core', '5.3', 'Not implementing method "loadUserByIdentifier()" in user provider "%s" is deprecated. This method will replace "loadUserByUsername()" in Symfony 6.0.', get_debug_type($this->userProvider));

                $user = $this->userProvider->loadUserByUsername($userIdentifier);
            }

            $this->signatureHasher->verifySignatureHash($user, $expires, $hash);
        } catch (UserNotFoundException $e) {
            throw new InvalidLoginLinkException('User not found.', 0, $e);
        } catch (ExpiredSignatureException $e) {
            throw new ExpiredLoginLinkException(ucfirst(str_ireplace('signature', 'login link', $e->getMessage())), 0, $e);
        } catch (InvalidSignatureException $e) {
            throw new InvalidLoginLinkException(ucfirst(str_ireplace('signature', 'login link', $e->getMessage())), 0, $e);
        }

        return $user;
    }
}
