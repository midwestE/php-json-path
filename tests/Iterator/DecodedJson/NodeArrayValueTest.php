<?php
declare(strict_types=1);

namespace Remorhaz\JSON\Path\Test\Iterator\DecodedJson;

use Iterator;
use function iterator_to_array;
use PHPUnit\Framework\TestCase;
use Remorhaz\JSON\Path\Iterator\DecodedJson\EventExporter;
use Remorhaz\JSON\Path\Iterator\DecodedJson\NodeArrayValue;
use Remorhaz\JSON\Path\Iterator\DecodedJson\NodeValueFactory;
use Remorhaz\JSON\Path\Iterator\Event\ValueEventInterface;
use Remorhaz\JSON\Path\Iterator\Fetcher;
use Remorhaz\JSON\Path\Iterator\DecodedJson\Event\ElementEvent;
use Remorhaz\JSON\Path\Iterator\Event\DataAwareEventInterface;
use Remorhaz\JSON\Path\Iterator\Event\DataEventInterface;
use Remorhaz\JSON\Path\Iterator\DecodedJson\Event\AfterArrayEvent;
use Remorhaz\JSON\Path\Iterator\DecodedJson\Event\BeforeArrayEvent;
use Remorhaz\JSON\Path\Iterator\DecodedJson\Exception\InvalidElementKeyException;
use Remorhaz\JSON\Path\Iterator\DecodedJson\Event\NodeScalarEvent;
use Remorhaz\JSON\Path\Iterator\Event\ElementEventInterface;
use Remorhaz\JSON\Path\Iterator\Event\PropertyEventInterface;
use Remorhaz\JSON\Path\Iterator\Path;
use Remorhaz\JSON\Path\Iterator\PathAwareInterface;

/**
 * @covers \Remorhaz\JSON\Path\Iterator\DecodedJson\NodeArrayValue
 */
class NodeArrayValueTest extends TestCase
{

    /**
     * @param array $data
     * @param array $expectedValue
     * @dataProvider providerValidData
     */
    public function testCreateIterator_Constructed_GeneratesMatchingEventList(
        array $data,
        array $expectedValue
    ): void {
        $value = new NodeArrayValue($data, Path::createEmpty(), new NodeValueFactory);

        $actualEvents = iterator_to_array($value->createIterator(), false);
        self::assertSame($expectedValue, $this->exportEvents(...$actualEvents));
    }

    public function providerValidData(): array
    {
        return [
            'Empty array' => [
                [],
                [
                    ['class' => BeforeArrayEvent::class, 'path' => [], 'data' => []],
                    ['class' => AfterArrayEvent::class, 'path' => [], 'data' => []],
                ],
            ],
            'Array with scalar element' => [
                [1],
                [
                    ['class' => BeforeArrayEvent::class, 'path' => [], 'data' => [1]],
                    ['class' => ElementEvent::class, 'path' => [], 'index' => 0],
                    ['class' => NodeScalarEvent::class, 'path' => [0], 'data' => 1],
                    ['class' => AfterArrayEvent::class, 'path' => [], 'data' => [1]],
                ],
            ],
            'Array with array element' => [
                [[1]],
                [
                    ['class' => BeforeArrayEvent::class, 'path' => [], 'data' => [[1]]],
                    ['class' => ElementEvent::class, 'path' => [], 'index' => 0],
                    ['class' => BeforeArrayEvent::class, 'path' => [0], 'data' => [1]],
                    ['class' => ElementEvent::class, 'path' => [0], 'index' => 0],
                    ['class' => NodeScalarEvent::class, 'path' => [0, 0], 'data' => 1],
                    ['class' => AfterArrayEvent::class, 'path' => [0], 'data' => [1]],
                    ['class' => AfterArrayEvent::class, 'path' => [], 'data' => [[1]]],
                ],
            ],
        ];
    }

    /**
     * @param array $data
     * @dataProvider providerArrayWithInvalidIndex
     */
    public function testCreateIterator_ArrayDataWithInvalidIndex_ThrowsException(array $data): void
    {
        $value = new NodeArrayValue($data, Path::createEmpty(), new NodeValueFactory);

        $this->expectException(InvalidElementKeyException::class);
        iterator_to_array($value->createIterator());
    }

    public function providerArrayWithInvalidIndex(): array
    {
        return [
            'Non-zero first index' => [[1 => 'a']],
            'Non-integer first index' => [['a' => 'b']],
        ];
    }

    private function exportEvents(DataEventInterface ...$events): array
    {
        $result = [];
        foreach ($events as $event) {
            $result[] = $this->exportEvent($event);
        }

        return $result;
    }

    private function exportEvent(DataEventInterface $event): array
    {
        $result = [
            'class' => get_class($event),
        ];

        if ($event instanceof PathAwareInterface) {
            $result += ['path' => $event->getPath()->getElements()];
        }

        if ($event instanceof ValueEventInterface) {
            $value = $event->getValue();
            if ($value instanceof PathAwareInterface) {
                $result += ['path' => $value->getPath()->getElements()];
            }
        }

        if ($event instanceof DataAwareEventInterface) {
            $result += ['data' => $this->exportData($event->getData())];
        }

        if ($event instanceof ValueEventInterface) {
            $result += ['data' => $this->exportData($this->exportIterator($event->getValue()->createIterator()))];
        }

        if ($event instanceof ElementEventInterface) {
            $result += ['index' => $event->getIndex()];
        }

        if ($event instanceof PropertyEventInterface) {
            $result += ['name' => $event->getName()];
        }

        return $result;
    }

    private function exportData($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $index => $element) {
                $result[$index] = $this->exportData($element);
            }
            return $result;
        }

        if (is_object($data)) {
            $result = [
                'class' => get_class($data),
                'data' => [],
            ];
            foreach (get_object_vars($data) as $name => $property) {
                $result['data'][$name] = $this->exportData($property);
            }
            return $result;
        }

        return $data;
    }

    private function exportIterator(Iterator $iterator)
    {
        return (new EventExporter(new Fetcher))->export($iterator);
    }
}
