<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use function json_decode;
use function json_encode;

class ModelState
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $workflowIdentifier;

    /**
     * @var string
     */
    protected $processName;

    /**
     * @var string
     */
    protected $stepName;

    /**
     * @var boolean
     */
    protected $successful;

    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $errors;

    /**
     * @var ModelState
     */
    protected $previous;

    /**
     * @var ArrayCollection
     */
    protected $next;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->createdAt = new DateTime('now');
        $this->next = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWorkflowIdentifier(): string
    {
        return $this->workflowIdentifier;
    }

    public function setWorkflowIdentifier(string $workflowIdentifier): void
    {
        $this->workflowIdentifier = $workflowIdentifier;
    }

    public function getProcessName(): string
    {
        return $this->processName;
    }

    public function setProcessName(string $processName): void
    {
        $this->processName = $processName;
    }

    public function getStepName(): string
    {
        return $this->stepName;
    }

    public function setStepName(string $stepName): void
    {
        $this->stepName = $stepName;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getData(): array
    {
        return json_decode($this->data, true);
    }

    public function setData(array $data): void
    {
        $this->data = json_encode($data);
    }

    public function getSuccessful(): bool
    {
        return $this->successful;
    }

    public function setSuccessful(bool $successful): void
    {
        $this->successful = $successful;
    }

    public function getErrors(): array
    {
        return json_decode($this->errors, true);
    }

    public function setErrors(array $errors): void
    {
        $this->errors = json_encode($errors);
    }

    public function hasPrevious(): bool
    {
        return $this->previous instanceof ModelState;
    }

    public function getPrevious(): ?ModelState
    {
        return $this->previous;
    }

    public function setPrevious(ModelState $state): void
    {
        $this->previous = $state;
    }

    public function getNext(): Collection
    {
        return $this->next;
    }

    public function addNext(ModelState $state): void
    {
        $state->setPrevious($this);
        $this->next->add($state);
    }
}
