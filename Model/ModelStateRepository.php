<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Model;

use ArrayAccess;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;

class ModelStateRepository extends EntityRepository
{
    public function findLatestModelState(
        string $workflowIdentifier,
        string $processName,
        string $stepName = null
    ): ?ModelState {
        $qb = $this->createQueryBuilder('ms');
        $qb
            ->andWhere('ms.workflowIdentifier = :workflow_identifier')
            ->andWhere('ms.processName = :process')
            ->andWhere('ms.successful = :success')
            ->orderBy('ms.id', 'DESC')
            ->setParameter('workflow_identifier', $workflowIdentifier)
            ->setParameter('process', $processName)
            ->setParameter('success', true);

        if (null !== $stepName) {
            $qb
                ->andWhere('ms.stepName = :stepName')
                ->setParameter('stepName', $stepName);
        }

        $results = $qb->getQuery()->getResult();

        return isset($results[0]) ? $results[0] : null;
    }

    public function findModelStates(string $workflowIdentifier, string $processName, bool $successOnly): array
    {
        $qb = $this->createQueryBuilder('ms')
            ->andWhere('ms.workflowIdentifier = :workflow_identifier')
            ->andWhere('ms.processName = :process')
            ->orderBy('ms.createdAt', 'ASC')
            ->setParameter('workflow_identifier', $workflowIdentifier)
            ->setParameter('process', $processName);

        if ($successOnly) {
            $qb->andWhere('ms.successful = :success')
                ->setParameter('success', true);
        }

        return $qb->getQuery()->getResult();
    }

    public function deleteModelStates(string $workflowIdentifier, string $processName = null): int
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete($this->_entityName, 'ms')
            ->andWhere('ms.workflowIdentifier = :workflow_identifier')
            ->setParameter('workflow_identifier', $workflowIdentifier);

        if (null !== $processName) {
            $qb->andWhere('ms.processName = :process')
                ->setParameter('process', $processName);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ModelState|array $objects
     *
     * @throws InvalidArgumentException
     */
    public function setStates($objects, array $processes, bool $onlySuccess)
    {
        $objects = (!is_array($objects) && !$objects instanceof ArrayAccess) ? [$objects] : $objects;

        if (0 === count($objects)) {
            return;
        }

        $ordersIndexedByWorkflowIdentifier = [];
        foreach ($objects as $object) {
            if (!$object instanceof ModelStateInterface) {
                throw new InvalidArgumentException();
            }

            $ordersIndexedByWorkflowIdentifier[$object->getWorkflowIdentifier()] = $object;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('ms')
            ->from('Lexik\Bundle\WorkflowBundle\Entity\ModelState', 'ms')
            ->andWhere($qb->expr()->in('ms.workflowIdentifier', array_keys($ordersIndexedByWorkflowIdentifier)))
            ->orderBy('ms.id');

        if (count($processes)) {
            $qb->andWhere($qb->expr()->in('ms.processName', $processes));
        }

        if ($onlySuccess) {
            $qb->andWhere('ms.successful = 1');
        }

        $states = $qb->getQuery()->getResult();

        foreach ($states as $state) {
            $ordersIndexedByWorkflowIdentifier[$state->getWorkflowIdentifier()]->addState($state);
        }
    }
}
