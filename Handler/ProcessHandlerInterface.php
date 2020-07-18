<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
interface ProcessHandlerInterface
{
    public function start(ModelInterface $model): ModelState;

    public function reachNextState(ModelInterface $model, string $stateName): ModelState;

    public function getCurrentState(ModelInterface $model): ?ModelState;

    public function getAllStates(ModelInterface $model, bool $successOnly = true): array;

    public function isProcessComplete(ModelInterface $model): bool;
}
