<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Events;

trait PublicEventRecorder
{

    use PrivateEventRecorder {
        record as public;
    }

}
