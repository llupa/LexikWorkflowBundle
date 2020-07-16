<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Lexik\Bundle\WorkflowBundle\Validation\Violation;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
use Lexik\Bundle\WorkflowBundle\Event\ValidateStepEvent;
use Lexik\Bundle\WorkflowBundle\Event\StepEvent;
use Lexik\Bundle\WorkflowBundle\Exception\WorkflowException;
use Lexik\Bundle\WorkflowBundle\Exception\AccessDeniedException;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Flow\Process;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Model\ModelStorage;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use function in_array;
use function sprintf;

class ProcessHandler implements ProcessHandlerInterface
{
    protected $process;
    protected $storage;
    protected $authorizationChecker;
    protected $dispatcher;

    public function __construct(Process $process, ModelStorage $storage, EventDispatcherInterface $dispatcher)
    {
        $this->process    = $process;
        $this->storage    = $storage;
        $this->dispatcher = $dispatcher;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @throws WorkflowException
     */
    public function start(ModelInterface $model): ModelState
    {
        $modelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ($modelState instanceof ModelState) {
            throw new WorkflowException(sprintf('The given model has already started the "%s" process.', $this->process->getName()));
        }

        $step = $this->getProcessStep($this->process->getStartStep());

        return $this->reachStep($model, $step);
    }

    /**
     * @throws WorkflowException
     */
    public function reachNextState(ModelInterface $model, string $stateName): ModelState
    {
        $currentModelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ( ! ($currentModelState instanceof ModelState) ) {
            throw new WorkflowException(sprintf('The given model has not started the "%s" process.', $this->process->getName()));
        }

        $currentStep = $this->getProcessStep($currentModelState->getStepName());

        if ( !$currentStep->hasNextState($stateName) ) {
            throw new WorkflowException(sprintf('The step "%s" does not contain any next state named "%s".', $currentStep->getName(), $stateName));
        }

        $state = $currentStep->getNextState($stateName);
        $step = $state->getTarget($model);

        // pre validations
        $event = new ValidateStepEvent($step, $model, new ViolationList());
        $eventName = sprintf('%s.%s.%s.pre_validation', $this->process->getName(), $currentStep->getName(), $stateName);
        // todo: swap places in Sf 4
        $this->dispatcher->dispatch($eventName, $event);

        $modelState = null;

        if (count($event->getViolationList()) > 0) {
            $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $event->getViolationList(), $currentModelState);

            $eventName = sprintf('%s.%s.%s.pre_validation_fail', $this->process->getName(), $currentStep->getName(), $stateName);
            // todo: swap places in Sf 4
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));
        } else {
            $modelState = $this->reachStep($model, $step, $currentModelState);
        }

        return $modelState;
    }

    protected function reachStep(ModelInterface $model, Step $step, ?ModelState $currentModelState = null): ModelState
    {
        try {
            $this->checkCredentials($model, $step);
        } catch (AccessDeniedException $e) {
            $violations = new ViolationList();
            $violations->add(new Violation($e->getMessage()));

            $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $violations, $currentModelState);

            // todo: swap places in Sf 4
            $eventName = sprintf('%s.%s.bad_credentials', $this->process->getName(), $step->getName());
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));

            if ($step->getOnInvalid()) {
                $step = $this->getProcessStep($step->getOnInvalid());
                $modelState = $this->reachStep($model, $step);
            }

            return $modelState;
        }

        $event = new ValidateStepEvent($step, $model, new ViolationList());
        $eventName = sprintf('%s.%s.validate', $this->process->getName(), $step->getName());
        // todo: swap places in Sf 4
        $this->dispatcher->dispatch($eventName, $event);

        if (0 === count($event->getViolationList())) {
            $modelState = $this->storage->newModelStateSuccess($model, $this->process->getName(), $step->getName(), $currentModelState);

            // update model status
            if ($step->hasModelStatus()) {
                [$method, $constant] = $step->getModelStatus();
                $model->$method(constant($constant));
            }

            $eventName = sprintf('%s.%s.reached', $this->process->getName(), $step->getName());
            // todo: swap places in Sf 4
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));
        } else {
            $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $event->getViolationList(), $currentModelState);

            $eventName = sprintf('%s.%s.validation_fail', $this->process->getName(), $step->getName());
            // todo: swap places in Sf 4
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));

            if ($step->getOnInvalid()) {
                $step = $this->getProcessStep($step->getOnInvalid());
                $modelState = $this->reachStep($model, $step);
            }
        }

        return $modelState;
    }

    public function getCurrentState(ModelInterface $model): ?ModelState
    {
        return $this->storage->findCurrentModelState($model, $this->process->getName());
    }

    public function isProcessComplete(ModelInterface $model): bool
    {
        $state = $this->getCurrentState($model);

        return ( $state !== null && $state->getSuccessful() && in_array($state->getStepName(), $this->process->getEndSteps()) );
    }

    public function getAllStates(ModelInterface $model, bool $successOnly = true): array
    {
        return $this->storage->findAllModelStates($model, $this->process->getName(), $successOnly);
    }

    /**
     * @throws WorkflowException
     */
    protected function getProcessStep(string $stepName): ?Step
    {
        $step = $this->process->getStep($stepName);

        if (! ($step instanceof Step)) {
            throw new WorkflowException(sprintf('Can\'t find step named "%s" in process "%s".', $stepName, $this->process->getName()));
        }

        return $step;
    }

    /**
     * @throws AccessDeniedException
     */
    protected function checkCredentials(ModelInterface $model, Step $step): void
    {
        $roles = $step->getRoles();

        if (!empty($roles) && !$this->authorizationChecker->isGranted($roles, $model->getWorkflowObject())) {
            throw new AccessDeniedException($step->getName());
        }
    }
}
