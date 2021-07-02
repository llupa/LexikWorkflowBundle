<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Lexik\Bundle\WorkflowBundle\Exception\UnknownProcessException;
use Lexik\Bundle\WorkflowBundle\Flow\Process;

/**
 * Aggregate all processes.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ProcessAggregator
{
    /**
     * @var array
     */
    private $processes;

    public function __construct(array $processes)
    {
        $this->processes = $processes;
    }

    /**
     * Returns a process by its name.
     *
     * @param string $name
     * @return Process
     *
     * @throws UnknownProcessException
     */
    public function getProcess($name)
    {
        if (!isset($this->processes[$name])) {
            throw new UnknownProcessException(sprintf('Unknown process "%s".', $name));
        }

        return $this->processes[$name];
    }
}
