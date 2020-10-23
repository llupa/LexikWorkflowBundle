<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests\DependencyInjection;

use Lexik\Bundle\WorkflowBundle\DependencyInjection\LexikWorkflowExtension;
use Lexik\Bundle\WorkflowBundle\Flow\Process;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessAggregator;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandlerPool;
use Lexik\Bundle\WorkflowBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class LexikWorkflowExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        // fake entity manager and security context services
        $container->set('doctrine.orm.entity_manager', $this->getSqliteEntityManager());
        $container->set('security.authorization_checker', $this->getMockAuthorizationChecker());
        $container->set('event_dispatcher', new EventDispatcher());
        $container->set('next_state_condition', new stdClass());

        // simple config
        $extension = new LexikWorkflowExtension();
        $extension->load([$this->getSimpleConfig()], $container);

        self::assertTrue($container->getDefinition('lexik_workflow.process.document_proccess') instanceof Definition);

        // config with a process
        $extension = new LexikWorkflowExtension();
        $extension->load([$this->getConfig()], $container);

        self::assertTrue($container->getDefinition('lexik_workflow.process.document_proccess') instanceof Definition);
        self::assertTrue($container->getDefinition('lexik_workflow.process.document_proccess.step.step_create_doc') instanceof Definition);
        self::assertTrue($container->getDefinition('lexik_workflow.process.document_proccess.step.step_validate_doc') instanceof Definition);
        self::assertTrue($container->getDefinition('lexik_workflow.process.document_proccess.step.step_remove_doc') instanceof Definition);
        self::assertTrue($container->getDefinition('lexik_workflow.handler.document_proccess') instanceof Definition);

        $processHandlerFactory = $container->get('lexik_workflow.process_aggregator');

        self::assertTrue($processHandlerFactory instanceof ProcessAggregator);
        self::assertTrue($processHandlerFactory->getProcess('document_proccess') instanceof Process);

        $processHandler = $container->get('lexik_workflow.handler.document_proccess');

        self::assertTrue($processHandler instanceof ProcessHandler);

        $processHandlerPool = $container->get(ProcessHandlerPool::class);

        self::assertInstanceOf(ProcessHandlerPool::class, $processHandlerPool);
        self::assertInstanceOf(ProcessHandler::class, $processHandlerPool->getProcessHandler('lexik_workflow.handler.document_proccess'));
        self::assertInstanceOf(ProcessHandler::class, $processHandlerPool->getProcessHandler('document_proccess'));
        self::assertNull($processHandlerPool->getProcessHandler('lexik_workflow.handler.fake_proccess'));
        self::assertNull($processHandlerPool->getProcessHandler('fake_proccess'));

        self::assertSame($processHandler, $processHandlerPool->getProcessHandler('lexik_workflow.handler.document_proccess'));
    }
}
