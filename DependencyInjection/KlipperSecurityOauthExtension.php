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

use Klipper\Component\Security\Identity\SecurityIdentityInterface;
use Klipper\Component\SecurityOauth\Scope\Loader\SimpleScopeLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('oauth.xml');
        $loader->load('oauth_cache.xml');
        $loader->load('oauth_firewall.xml');
        $loader->load('oauth_listener.xml');
        $loader->load('command.xml');
        $loader->load('doctrine_subscriber.xml');
        $loader->load('exception_listener.xml');
        $loader->load('controller_token.xml');
        $loader->load('controller_authorize.xml');

        $container->setParameter('klipper_security_oauth.server.public_key', $config['public_key']);
        $container->setParameter('klipper_security_oauth.server.private_key', $config['private_key']);
        $container->setParameter('klipper_security_oauth.server.private_key_passphrase', $config['private_key_passphrase']);
        $container->setParameter('klipper_security_oauth.server.private_key_permissions_check', $config['private_key_permissions_check']);
        $container->setParameter('klipper_security_oauth.server.encryption_key', $config['encryption_key']);

        $defaultGrant = $config['grants']['default'];
        $grants = $config['grants'];
        $this->configureAuthenticationProvider($container, $config);
        $this->configureClientCredentialsGrant($container, $loader, $grants['client_credentials'], $defaultGrant);
        $this->configurePasswordGrant($container, $loader, $grants['password'], $defaultGrant);
        $this->configureAuthorizationCodeGrant($container, $loader, $grants['authorization_code'], $defaultGrant);
        $this->configureRefreshTokenGrant($container, $loader, $grants['refresh_token'], $defaultGrant);
        $this->configureImplicitGrant($container, $loader, $grants['implicit'], $defaultGrant);
        $this->configureScopes($container, $config['scopes']);
        $this->configureSecurityVoter($loader, $config['security_voter']);
        $this->configureSecurityIdentity($loader);
    }

    /**
     * @throws
     */
    private function configureAuthenticationProvider(ContainerBuilder $container, array $config): void
    {
        $authenticatorDef = $container->getDefinition('klipper_security_oauth.authenticator.oauth.authenticator');
        $authenticatorDef->replaceArgument(
            0,
            new Reference('security.user.provider.concrete.'.$config['user_provider'])
        );

        $accessTokenRepoDef = $container->getDefinition('klipper_security_oauth.user_authenticator');
        $accessTokenRepoDef->replaceArgument(
            2,
            new Reference('security.user.provider.concrete.'.$config['user_provider'])
        );
    }

    /**
     * @throws
     */
    private function configureClientCredentialsGrant(ContainerBuilder $container, FileLoader $loader, array $config, array $default): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('oauth_grant_client_credentials.xml');
        $serverDef = $container->getDefinition('klipper_security_oauth.authorization_server');
        $container->setParameter('klipper_security_oauth.grant.client_credentials.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.client_credentials'),
            new Reference('klipper_security_oauth.grant.client_credentials.access_token_ttl'),
        ]);
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
        $serverDef = $container->getDefinition('klipper_security_oauth.authorization_server');
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
    private function configureAuthorizationCodeGrant(ContainerBuilder $container, FileLoader $loader, array $config, array $default): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('oauth_grant_authorization_code.xml');
        $serverDef = $container->getDefinition('klipper_security_oauth.authorization_server');
        $container->setParameter('klipper_security_oauth.grant.authorization_code.authorization_code_ttl', $config['authorization_code_ttl']);
        $container->setParameter('klipper_security_oauth.grant.authorization_code.refresh_token_ttl', $config['refresh_token_ttl'] ?? $default['refresh_token_ttl']);
        $container->setParameter('klipper_security_oauth.grant.authorization_code.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.authorization_code'),
            new Reference('klipper_security_oauth.grant.authorization_code.access_token_ttl'),
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
        $serverDef = $container->getDefinition('klipper_security_oauth.authorization_server');
        $container->setParameter('klipper_security_oauth.grant.refresh_token.refresh_token_ttl', $config['refresh_token_ttl'] ?? $default['refresh_token_ttl']);
        $container->setParameter('klipper_security_oauth.grant.refresh_token.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.refresh_token'),
            new Reference('klipper_security_oauth.grant.refresh_token.access_token_ttl'),
        ]);
    }

    /**
     * @throws
     */
    private function configureImplicitGrant(ContainerBuilder $container, FileLoader $loader, array $config, array $default): void
    {
        if (!$config['enabled']) {
            return;
        }

        $loader->load('oauth_grant_implicit.xml');
        $serverDef = $container->getDefinition('klipper_security_oauth.authorization_server');
        $container->setParameter('klipper_security_oauth.grant.implicit.implicit_ttl', $config['implicit_ttl']);
        $container->setParameter('klipper_security_oauth.grant.implicit.access_token_ttl', $config['access_token_ttl'] ?? $default['access_token_ttl']);

        $serverDef->addMethodCall('enableGrantType', [
            new Reference('klipper_security_oauth.grant.implicit'),
            new Reference('klipper_security_oauth.grant.implicit.access_token_ttl'),
        ]);
    }

    private function configureScopes(ContainerBuilder $container, array $config): void
    {
        $configDir = $container->getParameter('kernel.project_dir').'/config';

        $container->setParameter(
            'klipper_security_oauth.repository.scope.allow_all_scopes',
            $config['allow_all_scopes']
        );

        $container->setDefinition(
            'klipper_security_oauth.scope.loader.config',
            (new Definition(SimpleScopeLoader::class, [$config['availables']]))
                ->addTag('klipper_security_oauth.scope_loader')
                ->addMethodCall('addResource', [
                    new Definition(DirectoryResource::class, [$configDir]),
                ])
        );
    }

    /**
     * @throws
     */
    private function configureSecurityVoter(FileLoader $loader, array $config): void
    {
        if ($config['oauth_scope']) {
            $loader->load('security_voter_oauth_scope.xml');
        }
    }

    /**
     * @throws
     */
    private function configureSecurityIdentity(FileLoader $loader): void
    {
        if (interface_exists(SecurityIdentityInterface::class)) {
            $loader->load('security_identity_listener.xml');
        }
    }
}
