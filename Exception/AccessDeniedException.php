<?php

namespace Lexik\Bundle\WorkflowBundle\Exception;

use Exception;
use Throwable;

class AccessDeniedException extends Exception
{
    public function __construct($stepName, $code = 0, Throwable $previous = null)
    {
        parent::__construct($stepName, $code, $previous);

        $this->message = sprintf('Access denied. The current user is not allowed to reach the step "%s"', $stepName);
    }
}
