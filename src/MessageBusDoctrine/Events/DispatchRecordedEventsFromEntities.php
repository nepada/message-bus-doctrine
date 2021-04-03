<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Proxy;
use Nepada\MessageBus\Events\EventDispatcher;

final class DispatchRecordedEventsFromEntities implements EventSubscriber
{

    private EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::preFlush,
            Events::postFlush,
        ];
    }

    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                $this->collectEventsFromEntity($entity);
            }
        }
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $this->collectEventsFromEntity($entity);
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                $this->collectEventsFromEntity($entity);
            }
        }
    }

    private function collectEventsFromEntity(?object $entity): void
    {
        if ($entity === null) {
            return;
        }
        if (! $entity instanceof ContainsRecordedEvents) {
            return;
        }

        if ($entity instanceof Proxy && ! $entity->__isInitialized()) {
            return;
        }

        foreach ($entity->getRecordedEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
        $entity->eraseRecordedEvents();
    }

}
