<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\DependencyInjection;

use Lexik\Bundle\WorkflowBundle\Flow\NextStateInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lexik_workflow');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->append($this->createClassesNodeDefinition())
            ->append($this->createProcessesNodeDefinition());

        return $treeBuilder;
    }

    private function createClassesNodeDefinition(): ArrayNodeDefinition
    {
        $classesNode = new ArrayNodeDefinition('classes');

        $classesNode
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('process_handler')
            ->defaultValue('Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler')
            ->end()
            ->scalarNode('process')
            ->defaultValue('Lexik\Bundle\WorkflowBundle\Flow\Process')
            ->end()
            ->scalarNode('step')
            ->defaultValue('Lexik\Bundle\WorkflowBundle\Flow\Step')
            ->end()
            ->end();

        return $classesNode;
    }

    private function createProcessesNodeDefinition(): ArrayNodeDefinition
    {
        $processesNode = new ArrayNodeDefinition('processes');

        $processesNode
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->validate()
            ->ifTrue(function ($value) {
                return !empty($value['import']) && !empty($value['steps']);
            })
            ->thenInvalid('You can\'t use "import" and "steps" keys at the same time.')
            ->end()
            ->children()
            ->scalarNode('import')
            ->defaultNull()
            ->end()
            ->scalarNode('start')
            ->defaultNull()
            ->end()
            ->arrayNode('end')
            ->defaultValue([])
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->append($this->createStepsNodeDefinition())
            ->end();

        return $processesNode;
    }

    private function createStepsNodeDefinition(): ArrayNodeDefinition
    {
        $stepsNode = new ArrayNodeDefinition('steps');

        $stepsNode
            ->defaultValue([])
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('label')
            ->defaultValue('')
            ->end()
            ->arrayNode('roles')
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('model_status')
            ->validate()
            ->ifTrue(function ($value) {
                return (is_array($value) && count($value) < 2);
            })
            ->thenInvalid('You must specify an array with [ method, constant ]')
            ->ifTrue(function ($value) {
                return (!defined($value[1]));
            })
            ->thenInvalid('You must specify a valid constant name as second parameter')
            ->end()
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('on_invalid')
            ->defaultNull()
            ->end()
            ->end()
            ->append($this->createNextStatesNodeDefinition())
            ->end();

        return $stepsNode;
    }

    private function createNextStatesNodeDefinition(): ArrayNodeDefinition
    {
        $flowTypes = [
            NextStateInterface::TYPE_STEP,
            NextStateInterface::TYPE_STEP_OR,
            NextStateInterface::TYPE_PROCESS,
        ];

        $nextStatesNode = new ArrayNodeDefinition('next_states');

        $nextStatesNode
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('type')
            ->defaultValue('step')
            ->validate()
            ->ifNotInArray($flowTypes)
            ->thenInvalid('Invalid next element type "%s". Please use one of the following types: '.implode(', ',
                    $flowTypes))
            ->end()
            ->end()
            ->variableNode('target')
            ->cannotBeEmpty()
            ->end()
            ->end()
            ->end();

        return $nextStatesNode;
    }
}
