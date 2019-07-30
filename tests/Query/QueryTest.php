<?php
declare(strict_types=1);

namespace Remorhaz\JSON\Path\Test\Query;

use PHPUnit\Framework\TestCase;
use Remorhaz\JSON\Data\Value\NodeValueInterface;
use Remorhaz\JSON\Path\Query\Query;
use Remorhaz\JSON\Path\Query\QueryInterface;
use Remorhaz\JSON\Path\Query\CapabilitiesInterface;
use Remorhaz\JSON\Path\Runtime\EvaluatorInterface;
use Remorhaz\JSON\Path\Runtime\LiteralFactoryInterface;
use Remorhaz\JSON\Path\Runtime\Matcher\MatcherFactoryInterface;
use Remorhaz\JSON\Path\Runtime\RuntimeInterface;
use Remorhaz\JSON\Path\Runtime\ValueListFetcherInterface;
use Remorhaz\JSON\Path\Value\NodeValueListInterface;
use Remorhaz\JSON\Path\Value\ValueListInterface;
use function array_shift;

/**
 * @covers \Remorhaz\JSON\Path\Query\Query
 */
class QueryTest extends TestCase
{

    public function testInvoke_ConstructedWithCallback_CallsSameCallback(): void
    {
        $rootValue = $this->createMock(NodeValueInterface::class);
        $runtime = $this->createMock(RuntimeInterface::class);
        $valueListFetcher = $this->createMock(ValueListFetcherInterface::class);
        $evaluator = $this->createMock(EvaluatorInterface::class);
        $literalFactory = $this->createMock(LiteralFactoryInterface::class);
        $matcherFactory = $this->createMock(MatcherFactoryInterface::class);
        $runtime
            ->method('getValueListFetcher')
            ->willReturn($valueListFetcher);
        $runtime
            ->method('getEvaluator')
            ->willReturn($evaluator);
        $runtime
            ->method('getLiteralFactory')
            ->willReturn($literalFactory);
        $runtime
            ->method('getMatcherFactory')
            ->willReturn($matcherFactory);

        $isCallbackCalledWithMatchingArgs = null;
        $callback = function () use (
            $rootValue,
            $valueListFetcher,
            $evaluator,
            $literalFactory,
            $matcherFactory,
            &$isCallbackCalledWithMatchingArgs
        ): ValueListInterface {
            $args = func_get_args();
            /** @var NodeValueListInterface $input */
            $input = array_shift($args);
            $isCallbackCalledWithMatchingArgs =
                $input->getValues() === [$rootValue] &&
                [$valueListFetcher, $evaluator, $literalFactory, $matcherFactory] === $args;

            return $this->createMock(ValueListInterface::class);
        };

        $query = new Query(
            'a',
            $callback,
            $this->createMock(CapabilitiesInterface::class)
        );

        $query($rootValue, $runtime);
        self::assertTrue($isCallbackCalledWithMatchingArgs);
    }

    public function testInvoke_CallbackReturnsValueList_ReturnsSameInstance(): void
    {
        $values = $this->createMock(ValueListInterface::class);
        $callback = function () use ($values): ValueListInterface {
            return $values;
        };
        $query = new Query(
            'a',
            $callback,
            $this->createMock(CapabilitiesInterface::class)
        );

        $actualValue = $query(
            $this->createMock(NodeValueInterface::class),
            $this->createMock(RuntimeInterface::class),
        );
        self::assertSame($values, $actualValue);
    }

    public function testGetProperties_ConstructedWithGivenProperties_ReturnsSameInstance(): void
    {
        $properties = $this->createMock(CapabilitiesInterface::class);
        $callback = $this->createMock(QueryInterface::class);
        $query = new Query('a', $callback, $properties);

        self::assertSame($properties, $query->getCapabilities());
    }

    public function providerIsDefinite(): array
    {
        return [
            'TRUE' => [true, true],
            'FALSE' => [false, false],
        ];
    }

    public function testGetSource_ConstructedWithGivenSource_ReturnsSameValue(): void
    {
        $query = new Query(
            'a',
            $this->createMock(QueryInterface::class),
            $this->createMock(CapabilitiesInterface::class)
        );
        self::assertSame('a', $query->getSource());
    }
}
