<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;

class ModelStorage
{
    protected ManagerRegistry $registry;
    protected string          $entityClass;

    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        $this->registry    = $registry;
        $this->entityClass = $entityClass;
    }

    public function findCurrentModelState(
        ModelInterface $model,
        string $processName,
        string $stepName = null
    ): ?ModelState {
        return $this->getRepository()->findLatestModelState(
            $model->getWorkflowIdentifier(),
            $processName,
            $stepName
        );
    }

    public function findAllModelStates(ModelInterface $model, string $processName, bool $successOnly = true): array
    {
        return $this->getRepository()->findModelStates(
            $model->getWorkflowIdentifier(),
            $processName,
            $successOnly
        );
    }

    public function newModelStateError(
        ModelInterface $model,
        string $processName,
        string $stepName,
        ViolationList $violationList,
        ?ModelState $previous = null
    ): ModelState {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setSuccessful(false);
        $modelState->setErrors($violationList->toArray());

        $this->getManager()->persist($modelState);
        $this->getManager()->flush();

        return $modelState;
    }

    protected function createModelState(
        ModelInterface $model,
        string $processName,
        string $stepName,
        ?ModelState $previous = null
    ): ModelState {
        $modelState = new ModelState();
        $modelState->setWorkflowIdentifier($model->getWorkflowIdentifier());
        $modelState->setProcessName($processName);
        $modelState->setStepName($stepName);
        $modelState->setData($model->getWorkflowData());

        if ($previous instanceof ModelState) {
            $modelState->setPrevious($previous);
        }

        return $modelState;
    }

    public function deleteAllModelStates(ModelInterface $model, string $processName = null): int
    {
        return $this->getRepository()->deleteModelStates(
            $model->getWorkflowIdentifier(),
            $processName
        );
    }

    public function newModelStateSuccess(
        ModelInterface $model,
        string $processName,
        string $stepName,
        ?ModelState $previous = null
    ): ModelState {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setSuccessful(true);

        $this->getManager()->persist($modelState);
        $this->getManager()->flush();

        return $modelState;
    }

    /**
     * @param ModelState|array $objects
     */
    public function setStates($objects, array $processes = [], bool $onlySuccess = false)
    {
        $this->getRepository()->setStates($objects, $processes, $onlySuccess);
    }

    protected function getManager(): ObjectManager
    {
        return $this->registry->getManager();
    }

    protected function getRepository(): ObjectRepository
    {
        return $this->getManager()->getRepository($this->entityClass);
    }
}
