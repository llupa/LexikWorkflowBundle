<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Event;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class StepEvent extends Event
{
    private $step;
    private $model;
    private $modelState;

    public function __construct(Step $step, ModelInterface $model, ModelState $modelState)
    {
        $this->step = $step;
        $this->model = $model;
        $this->modelState = $modelState;
    }

    public function getStep(): Step
    {
        return $this->step;
    }

    public function getModel(): ModelInterface
    {
        return $this->model;
    }

    public function getModelState(): ModelState
    {
        return $this->modelState;
    }
}
