<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Validation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use function count;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 * @author Gilles Gauthier <g.gauthier@lexik.fr>
 */
class ViolationList implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var Violation[]
     */
    private $violations = [];

    public function __toString(): string
    {
        $output = '';
        foreach ($this->violations as $violation) {
            $output .= $violation->getMessage()."\n";
        }

        return $output;
    }

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    public function offsetGet($offset): Violation
    {
        if (!isset($this->violations[$offset])) {
            throw new OutOfBoundsException(sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->violations[$offset];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->violations[$offset]);
    }

    public function offsetSet($offset, $violation): void
    {
        if (!$violation instanceof Violation) {
            throw new InvalidArgumentException('You must pass a valid Violation object');
        }

        $this->violations[$offset] = $violation;
    }

    public function offsetUnset($offset): void
    {
        unset($this->violations[$offset]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->violations);
    }

    public function count(): int
    {
        return count($this->violations);
    }

    public function toArray(): array
    {
        $data = [];
        foreach ($this->violations as $violation) {
            $data[] = $violation->getMessage();
        }

        return $data;
    }
}
