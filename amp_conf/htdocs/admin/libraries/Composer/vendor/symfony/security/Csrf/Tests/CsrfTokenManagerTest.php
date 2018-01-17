<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Csrf\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CsrfTokenManagerTest extends TestCase
{
    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testGetNonExistingToken($namespace, $manager, $storage, $generator)
    {
        $storage->expects($this->once())
            ->method('hasToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue(false));

        $generator->expects($this->once())
            ->method('generateToken')
            ->will($this->returnValue('TOKEN'));

        $storage->expects($this->once())
            ->method('setToken')
            ->with($namespace.'token_id', 'TOKEN');

        $token = $manager->getToken('token_id');

        $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token);
        $this->assertSame('token_id', $token->getId());
        $this->assertSame('TOKEN', $token->getValue());
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testUseExistingTokenIfAvailable($namespace, $manager, $storage)
    {
        $storage->expects($this->once())
            ->method('hasToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue(true));

        $storage->expects($this->once())
            ->method('getToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue('TOKEN'));

        $token = $manager->getToken('token_id');

        $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token);
        $this->assertSame('token_id', $token->getId());
        $this->assertSame('TOKEN', $token->getValue());
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testRefreshTokenAlwaysReturnsNewToken($namespace, $manager, $storage, $generator)
    {
        $storage->expects($this->never())
            ->method('hasToken');

        $generator->expects($this->once())
            ->method('generateToken')
            ->will($this->returnValue('TOKEN'));

        $storage->expects($this->once())
            ->method('setToken')
            ->with($namespace.'token_id', 'TOKEN');

        $token = $manager->refreshToken('token_id');

        $this->assertInstanceOf('Symfony\Component\Security\Csrf\CsrfToken', $token);
        $this->assertSame('token_id', $token->getId());
        $this->assertSame('TOKEN', $token->getValue());
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testMatchingTokenIsValid($namespace, $manager, $storage)
    {
        $storage->expects($this->once())
            ->method('hasToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue(true));

        $storage->expects($this->once())
            ->method('getToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue('TOKEN'));

        $this->assertTrue($manager->isTokenValid(new CsrfToken('token_id', 'TOKEN')));
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testNonMatchingTokenIsNotValid($namespace, $manager, $storage)
    {
        $storage->expects($this->once())
            ->method('hasToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue(true));

        $storage->expects($this->once())
            ->method('getToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue('TOKEN'));

        $this->assertFalse($manager->isTokenValid(new CsrfToken('token_id', 'FOOBAR')));
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testNonExistingTokenIsNotValid($namespace, $manager, $storage)
    {
        $storage->expects($this->once())
            ->method('hasToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue(false));

        $storage->expects($this->never())
            ->method('getToken');

        $this->assertFalse($manager->isTokenValid(new CsrfToken('token_id', 'FOOBAR')));
    }

    /**
     * @dataProvider getManagerGeneratorAndStorage
     */
    public function testRemoveToken($namespace, $manager, $storage)
    {
        $storage->expects($this->once())
            ->method('removeToken')
            ->with($namespace.'token_id')
            ->will($this->returnValue('REMOVED_TOKEN'));

        $this->assertSame('REMOVED_TOKEN', $manager->removeToken('token_id'));
    }

    public function testNamespaced()
    {
        $generator = $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface')->getMock();
        $storage = $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface')->getMock();

        $requestStack = new RequestStack();
        $requestStack->push(new Request(array(), array(), array(), array(), array(), array('HTTPS' => 'on')));

        $manager = new CsrfTokenManager($generator, $storage, null, $requestStack);

        $token = $manager->getToken('foo');
        $this->assertSame('foo', $token->getId());
    }

    public function getManagerGeneratorAndStorage()
    {
        $data = array();

        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('', new CsrfTokenManager($generator, $storage, ''), $storage, $generator);

        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('https-', new CsrfTokenManager($generator, $storage), $storage, $generator);

        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('aNamespace-', new CsrfTokenManager($generator, $storage, 'aNamespace-'), $storage, $generator);

        $requestStack = new RequestStack();
        $requestStack->push(new Request(array(), array(), array(), array(), array(), array('HTTPS' => 'on')));
        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('https-', new CsrfTokenManager($generator, $storage, $requestStack), $storage, $generator);

        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('generated-', new CsrfTokenManager($generator, $storage, function () {
            return 'generated-';
        }), $storage, $generator);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        list($generator, $storage) = $this->getGeneratorAndStorage();
        $data[] = array('', new CsrfTokenManager($generator, $storage, $requestStack), $storage, $generator);

        return $data;
    }

    private function getGeneratorAndStorage()
    {
        return array(
            $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface')->getMock(),
            $this->getMockBuilder('Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface')->getMock(),
        );
    }

    public function setUp()
    {
        $_SERVER['HTTPS'] = 'on';
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($_SERVER['HTTPS']);
    }
}
