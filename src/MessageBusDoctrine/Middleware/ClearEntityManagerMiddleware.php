<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class ClearEntityManagerMiddleware implements MiddlewareInterface
{

    private EntityManagerInterface $entityManager;

    private bool $clearOnStart;

    private bool $clearOnError;

    private bool $clearOnSuccess;

    public function __construct(EntityManagerInterface $entityManager, bool $clearOnStart = true, bool $clearOnSuccess = true, bool $clearOnError = true)
    {
        $this->entityManager = $entityManager;
        $this->clearOnStart = $clearOnStart;
        $this->clearOnError = $clearOnError;
        $this->clearOnSuccess = $clearOnSuccess;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
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
