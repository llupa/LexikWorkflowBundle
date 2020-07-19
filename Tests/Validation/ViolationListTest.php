<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests\Validation;

use InvalidArgumentException;
use Lexik\Bundle\WorkflowBundle\Tests\TestCase;
use Lexik\Bundle\WorkflowBundle\Validation\Violation;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
use OutOfBoundsException;

final class ViolationListTest extends TestCase
{
    public function testAdd(): void
    {
        $violationList = new ViolationList();
        $violationList->add(new Violation('Violation test'));
        self::assertCount(1, $violationList);
    }

    public function testToString(): void
    {
        $violationList = new ViolationList();
        $violationList->add(new Violation('Violation test n°1'));
        $violationList->add(new Violation('Violation test n°2'));

        $expectedResult = <<<EOF
Violation test n°1
Violation test n°2

EOF;

        self::assertEquals($expectedResult, (string)$violationList);
    }

    public function testArrayAccess(): void
    {
        $violationList = new ViolationList();
        $violation = new Violation('Violation test');

        $violationList->add($violation);
        self::assertCount(1, $violationList);
        self::assertSame($violation, $violationList[0]);

        $violationList[1] = $violation;
        self::assertSame($violation, $violationList[1]);
        self::assertCount(2, $violationList);

        unset($violationList[1]);
        self::assertFalse(isset($violationList[1]));

        try {
            $test = $violationList[1];
            $this->fail('An expected OutOfBoundsException has not been raised.');
        } catch (OutOfBoundsException $e) {
        }

        try {
            $violationList[1] = 'Wrong argument';
            $this->fail('An expected InvalidArgumentException has not been raised.');
        } catch (InvalidArgumentException $e) {
        }

        foreach ($violationList as $key => $violation) {
            self::assertSame($violation, $violationList[$key]);
        }
    }
}
