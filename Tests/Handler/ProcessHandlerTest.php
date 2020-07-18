<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests\Handler;

use DateTime;
use Doctrine\ORM\EntityManager;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Exception\WorkflowException;
use Lexik\Bundle\WorkflowBundle\Flow\NextStateInterface;
use Lexik\Bundle\WorkflowBundle\Flow\Process;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandlerInterface;
use Lexik\Bundle\WorkflowBundle\Model\ModelStorage;
use Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeAuthorizationChecker;
use Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModel;
use Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModelChecker;
use Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeProcessListener;
use Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeValidatorListener;
use Lexik\Bundle\WorkflowBundle\Tests\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class ProcessHandlerTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ModelStorageƒ
     */
    protected $modelStorage;

    /**
     * @var FakeAuthorizationChecker
     */
    protected $authorizationChecker;

    public function testStart(): void
    {
        $model = new FakeModel();
        $modelState = $this->getProcessHandler()->start($model);

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals($model->getWorkflowIdentifier(), $modelState->getWorkflowIdentifier());
        self::assertEquals('document_process', $modelState->getProcessName());
        self::assertEquals('step_create_doc', $modelState->getStepName());
        self::assertTrue($modelState->getCreatedAt() instanceof DateTime);
        self::assertIsArray($modelState->getData());
        self::assertCount(0, $modelState->getData());
        self::assertEquals(FakeModel::STATUS_CREATE, $model->getStatus());
        self::assertEquals(["ROLE_ADMIN"], $this->authorizationChecker->testedAttributes);
        self::assertSame($model->getWorkflowObject(), $this->authorizationChecker->testedObject);
    }

    protected function getProcessHandler($authenticatedUser = true): ProcessHandlerInterface
    {
        $stepValidateDoc = new Step(
            'step_validate_doc',
            'Validate doc',
            [],
            ['setStatus', 'Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModel::STATUS_VALIDATE']
        );

        $stepRemoveDoc = new Step(
            'step_remove_doc',
            'Remove doc',
            [],
            ['setStatus', 'Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModel::STATUS_REMOVE']
        );

        $stepRemoveOnInvalidDoc = new Step(
            'step_remove_on_invalid_doc',
            'Remove doc',
            [],
            ['setStatus', 'Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModel::STATUS_REMOVE'],
            [],
            'step_fake'
        );

        $stepFake = new Step('step_fake', 'Fake', []);

        $stepCreateDoc = new Step(
            'step_create_doc',
            'Create doc',
            [],
            ['setStatus', 'Lexik\Bundle\WorkflowBundle\Tests\Fixtures\FakeModel::STATUS_CREATE'],
            ['ROLE_ADMIN']
        );
        $stepCreateDoc->addNextState('validate', NextStateInterface::TYPE_STEP, $stepValidateDoc);
        $stepCreateDoc->addNextState('validate_with_pre_validation', NextStateInterface::TYPE_STEP, $stepValidateDoc);
        $stepCreateDoc->addNextState('validate_with_pre_validation_invalid', NextStateInterface::TYPE_STEP,
            $stepValidateDoc);
        $stepCreateDoc->addNextState('remove', NextStateInterface::TYPE_STEP, $stepRemoveDoc);
        $stepCreateDoc->addNextState('remove_on_invalid', NextStateInterface::TYPE_STEP, $stepRemoveOnInvalidDoc);
        $stepCreateDoc->addNextStateOr('validate_or_remove', NextStateInterface::TYPE_STEP_OR, [
            [
                'target' => $stepValidateDoc,
                'condition_object' => new FakeModelChecker(),
                'condition_method' => 'isClean',
            ],
            [
                'target' => $stepRemoveDoc,
                'condition_object' => null,
                'condition_method' => null,
            ],
        ]);

        $process = new Process(
            'document_process',
            [
                'step_create_doc' => $stepCreateDoc,
                'step_validate_doc' => $stepValidateDoc,
                'step_remove_doc' => $stepRemoveDoc,
                'step_remove_on_invalid_doc' => $stepRemoveOnInvalidDoc,
                'step_fake' => $stepFake,
            ],
            'step_create_doc',
            ['step_validate_doc']
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('document_process.step_fake.reached', [
            new FakeProcessListener(),
            'handleSucccess',
        ]);
        $dispatcher->addListener('document_process.step_remove_doc.validate', [
            new FakeValidatorListener(),
            'invalid',
        ]);
        $dispatcher->addListener('document_process.step_remove_on_invalid_doc.validate', [
            new FakeValidatorListener(),
            'invalid',
        ]);
        $dispatcher->addListener('document_process.step_validate_doc.validate', [
            new FakeValidatorListener(),
            'valid',
        ]);
        $dispatcher->addListener('document_process.step_create_doc.validate_with_pre_validation.pre_validation', [
            new FakeValidatorListener(),
            'valid',
        ]);
        $dispatcher->addListener('document_process.step_create_doc.validate_with_pre_validation_invalid.pre_validation',
            [
                new FakeValidatorListener(),
                'invalid',
            ]);
        $dispatcher->addListener('document_process.step_create_doc.validate_with_pre_validation_invalid.pre_validation_fail',
            [
                new FakeProcessListener(),
                'handleSucccess',
            ]);

        $processHandler = new ProcessHandler($process, $this->modelStorage, $dispatcher);

        $this->authorizationChecker = new FakeAuthorizationChecker($authenticatedUser);
        $processHandler->setAuthorizationChecker($this->authorizationChecker);

        return $processHandler;
    }

    public function testStartBadCredentials(): void
    {
        $model = new FakeModel();
        $modelState = $this->getProcessHandler(false)->start($model);

        self::assertTrue($modelState instanceof ModelState);
        self::assertFalse($modelState->getSuccessful());
        self::assertEquals(["ROLE_ADMIN"], $this->authorizationChecker->testedAttributes);
        self::assertSame($model->getWorkflowObject(), $this->authorizationChecker->testedObject);
    }

    public function testStartWithData(): void
    {
        $data = ['some', 'information'];

        $model = new FakeModel();
        $model->data = $data;
        $modelState = $this->getProcessHandler()->start($model);

        self::assertEquals($data, $modelState->getData());
        self::assertEquals(["ROLE_ADMIN"], $this->authorizationChecker->testedAttributes);
        self::assertSame($model->getWorkflowObject(), $this->authorizationChecker->testedObject);
    }

    public function testStartAlreadyStarted(): void
    {
        self::expectException(WorkflowException::class);
        self::expectExceptionMessage("The given model has already started the \"document_process\" process.");

        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');

        $this->getProcessHandler()->start($model);
    }

    public function testReachNextStateNotStarted(): void
    {
        self::expectException(WorkflowException::class);
        self::expectExceptionMessage("The given model has not started the \"document_process\" process.");

        $model = new FakeModel();

        $this->getProcessHandler()->reachNextState($model, 'validate');
    }

    public function testReachNextState(): void
    {
        $model = new FakeModel();
        $previous = $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');

        $modelState = $this->getProcessHandler()->reachNextState($model, 'validate');

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals('step_validate_doc', $modelState->getStepName());
        self::assertTrue($modelState->getSuccessful());
        self::assertTrue($modelState->getPrevious() instanceof ModelState);
        self::assertEquals($previous->getId(), $modelState->getPrevious()->getId());
        self::assertEquals(FakeModel::STATUS_VALIDATE, $model->getStatus());
        self::assertNull($this->authorizationChecker->testedAttributes);
        self::assertNull($this->authorizationChecker->testedObject);
    }

    public function testReachNextStateInvalidNextStep(): void
    {
        self::expectException(WorkflowException::class);
        self::expectExceptionMessage("The step \"step_create_doc\" does not contain any next state named \"step_fake\".");

        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');

        $modelState = $this->getProcessHandler()->reachNextState($model, 'step_fake');
    }

    public function testReachNextStateWithListener(): void
    {
        self::assertEquals(0, FakeProcessListener::$call);

        $reflectionClass = new ReflectionClass('Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler');
        $method = $reflectionClass->getMethod('reachStep');
        $method->setAccessible(true);
        $method->invoke($this->getProcessHandler(), new FakeModel(), new Step('step_fake', 'Fake'));

        self::assertEquals(1, FakeProcessListener::$call);
    }

    public function testReachNextStateOnInvalid(): void
    {
        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');

        $modelState = $this->getProcessHandler()->reachNextState($model, 'remove_on_invalid');

        self::assertEquals('step_fake', $modelState->getStepName());
    }

    public function testReachNextStateOrValidate(): void
    {
        // content is clean so we should go to validate
        $model = new FakeModel();
        $modelState = $this->getProcessHandler()->start($model);

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals('document_process', $modelState->getProcessName());
        self::assertEquals('step_create_doc', $modelState->getStepName());

        $model->setContent('blablabla');
        $modelState = $this->getProcessHandler()->reachNextState($model, 'validate_or_remove');

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals('document_process', $modelState->getProcessName());
        self::assertEquals('step_validate_doc', $modelState->getStepName());
    }

    public function testReachNextStateOrRemove(): void
    {
        // content is NOT clean so we should go to remove
        $model = new FakeModel();
        $modelState = $this->getProcessHandler()->start($model);

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals('document_process', $modelState->getProcessName());
        self::assertEquals('step_create_doc', $modelState->getStepName());

        $model->setContent('');
        $modelState = $this->getProcessHandler()->reachNextState($model, 'validate_or_remove');

        self::assertTrue($modelState instanceof ModelState);
        self::assertEquals('document_process', $modelState->getProcessName());
        self::assertEquals('step_remove_doc', $modelState->getStepName());
    }

    public function testExecuteValidations(): void
    {
        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');

        $modelState = $this->getProcessHandler()->reachNextState($model, 'remove');

        self::assertFalse($modelState->getSuccessful());
        self::assertEquals(['Validation error!'], $modelState->getErrors());
    }

    public function testGetProcessStepInvalidStepName(): void
    {
        self::expectException(WorkflowException::class);
        self::expectExceptionMessage("Can't find step named \"step_unknown\" in process \"document_process\".");

        $reflectionClass = new ReflectionClass('Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler');
        $method = $reflectionClass->getMethod('getProcessStep');
        $method->setAccessible(true);
        $method->invoke($this->getProcessHandler(), 'step_unknown');
    }

    public function testExecutePreValidations(): void
    {
        // reset fake calls
        FakeProcessListener::$call = 0;

        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');
        $modelState = $this->getProcessHandler()->reachNextState($model, 'validate_with_pre_validation');

        self::assertTrue($modelState->getSuccessful());
        self::assertEquals('step_validate_doc', $modelState->getStepName());

        self::assertEquals(0, FakeProcessListener::$call);

        $model = new FakeModel();
        $this->modelStorage->newModelStateSuccess($model, 'document_process', 'step_create_doc');
        $modelState = $this->getProcessHandler()->reachNextState($model, 'validate_with_pre_validation_invalid');

        self::assertFalse($modelState->getSuccessful());
        self::assertEquals(['Validation error!'], $modelState->getErrors());

        self::assertEquals(1, FakeProcessListener::$call);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->getSqliteEntityManager();
        $this->createSchema($this->em);

        $this->modelStorage = new ModelStorage($this->em, 'Lexik\Bundle\WorkflowBundle\Entity\ModelState');
    }
}
