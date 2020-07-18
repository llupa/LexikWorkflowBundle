<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface NextStateInterface
{
    const TYPE_STEP = 'step';
    const TYPE_STEP_OR = 'step_or';
    const TYPE_PROCESS = 'process';

    public function getName(): string;

    public function getType(): string;

    public function getTarget(ModelInterface $model): Step;
}
