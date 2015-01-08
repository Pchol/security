<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Http\Firewall;

use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * AnonymousAuthenticationListener automatically adds a Token if none is
 * already present.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AnonymousAuthenticationListener implements ListenerInterface
{
    private $tokenStorage;
    private $key;
    private $authenticationManager;
    private $logger;

    /**
     * @param SecurityContextInterface|TokenStorageInterface
     *
     * Passing a SecurityContextInterface as a first argument was deprecated in 2.7 and will be removed in 3.0
     */
    public function __construct($tokenStorage, $key, LoggerInterface $logger = null, AuthenticationManagerInterface $authenticationManager = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->key = $key;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
    }

    /**
     * Handles anonymous authentication.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->tokenStorage->getToken()) {
            return;
        }

        try {
            $token = new AnonymousToken($this->key, 'anon.', array());
            if (null !== $this->authenticationManager) {
                $token = $this->authenticationManager->authenticate($token);
            }

            $this->tokenStorage->setToken($token);

            if (null !== $this->logger) {
                $this->logger->info('Populated TokenStorage with an anonymous Token');
            }
        } catch (AuthenticationException $failed) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf('Anonymous authentication failed: %s', $failed->getMessage()));
            }
        }
    }
}
