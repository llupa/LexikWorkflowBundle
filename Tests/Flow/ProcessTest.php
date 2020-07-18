<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests\Flow;

use Doctrine\Common\Collections\ArrayCollection;
use Lexik\Bundle\WorkflowBundle\DependencyInjection\LexikWorkflowExtension;
use Lexik\Bundle\WorkflowBundle\Flow\Process;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Tests\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ProcessTest extends TestCase
{
    public function testProcessService(): void
    {
        $container = new ContainerBuilder();
        $container->set('next_state_condition', new stdClass());

        $extension = new LexikWorkflowExtension();
        $extension->load([$this->getConfig()], $container);

        $process = $container->get('lexik_workflow.process.document_proccess');
        self::assertTrue($process instanceof Process);
        self::assertTrue($process->getSteps() instanceof ArrayCollection);
        self::assertEquals(3, $process->getSteps()->count());
        self::assertTrue($process->getSteps()->get('step_create_doc') instanceof Step);
    }
}
