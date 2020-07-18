<?php

namespace Lexik\Bundle\WorkflowBundle\Tests\Fixtures;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Model\ModelStateInterface;

class FakeModel implements ModelInterface, ModelStateInterface
{
    const STATUS_CREATE = 1;
    const STATUS_VALIDATE = 2;
    const STATUS_REMOVE = 3;
    public $data = [];
    public $states = [];
    protected $status;
    protected $content;
    protected $object;

    public function __construct()
    {
        $this->object = new \stdClass();
    }

    public function getWorkflowIdentifier(): string
    {
        return 'sample_identifier';
    }

    public function getWorkflowData(): array
    {
        return $this->data;
    }

    public function addState(ModelState $modelState): void
    {
        $this->states[] = $modelState;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function getWorkflowObject()
    {
        return $this->object;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }
}
