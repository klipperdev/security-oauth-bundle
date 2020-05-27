<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\SecurityOauthBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperSecurityOauthExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('oauth.xml');
        $loader->load('command.xml');
        $loader->load('doctrine_subscriber.xml');

        $container->setParameter('klipper_security_oauth.server.private_key', $config['private_key']);
        $container->setParameter('klipper_security_oauth.server.private_key_passphrase', $config['private_key_passphrase']);
        $container->setParameter('klipper_security_oauth.server.private_key_permissions_check', $config['private_key_permissions_check']);
        $container->setParameter('klipper_security_oauth.server.encryption_key', $config['encryption_key']);

        $defaultGrant = $config['grants']['default'];
        $grants = $config['grants'];
        $this->configureUserRepository($container, $config);
        $this->configurePasswordGrant($container, $loader, $grants['password'], $defaultGrant);
        $this->configureRefreshTokenGrant($container, $loader, $grants['refresh_token'], $defaultGrant);
    }

    /**
     * @throws
     */
    private function configureUserRepository(ContainerBuilder $container, array $config): void
    {
        $userRepoDef = $container->getDefinition('klipper_security_oauth.repository.user');
        $userRepoDef->replaceArgument(
            0,
            new Reference('security.user.provider.concrete.'.$config['user_provider'])
        );
    }

    /**
     * @throws
     */
    private function configurePasswordGrant(ContainerBuilder $container, FileLoader $loader, array $config, array $default): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('oauth_grant_password.xml');
        $serverDef = $container->getDefinition('klipper_security_oauth.server');
        $container->setParameter('klipper_security_oauth.grant.password.refresh_token_ttl', $config['refresh_token_ttl'] ?? $default['refresh_token_ttl']);
        $container->setParameter('klipper_security_oauth.grant.password.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.password'),
            new Reference('klipper_security_oauth.grant.password.access_token_ttl'),
        ]);
    }

    /**
     * @throws
     */
    private function configureRefreshTokenGrant(ContainerBuilder $container, FileLoader $loader, array $config, array $default): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('oauth_grant_refresh_token.xml');
        $serverDef = $container->getDefinition('klipper_security_oauth.server');
        $container->setParameter('klipper_security_oauth.grant.refresh_token.refresh_token_ttl', $config['refresh_token_ttl'] ?? $default['refresh_token_ttl']);
        $container->setParameter('klipper_security_oauth.grant.refresh_token.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.refresh_token'),
            new Reference('klipper_security_oauth.grant.refresh_token.access_token_ttl'),
        ]);
    }
}
