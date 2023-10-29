<?php
declare(strict_types = 1);

namespace Nepada\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class TransactionMiddleware implements MiddlewareInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->entityManager->beginTransaction();

        $transactionNestingLevel = $this->entityManager->getConnection()->getTransactionNestingLevel();

        try {
            $result = $stack->next()->handle($envelope, $stack);

            $this->assertTransactionNestingLevel($transactionNestingLevel);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $result;

        } catch (\Throwable $exception) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->assertTransactionNestingLevel($transactionNestingLevel, $exception);
                $this->entityManager->rollback();
                $this->entityManager->close();
            }

            throw $exception;
        }
    }

    private function assertTransactionNestingLevel(
        int $expectedTransactionNestingLevel,
        ?\Throwable $previousException = null,
    ): void
    {
        $transactionNestingLevel = $this->entityManager->getConnection()->getTransactionNestingLevel();

        if ($expectedTransactionNestingLevel !== $transactionNestingLevel) {
            throw new \LogicException(
                sprintf(
                    'Transaction nesting level mismatch. Expected level: %d, got: %d.',
                    $expectedTransactionNestingLevel,
                    $transactionNestingLevel,
                ),
                0,
                $previousException,
            );
        }
    }

}
