<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class ClearEntityManagerMiddleware implements MiddlewareInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private bool $clearOnStart = true,
        private bool $clearOnSuccess = true,
        private bool $clearOnError = true,
    )
    {
    }

    public function handle(
        Envelope $envelope,
        StackInterface $stack,
    ): Envelope
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            return $stack->next()->handle($envelope, $stack);
        }

        try {
            if ($this->clearOnStart) {
                $this->entityManager->clear();
            }
            $result = $stack->next()->handle($envelope, $stack);
            if ($this->clearOnSuccess) {
                $this->entityManager->clear();
            }
            return $result;

        } catch (\Throwable $exception) {
            if ($this->clearOnError) {
                $this->entityManager->clear();
            }
            throw $exception;
        }
    }

}
