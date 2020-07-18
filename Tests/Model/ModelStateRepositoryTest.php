<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests\Model;

use DateTime;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Tests\TestCase;

final class ModelStateRepositoryTest extends TestCase
{
    private $em;

    public function testFindLatestModelState(): void
    {
        $this->createData();

        $repository = $this->em->getRepository('Lexik\Bundle\WorkflowBundle\Entity\ModelState');

        self::assertInstanceOf('Lexik\Bundle\WorkflowBundle\Model\ModelStateRepository', $repository);

        self::assertNull($repository->findLatestModelState('id', 'process'));
        self::assertNull($repository->findLatestModelState('a1b2c3', 'process_?'));
        self::assertNull($repository->findLatestModelState('a1b2c3333', 'process_1'));

        $model = $repository->findLatestModelState('a1b2c3', 'process_1');
        self::assertEquals('a1b2c3', $model->getWorkflowIdentifier());
        self::assertEquals('2012-02-14', $model->getCreatedAt()->format('Y-m-d'));
        self::assertEquals('process_1', $model->getProcessName());
        self::assertEquals('step_B', $model->getStepName());

        $model = $repository->findLatestModelState('a1b2c3', 'process_1', 'step_A');
        self::assertEquals('a1b2c3', $model->getWorkflowIdentifier());
        self::assertEquals('2012-02-12', $model->getCreatedAt()->format('Y-m-d'));
        self::assertEquals('process_1', $model->getProcessName());
        self::assertEquals('step_A', $model->getStepName());
    }

    protected function createData(): void
    {
        $model1 = new ModelState();
        $model1->setWorkflowIdentifier('a1b2c3');
        $model1->setCreatedAt(new DateTime('2012-02-12'));
        $model1->setProcessName('process_1');
        $model1->setStepName('step_A');
        $model1->setSuccessful(true);

        $this->em->persist($model1);

        $model2 = new ModelState();
        $model2->setWorkflowIdentifier('a1b2c3');
        $model2->setCreatedAt(new DateTime('2012-02-14'));
        $model2->setProcessName('process_1');
        $model2->setStepName('step_B');
        $model2->setSuccessful(true);

        $this->em->persist($model2);

        $model3 = new ModelState();
        $model3->setWorkflowIdentifier('a1b2c3');
        $model3->setCreatedAt(new DateTime('2012-02-20'));
        $model3->setProcessName('process_1');
        $model3->setStepName('step_C');
        $model3->setSuccessful(false);

        $this->em->persist($model3);

        $model4 = new ModelState();
        $model4->setWorkflowIdentifier('a1b2c3');
        $model4->setCreatedAt(new DateTime('2012-02-20'));
        $model4->setProcessName('process_2');
        $model4->setStepName('step_A');
        $model4->setSuccessful(false);

        $this->em->persist($model4);

        $this->em->flush();
    }

    public function testFindModelStates(): void
    {
        $this->createData();

        $repository = $this->em->getRepository('Lexik\Bundle\WorkflowBundle\Entity\ModelState');

        self::assertEmpty($repository->findModelStates('id', 'process', true));
        self::assertEmpty($repository->findModelStates('a1b2c3', 'process_?', true));
        self::assertEmpty($repository->findModelStates('a1b2c3333', 'process_1', true));

        self::assertCount(2, $repository->findModelStates('a1b2c3', 'process_1', true));
        self::assertCount(3, $repository->findModelStates('a1b2c3', 'process_1', false));
    }

    public function testDeleteModelStates(): void
    {
        $this->createData();

        $repository = $this->em->getRepository('Lexik\Bundle\WorkflowBundle\Entity\ModelState');

        self::assertEmpty($repository->deleteModelStates('id', 'process'));
        self::assertEmpty($repository->deleteModelStates('a1b2c3', 'process_?'));
        self::assertEmpty($repository->deleteModelStates('a1b2c3333', 'process_1'));

        self::assertEquals(3, $repository->deleteModelStates('a1b2c3', 'process_1'));
        self::assertEquals(1, $repository->deleteModelStates('a1b2c3'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->getSqliteEntityManager();
        $this->createSchema($this->em);
    }
}
