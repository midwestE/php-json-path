<?php
declare(strict_types=1);

namespace Remorhaz\JSON\Data\Event;

interface ScalarEventInterface extends EventInterface
{

    public function getData();
}
