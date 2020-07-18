<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Process extends Node
{
    protected $steps;
    protected $startStep;
    protected $endSteps;

    public function __construct(string $name, array $steps, string $startStep, array $endSteps)
    {
        parent::__construct($name);

        $this->steps = new ArrayCollection($steps);
        $this->startStep = $startStep;
        $this->endSteps = $endSteps;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function getStep(string $name): ?Step
    {
        return $this->steps->get($name);
    }

    public function getStartStep(): string
    {
        return $this->startStep;
    }

    public function getEndSteps(): array
    {
        return $this->endSteps;
    }
}
