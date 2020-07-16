<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Validation;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 * @author Gilles Gauthier <g.gauthier@lexik.fr>
 */
class Violation
{
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
