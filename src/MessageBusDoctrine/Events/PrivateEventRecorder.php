<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Events;

use Nepada\MessageBus\Events\Event;

trait PrivateEventRecorder
{

    /**
     * @var Event[]
     */
    private array $recordedEvents = [];

    /**
     * @return Event[]
     */
    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function eraseRecordedEvents(): void
    {
        $this->recordedEvents = [];
    }

    protected function record(Event $event): void
    {
        $this->recordedEvents[] = $event;
    }

}
