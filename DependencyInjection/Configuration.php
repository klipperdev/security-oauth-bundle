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

use Klipper\Bundle\SecurityBundle\DependencyInjection\NodeUtils;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_security_oauth');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('user_provider')->defaultValue('users')->end()
            ->scalarNode('private_key')->defaultValue('%env(resolve:OAUTH2_PRIVATE_KEY)%')->end()
            ->scalarNode('private_key_passphrase')->defaultValue('%env(OAUTH2_PRIVATE_KEY_PASSPHRASE)%')->end()
            ->booleanNode('private_key_permissions_check')->defaultTrue()->end()
            ->scalarNode('encryption_key')->defaultValue('%env(OAUTH2_ENCRYPTION_KEY)%')->end()
            ->end()
            ->append($this->getGrantsNode())
        ;

        return $treeBuilder;
    }

    private function getGrantsNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('grants')
            ->addDefaultsIfNotSet()
            ->append($this->getPasswordGrantNode())
        ;
    }

    private function getPasswordGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('password')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('refresh_token_ttl')->defaultValue('P1M')->end()
            ->scalarNode('access_token_ttl')->defaultValue('PT1H')->end()
            ->end()
        ;
    }
}
