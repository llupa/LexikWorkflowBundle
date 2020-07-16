<?php

namespace Lexik\Bundle\WorkflowBundle\Model;

interface ModelInterface
{
    public function getWorkflowIdentifier(): string;

    public function getWorkflowData(): array;

    /**
     * Returns the object of the workflow.
     *
     * @return mixed
     */
    public function getWorkflowObject();
}
