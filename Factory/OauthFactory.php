<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityOauthBundle\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class OauthFactory implements AuthenticatorFactoryInterface
{
    public function getPriority(): int
    {
        return -10;
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): array
    {
        if (!$config['enabled']) {
            return [];
        }

        $authenticatorId = 'klipper_security_oauth.authenticator.oauth.authenticator.'.$firewallName;
        $container
            ->setDefinition($authenticatorId, new ChildDefinition('klipper_security_oauth.authenticator.oauth.authenticator'))
        ;

        return [$authenticatorId];
    }

    public function getKey(): string
    {
        return 'oauth';
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        /* @var ArrayNodeDefinition $builder */
        $builder
            ->canBeEnabled()
        ;
    }
}
