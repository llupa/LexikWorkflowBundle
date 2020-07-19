<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;

/**
 * A State represent one of the available next element (step) a given step.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class NextState implements NextStateInterface
{
    protected $name;
    protected $type;
    protected $target;

    public function __construct(string $name, string $type, Step $target)
    {
        $this->name = $name;
        $this->type = $type;
        $this->target = $target;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(ModelInterface $model = null): Step
    {
        return $this->target;
    }
}
