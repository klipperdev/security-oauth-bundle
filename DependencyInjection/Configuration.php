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
use Klipper\Component\Security\Identity\SecurityIdentityInterface;
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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_security_oauth');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('user_provider')->defaultValue('users')->end()
            ->scalarNode('public_key')->defaultValue('%env(resolve:OAUTH2_PUBLIC_KEY)%')->end()
            ->scalarNode('private_key')->defaultValue('%env(resolve:OAUTH2_PRIVATE_KEY)%')->end()
            ->scalarNode('private_key_passphrase')->defaultValue('%env(OAUTH2_PRIVATE_KEY_PASSPHRASE)%')->end()
            ->booleanNode('private_key_permissions_check')->defaultTrue()->end()
            ->scalarNode('encryption_key')->defaultValue('%env(OAUTH2_ENCRYPTION_KEY)%')->end()
            ->end()
            ->append($this->getGrantsNode())
            ->append($this->getScopesNode())
            ->append($this->getSecurityVoterNode())
        ;

        return $treeBuilder;
    }

    private function getGrantsNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('grants')
            ->addDefaultsIfNotSet()
            ->append($this->getDefaultNode())
            ->append($this->getClientCredentialsGrantNode())
            ->append($this->getPasswordGrantNode())
            ->append($this->getAuthorizationCodeGrantNode())
            ->append($this->getRefreshTokenGrantNode())
            ->append($this->getImplicitGrantNode())
        ;
    }

    private function getDefaultNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('default')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('refresh_token_ttl')->defaultValue('P1M')->end()
            ->scalarNode('access_token_ttl')->defaultValue('PT1H')->end()
            ->end()
        ;
    }

    private function getClientCredentialsGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('client_credentials')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('access_token_ttl')->defaultNull()->end()
            ->end()
        ;
    }

    private function getPasswordGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('password')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('refresh_token_ttl')->defaultNull()->end()
            ->scalarNode('access_token_ttl')->defaultNull()->end()
            ->end()
        ;
    }

    private function getAuthorizationCodeGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('authorization_code')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('authorization_code_ttl')->defaultValue('PT10M')->end()
            ->scalarNode('refresh_token_ttl')->defaultNull()->end()
            ->scalarNode('access_token_ttl')->defaultNull()->end()
            ->end()
        ;
    }

    private function getRefreshTokenGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('refresh_token')
            ->addDefaultsIfNotSet()
            ->canBeDisabled()
            ->children()
            ->scalarNode('refresh_token_ttl')->defaultNull()->end()
            ->scalarNode('access_token_ttl')->defaultNull()->end()
            ->end()
        ;
    }

    private function getImplicitGrantNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('implicit')
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
            ->scalarNode('implicit_ttl')->defaultValue('PT1H')->end()
            ->scalarNode('access_token_ttl')->defaultNull()->end()
            ->end()
        ;
    }

    private function getScopesNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('scopes')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('allow_all_scopes')->defaultTrue()->end()
            ->arrayNode('availables')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }

    private function getSecurityVoterNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('security_voter')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('oauth_scope')
            ->defaultValue(interface_exists(SecurityIdentityInterface::class))
            ->end()
            ->end()
        ;
    }
}
