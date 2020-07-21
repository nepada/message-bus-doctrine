<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Events;

use Nepada\MessageBus\Events\Event;

interface ContainsRecordedEvents
{

    /**
     * @return Event[]
     */
    public function getRecordedEvents(): array;

    public function eraseRecordedEvents(): void;

}
