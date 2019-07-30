<?php
declare(strict_types=1);

namespace Remorhaz\JSON\Path\Processor;

use Collator;
use Remorhaz\JSON\Data\Export\Decoder;
use Remorhaz\JSON\Data\Export\Encoder;
use Remorhaz\JSON\Data\Iterator\ValueIteratorFactory;
use Remorhaz\JSON\Data\Value\NodeValueInterface;
use Remorhaz\JSON\Path\Processor\Result\ResultFactory;
use Remorhaz\JSON\Path\Processor\Result\ResultFactoryInterface;
use Remorhaz\JSON\Path\Processor\Result\SelectOnePathResultInterface;
use Remorhaz\JSON\Path\Processor\Result\SelectOneResultInterface;
use Remorhaz\JSON\Path\Processor\Result\SelectPathsResultInterface;
use Remorhaz\JSON\Path\Processor\Result\SelectResultInterface;
use Remorhaz\JSON\Path\Query\QueryInterface;
use Remorhaz\JSON\Path\Query\QueryValidator;
use Remorhaz\JSON\Path\Query\QueryValidatorInterface;
use Remorhaz\JSON\Path\Runtime\Aggregator\AggregatorCollection;
use Remorhaz\JSON\Path\Runtime\Comparator\ComparatorCollection;
use Remorhaz\JSON\Path\Runtime\Evaluator;
use Remorhaz\JSON\Path\Runtime\LiteralFactory;
use Remorhaz\JSON\Path\Runtime\Matcher\MatcherFactory;
use Remorhaz\JSON\Path\Runtime\ValueListFetcher;
use Remorhaz\JSON\Path\Runtime\Runtime;
use Remorhaz\JSON\Path\Runtime\RuntimeInterface;
use Remorhaz\JSON\Path\Runtime\ValueFetcher;

final class Processor implements ProcessorInterface
{

    private $runtime;

    private $resultFactory;

    private $queryValidator;

    public static function create(): ProcessorInterface
    {
        $valueIteratorFactory = new ValueIteratorFactory;
        $evaluator = new Evaluator(
            new ComparatorCollection($valueIteratorFactory, new Collator('UTF-8')),
            new AggregatorCollection($valueIteratorFactory),
        );
        $valueFetcher = new ValueFetcher($valueIteratorFactory);
        $runtime = new Runtime(
            new ValueListFetcher($valueFetcher),
            $evaluator,
            new LiteralFactory,
            new MatcherFactory($valueFetcher),
        );
        $jsonDecoder = new Decoder($valueIteratorFactory);
        $jsonEncoder = new Encoder($jsonDecoder);

        return new self(
            $runtime,
            new ResultFactory($jsonEncoder, $jsonDecoder, new PathEncoder),
            new QueryValidator,
        );
    }

    public function __construct(
        RuntimeInterface $runtime,
        ResultFactoryInterface $resultFactory,
        QueryValidatorInterface $queryValidator
    ) {
        $this->runtime = $runtime;
        $this->resultFactory = $resultFactory;
        $this->queryValidator = $queryValidator;
    }

    public function select(QueryInterface $query, NodeValueInterface $rootNode): SelectResultInterface
    {
        $values = $query($rootNode, $this->runtime);

        return $this
            ->resultFactory
            ->createSelectResult($values);
    }

    public function selectOne(QueryInterface $query, NodeValueInterface $rootNode): SelectOneResultInterface
    {
        $values = $this
            ->queryValidator
            ->getDefiniteQuery($query)($rootNode, $this->runtime);

        return $this
            ->resultFactory
            ->createSelectOneResult($values);
    }

    public function selectPaths(QueryInterface $query, NodeValueInterface $rootNode): SelectPathsResultInterface
    {
        $values = $this
            ->queryValidator
            ->getPathQuery($query)($rootNode, $this->runtime);

        return $this
            ->resultFactory
            ->createSelectPathsResult($values);
    }

    public function selectOnePath(QueryInterface $query, NodeValueInterface $rootNode): SelectOnePathResultInterface
    {
        $values = $this
            ->queryValidator
            ->getDefinitePathQuery($query)($rootNode, $this->runtime);

        return $this
            ->resultFactory
            ->createSelectOnePathResult($values);
    }
}
