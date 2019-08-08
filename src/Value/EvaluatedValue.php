<?php
declare(strict_types=1);

namespace Remorhaz\JSON\Path\Value;

use Generator;
use Iterator;
use Remorhaz\JSON\Data\Event\ScalarEvent;

final class EvaluatedValue implements EvaluatedValueInterface
{

    private $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function getData(): bool
    {
        return $this->value;
    }

    public function createEventIterator(): Iterator
    {
        return $this->createGenerator();
    }

    private function createGenerator(): Generator
    {
        yield new ScalarEvent($this);
    }
}
