<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Model;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;

interface ModelStateInterface
{
    public function addState(ModelState $modelState): void;

    public function getStates(): array;
}
