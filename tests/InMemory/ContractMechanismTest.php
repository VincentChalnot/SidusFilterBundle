<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\InMemory;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Test\Contract\ContractCases;
use Sidus\FilterBundle\Test\Contract\FixtureDataset;
use Sidus\FilterBundle\Tests\Support\ConfigurableContractSuite;

/**
 * The knownDivergences mechanism: a declared divergence must really diverge, an undeclared one fails the case.
 */
final class ContractMechanismTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $data
     * @param array<string, string>      $divergences
     */
    private function suite(array $data, array $divergences): ConfigurableContractSuite
    {
        $suite = new ConfigurableContractSuite('testCase');
        $suite->data = $data;
        $suite->divergences = $divergences;

        return $suite;
    }

    public function testAMatchingCaseThatIsDeclaredDivergentFails(): void
    {
        $suite = $this->suite(FixtureDataset::posts(), ['contains_is_case_insensitive' => 'pretend']);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('now matches the reference: remove the entry');

        $suite->testCase('contains_is_case_insensitive', ContractCases::all()['contains_is_case_insensitive']);
    }

    public function testADivergingCaseThatIsNotDeclaredFails(): void
    {
        $suite = $this->suite([], []);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Case "contains_is_case_insensitive" diverges from the reference.');

        $suite->testCase('contains_is_case_insensitive', ContractCases::all()['contains_is_case_insensitive']);
    }

    public function testADivergingCaseThatIsDeclaredPasses(): void
    {
        $suite = $this->suite([], ['contains_is_case_insensitive' => 'empty storage']);

        $suite->testCase('contains_is_case_insensitive', ContractCases::all()['contains_is_case_insensitive']);

        $this->addToAssertionCount(1);
    }

    public function testDivergenceEntriesMustNameExistingCases(): void
    {
        $suite = $this->suite(FixtureDataset::posts(), ['no_such_case' => 'x']);

        $this->expectException(AssertionFailedError::class);

        $suite->testKnownDivergencesReferToExistingCases();
    }
}
