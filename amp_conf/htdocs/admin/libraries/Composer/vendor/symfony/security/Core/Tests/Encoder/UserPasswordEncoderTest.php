<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Tests\Encoder;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserPasswordEncoderTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodePassword()
    {
        $userMock = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMock();
        $userMock->expects($this->any())
            ->method('getSalt')
            ->will($this->returnValue('userSalt'));

        $mockEncoder = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface')->getMock();
        $mockEncoder->expects($this->any())
            ->method('encodePassword')
            ->with($this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->will($this->returnValue('encodedPassword'));

        $mockEncoderFactory = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface')->getMock();
        $mockEncoderFactory->expects($this->any())
            ->method('getEncoder')
            ->with($this->equalTo($userMock))
            ->will($this->returnValue($mockEncoder));

        $passwordEncoder = new UserPasswordEncoder($mockEncoderFactory);

        $encoded = $passwordEncoder->encodePassword($userMock, 'plainPassword');
        $this->assertEquals('encodedPassword', $encoded);
    }

    public function testIsPasswordValid()
    {
        $userMock = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')->getMock();
        $userMock->expects($this->any())
            ->method('getSalt')
            ->will($this->returnValue('userSalt'));
        $userMock->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue('encodedPassword'));

        $mockEncoder = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface')->getMock();
        $mockEncoder->expects($this->any())
            ->method('isPasswordValid')
            ->with($this->equalTo('encodedPassword'), $this->equalTo('plainPassword'), $this->equalTo('userSalt'))
            ->will($this->returnValue(true));

        $mockEncoderFactory = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface')->getMock();
        $mockEncoderFactory->expects($this->any())
            ->method('getEncoder')
            ->with($this->equalTo($userMock))
            ->will($this->returnValue($mockEncoder));

        $passwordEncoder = new UserPasswordEncoder($mockEncoderFactory);

        $isValid = $passwordEncoder->isPasswordValid($userMock, 'plainPassword');
        $this->assertTrue($isValid);
    }
}
