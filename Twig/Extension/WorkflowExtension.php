<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Twig\Extension;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessAggregator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function implode;

class WorkflowExtension extends AbstractExtension
{
    private $aggregator;

    public function __construct(ProcessAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_step_label', [$this, 'getStepLabel']),
            new TwigFunction('get_state_message', [$this, 'getStateMessage']),
            new TwigFunction('get_state_messsage', [$this, 'getStateMessage']) // todo: drop for Sf4
        ];
    }

    //todo: drop for Sf4
    public function getName(): string
    {
        return 'workflow_extension';
    }

    public function getStepLabel(ModelState $state): string
    {
        $step = $this->aggregator
            ->getProcess($state->getProcessName())
            ->getStep($state->getStepName());

        return $step instanceof Step ? $step->getLabel() : '';
    }

    public function getStateMessage(ModelState $state): string
    {
        $message = '';

        if ($state->getSuccessful()) {
            $data = $state->getData();

            $message = isset($data['success_message']) ? $data['success_message'] : '';
        } else {
            $message = implode("\n", $state->getErrors());
        }

        return $message;
    }
}
