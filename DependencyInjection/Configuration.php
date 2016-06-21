<?php

/*
 * This file is part of the Stinger Enity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\EntitySearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

	/**
	 *
	 * {@inheritDoc}
	 *
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder();
		$root = $treeBuilder->root('stinger_soft_search');
		// @formatter:off
		$root->children()
			->arrayNode('types')
			->useAttributeAsKey('name')
			->prototype('array')
				->children()
					->arrayNode('mappings')
						->useAttributeAsKey('name')
						->cannotBeEmpty()
						->prototype('array')
							->children()
								->scalarNode('propertyPath')->defaultValue(false)->end()
							->end()
						->end()
					->end()
					->arrayNode('persistence')
						->children()
							->scalarNode('model')->end()
						->end()
					->end()
				->end()
			->end()
		->end();
		// @formatter:on
		
		return $treeBuilder;
	}
}
