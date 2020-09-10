<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;

final class ProcessHandlerPool
{
    private $processHandlers;

    public function __construct()
    {
        $this->processHandlers = new ArrayCollection();
    }

    public function addProcessHandler(string $key, ProcessHandlerInterface $handler): void
    {
        if (!$this->processHandlers->containsKey($key)) {
            $this->processHandlers->set($key, $handler);
        }
    }

    /**
     * @param string $key Can be Process name or ProcessHandler service ID
     */
    public function getProcessHandler(string $key): ?ProcessHandlerInterface
    {
        return $this->processHandlers->get($key);
    }
}
