<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Exception;

/**
 * An authentication exception where you can control the message shown to the user.
 *
 * Be sure that the message passed to this exception is something that
 * can be shown safely to your user. In other words, avoid catching
 * other exceptions and passing their message directly to this class.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class CustomUserMessageAuthenticationException extends AuthenticationException
{
    private $messageKey;

    private $messageData = array();

    public function __construct($message = '', array $messageData = array(), $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->setSafeMessage($message, $messageData);
    }

    /**
     * Set a message that will be shown to the user.
     *
     * @param string $messageKey  The message or message key
     * @param array  $messageData Data to be passed into the translator
     */
    public function setSafeMessage($messageKey, array $messageData = array())
    {
        $this->messageKey = $messageKey;
        $this->messageData = $messageData;
    }

    public function getMessageKey()
    {
        return $this->messageKey;
    }

    public function getMessageData()
    {
        return $this->messageData;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            parent::serialize(),
            $this->messageKey,
            $this->messageData,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list($parentData, $this->messageKey, $this->messageData) = unserialize($str);

        parent::unserialize($parentData);
    }
}
