<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\DependencyInjection;

use InvalidArgumentException;
use Lexik\Bundle\WorkflowBundle\Flow\NextStateInterface;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandlerPool;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Parser;

class LexikWorkflowExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('lexik_workflow.process_handler.class', $config['classes']['process_handler']);

        // build process definitions
        $processReferences = $this->buildProcesses($config['processes'], $container, $config['classes']['process'], $config['classes']['step']);
        $this->buildProcessHandlers($processReferences, $container, $config['classes']['process_handler']);

        // inject processes into ProcessAggregator (not possible from a CompilerPass because definitions are loaded from Extension class)
        if ($container->hasDefinition('lexik_workflow.process_aggregator')) {
            $container->findDefinition('lexik_workflow.process_aggregator')->replaceArgument(0, $processReferences);
        }
    }

    protected function buildProcesses(
        array $processes,
        ContainerBuilder $container,
        string $processClass,
        string $stepClass
    ): array {
        $processReferences = [];

        foreach ($processes as $processName => $processConfig) {
            if (!empty($processConfig['import'])) {
                if (is_file($processConfig['import'])) {
                    $yaml = new Parser();
                    $config = $yaml->parse(file_get_contents($processConfig['import']));

                    $processConfig = array_merge($processConfig, $config[$processName]);
                } else {
                    throw new InvalidArgumentException(sprintf('Can\'t load process from file "%s"',
                        $processConfig['import']));
                }
            }

            $stepReferences = $this->buildSteps($processName, $processConfig['steps'], $container, $stepClass);

            $definition = new Definition($processClass, [
                $processName,
                $stepReferences,
                $processConfig['start'],
                $processConfig['end'],
            ]);

            $definition
                ->setPublic(false)
                ->addTag('lexik_workflow.process', ['alias' => $processName]);

            $processReference = sprintf('lexik_workflow.process.%s', $processName);
            $container->setDefinition($processReference, $definition);

            $processReferences[$processName] = new Reference($processReference);
        }

        return $processReferences;
    }

    protected function buildSteps(
        string $processName,
        array $steps,
        ContainerBuilder $container,
        string $stepClass
    ): array {
        $stepReferences = [];

        foreach ($steps as $stepName => $stepConfig) {
            $definition = new Definition($stepClass, [
                $stepName,
                $stepConfig['label'],
                [],
                $stepConfig['model_status'],
                $stepConfig['roles'],
                $stepConfig['on_invalid'],
            ]);

            $this->addStepNextStates($definition, $stepConfig['next_states'], $processName);

            $definition->setPublic(false)
                ->addTag(sprintf('lexik_workflow.process.%s.step', $processName), ['alias' => $stepName]);

            $stepReference = sprintf('lexik_workflow.process.%s.step.%s', $processName, $stepName);
            $container->setDefinition($stepReference, $definition);

            $stepReferences[$stepName] = new Reference($stepReference);
        }

        return $stepReferences;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function addStepNextStates(Definition $step, array $stepsNextStates, string $processName): void
    {
        foreach ($stepsNextStates as $stateName => $data) {
            if (NextStateInterface::TYPE_STEP === $data['type']) {
                $step->addMethodCall('addNextState', [
                    $stateName,
                    $data['type'],
                    new Reference(sprintf('lexik_workflow.process.%s.step.%s', $processName, $data['target'])),
                ]);

            } elseif (NextStateInterface::TYPE_STEP_OR === $data['type']) {
                $targets = [];

                foreach ($data['target'] as $stepName => $condition) {
                    $serviceId = null;
                    $method = null;

                    if (!empty($condition)) {
                        [$serviceId, $method] = explode(':', $condition);
                    }

                    $targets[] = [
                        'target' => new Reference(sprintf('lexik_workflow.process.%s.step.%s', $processName,
                            $stepName)),
                        'condition_object' => null !== $serviceId ? new Reference($serviceId) : null,
                        'condition_method' => $method,
                    ];
                }

                $step->addMethodCall('addNextStateOr', [$stateName, $data['type'], $targets]);

            } elseif (NextStateInterface::TYPE_PROCESS === $data['type']) {
                $step->addMethodCall('addNextState', [
                    $stateName,
                    $data['type'],
                    new Reference(sprintf('lexik_workflow.process.%s', $data['target'])),
                ]);

            } else {
                throw new InvalidArgumentException(sprintf('Unknown type "%s", please use "step" or "process"',
                    $data['type']));
            }
        }
    }

    protected function buildProcessHandlers(
        array $processReferences,
        ContainerBuilder $container,
        string $processHandlerClass
    ): void {
        $pool = $container->getDefinition(ProcessHandlerPool::class);

        foreach ($processReferences as $processName => $processReference) {
            $definition = new Definition($processHandlerClass, [
                new Reference(sprintf('lexik_workflow.process.%s', $processName)),
                new Reference('lexik_workflow.model_storage'),
                new Reference('event_dispatcher'),
            ]);

            $definition->addMethodCall('setAuthorizationChecker', [new Reference('security.authorization_checker')]);

            $id = sprintf('lexik_workflow.handler.%s', $processName);

            $container->setDefinition($id, $definition);

            $pool
                ->addMethodCall('addProcessHandler', [$id, new Reference($id)])
                ->addMethodCall('addProcessHandler', [$processName, new Reference($id)]);
        }
    }
}
