<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Step extends Node
{
    protected $label;
    protected $roles;
    protected $modelStatus;
    protected $onInvalid;

    public function __construct(
        string $name,
        string $label,
        array $nextStates = [],
        array $modelStatus = [],
        array $roles = [],
        string $onInvalid = null
    ) {
        parent::__construct($name, $nextStates);

        $this->label = $label;
        $this->modelStatus = $modelStatus;
        $this->roles = $roles;
        $this->onInvalid = $onInvalid;
    }

    public function __toString(): string
    {
        return $this->label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getModelStatus(): array
    {
        return $this->modelStatus;
    }

    public function hasModelStatus(): bool
    {
        return !empty($this->modelStatus);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getOnInvalid(): ?string
    {
        return $this->onInvalid;
    }
}
