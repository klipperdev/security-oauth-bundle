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

        $container->setParameter('klipper_security_oauth.server.private_key', $config['private_key']);
        $container->setParameter('klipper_security_oauth.server.private_key_passphrase', $config['private_key_passphrase']);
        $container->setParameter('klipper_security_oauth.server.private_key_permissions_check', $config['private_key_permissions_check']);
        $container->setParameter('klipper_security_oauth.server.encryption_key', $config['encryption_key']);

        $this->configureUserRepository($container, $config);
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
}
