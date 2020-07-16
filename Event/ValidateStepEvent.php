<?php

namespace Lexik\Bundle\WorkflowBundle\Event;

use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
use Lexik\Bundle\WorkflowBundle\Validation\Violation;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 * @author Gilles Gauthier <g.gauthier@lexik.fr>
 */
class ValidateStepEvent extends Event
{
    private $step;
    private $model;
    private $violationList;

    public function __construct(Step $step, ModelInterface $model, ViolationList $violationList)
    {
        $this->step          = $step;
        $this->model         = $model;
        $this->violationList = $violationList;
    }

    public function getStep(): Step
    {
        return $this->step;
    }

    public function getModel(): ModelInterface
    {
        return $this->model;
    }

    public function getViolationList(): ViolationList
    {
        return $this->violationList;
    }

    public function addViolation(string $message)
    {
        $this->violationList->add(new Violation($message));
    }
}
