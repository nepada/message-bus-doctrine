<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class PreventOuterTransactionMiddleware implements MiddlewareInterface
{

    private bool $isHandlingRootMessage = false;

    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function handle(
        Envelope $envelope,
        StackInterface $stack,
    ): Envelope
    {
        if ($this->isHandlingRootMessage) {
            return $stack->next()->handle($envelope, $stack);
        }

        if ($this->entityManager->getConnection()->isTransactionActive()) {
            throw new \LogicException('Database transaction was already started.');
        }

        try {
            $this->isHandlingRootMessage = true;
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->isHandlingRootMessage = false;
        }
    }

}
