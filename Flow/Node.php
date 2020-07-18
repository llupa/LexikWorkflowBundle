<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

use function array_keys;
use function in_array;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class Node
{
    protected $name;
    protected $nextStates;

    public function __construct(string $name, array $nextStates = [])
    {
        $this->name = $name;
        $this->nextStates = $nextStates;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNextStates(): array
    {
        return $this->nextStates;
    }

    public function getNextState(string $name): ?NextStateInterface
    {
        if (!$this->hasNextState($name)) {
            return null;
        }

        return $this->nextStates[$name];
    }

    public function hasNextState(string $name): bool
    {
        return in_array($name, array_keys($this->nextStates));
    }

    public function addNextState(string $name, string $type, Node $target): void
    {
        $this->nextStates[$name] = new NextState($name, $type, $target);
    }

    public function addNextStateOr(string $name, string $type, array $targets): void
    {
        $this->nextStates[$name] = new NextStateOr($name, $type, $targets);
    }
}
