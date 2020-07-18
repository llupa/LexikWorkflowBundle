<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Flow;

use Lexik\Bundle\WorkflowBundle\Exception\WorkflowException;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use function call_user_func;

/**
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class NextStateOr implements NextStateInterface
{
    protected $name;
    protected $type;
    protected $targets;

    public function __construct(string $name, string $type, array $targets)
    {
        $this->name = $name;
        $this->type = $type;
        $this->targets = $targets;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @throws WorkflowException
     */
    public function getTarget(ModelInterface $model): Step
    {
        $target = null;
        $i = 0;

        while ($i < count($this->targets) && null === $target) {
            $data = $this->targets[$i];

            $isDefault = (null === $data['condition_object'] && null === $data['condition_method']);
            $callback = [$data['condition_object'], $data['condition_method']];

            if ($isDefault || true === call_user_func($callback, $model)) {
                $target = $data['target'];
            }

            $i++;
        }

        if (!$target instanceof Node) {
            throw new WorkflowException(sprintf('Next state "%s": can\'t choose target step according to given OR conditions.',
                $this->name));
        }

        return $target;
    }
}
