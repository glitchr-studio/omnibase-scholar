<?php

namespace Base\Scholar\DependencyInjection;

use Base\Bundle\AbstractBaseConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ScholarConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('max_per_source')->min(1)->defaultValue(2000)
                    ->info('The most works scholar:sync reads from one source for one scholar.')->end()
                ->scalarNode('metrics_source')->defaultValue('openalex')
                    ->info('The omnischolar source whose author profile gives the counts (works, citations, h-index); null: none.')->end()
                ->scalarNode('cv_source')->defaultValue('orcid')
                    ->info('The omnischolar source the CV is read from (positions, degrees, distinctions), when the scholar has a profile there; null: the CV is typed only.')->end()
                ->integerNode('per_page')->min(1)->defaultValue(100)
                    ->info('Publications listed per page.')->end()
                ->booleanNode('jsonld')->defaultTrue()
                    ->info('schema.org Person, ScholarlyArticle and Book on the pages.')->end()
                ->booleanNode('abstracts_in_exports')->defaultFalse()
                    ->info('Write the abstracts into the BibTeX and RIS exports.')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
