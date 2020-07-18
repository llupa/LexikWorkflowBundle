<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Event;

use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Validation\Violation;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
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
        $this->step = $step;
        $this->model = $model;
        $this->violationList = $violationList;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getStep(): Step
    {
        return $this->step;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getModel(): ModelInterface
    {
        return $this->model;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getViolationList(): ViolationList
    {
        return $this->violationList;
    }

    public function addViolation(string $message)
    {
        $this->violationList->add(new Violation($message));
    }
}
