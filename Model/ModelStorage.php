<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;

class ModelStorage
{
    protected $om;

    /**
     * @var EntityRepository
     */
    protected $repository;

    public function __construct(EntityManager $om, string $entityClass)
    {
        $this->om = $om;
        $this->repository = $this->om->getRepository($entityClass);
    }

    public function findCurrentModelState(ModelInterface $model, string $processName, string $stepName = null): ?ModelState
    {
        return $this->repository->findLatestModelState(
            $model->getWorkflowIdentifier(),
            $processName,
            $stepName
        );
    }

    public function findAllModelStates(ModelInterface $model, string $processName, bool $successOnly = true): array
    {
        return $this->repository->findModelStates(
            $model->getWorkflowIdentifier(),
            $processName,
            $successOnly
        );
    }

    public function newModelStateError(ModelInterface $model, string $processName, string $stepName, ViolationList $violationList, ?ModelState $previous = null): ModelState
    {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setSuccessful(false);
        $modelState->setErrors($violationList->toArray());

        //todo: drop single entity flush
        $this->om->persist($modelState);
        $this->om->flush($modelState);

        return $modelState;
    }

    public function deleteAllModelStates(ModelInterface $model, string $processName = null): int
    {
        return $this->repository->deleteModelStates(
            $model->getWorkflowIdentifier(),
            $processName
        );
    }

    public function newModelStateSuccess(ModelInterface $model, string $processName, string $stepName, ?ModelState $previous = null): ModelState
    {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setSuccessful(true);

        //todo: drop single entity flush
        $this->om->persist($modelState);
        $this->om->flush($modelState);

        return $modelState;
    }

    /**
     * @param ModelState|array $objects
     */
    public function setStates($objects, array $processes = [], bool $onlySuccess = false)
    {
        $this->repository->setStates($objects, $processes, $onlySuccess);
    }

    protected function createModelState(ModelInterface $model, string $processName, string $stepName, ?ModelState $previous = null): ModelState
    {
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
}
