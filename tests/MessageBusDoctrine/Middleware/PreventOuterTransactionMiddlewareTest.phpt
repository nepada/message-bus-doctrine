<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Nepada\MessageBusDoctrine\Middleware\PreventOuterTransactionMiddleware;
use Nepada\MessageBusDoctrine\Middleware\TransactionMiddleware;
use NepadaTests\Environment;
use NepadaTests\MessageBusDoctrine\Fixtures\TestCommand;
use NepadaTests\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class PreventOuterTransactionMiddlewareTest extends TestCase
{

    public function testSucceedWithNoOuterTransaction(): void
    {
        $entityManager = $this->createEntityManager();
        Assert::noError(
            function () use ($entityManager): void {
                $this->runInMiddleware($entityManager);
            },
        );
    }

    public function testSucceedWithNoOuterTransactionAndNestedHandling(): void
    {
        $entityManager = $this->createEntityManager();
        $preventOuterTransactionMiddleware = new PreventOuterTransactionMiddleware($entityManager);
        $transactionMiddleware = new TransactionMiddleware($entityManager);
        $stack = new StackMiddleware([$preventOuterTransactionMiddleware, $transactionMiddleware, $preventOuterTransactionMiddleware, $transactionMiddleware]);
        $envelope = new Envelope(new TestCommand());
        Assert::noError(
            function () use ($preventOuterTransactionMiddleware, $envelope, $stack): void {
                $preventOuterTransactionMiddleware->handle($envelope, $stack);
            },
        );
    }

    public function testFailOnOuterTransaction(): void
    {
        $entityManager = $this->createEntityManager();
        $entityManager->beginTransaction();
        Assert::exception(
            function () use ($entityManager): void {
                $this->runInMiddleware($entityManager);
            },
            \LogicException::class,
            'Database transaction was already started.',
        );
    }

    private function runInMiddleware(EntityManagerInterface $entityManager): void
    {
        $middleware = new PreventOuterTransactionMiddleware($entityManager);
        $stack = new StackMiddleware([$middleware]);
        $envelope = new Envelope(new TestCommand());
        $middleware->handle($envelope, $stack);
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $tempDir = Environment::getTempDir();
        $configuration = Setup::createAnnotationMetadataConfiguration([$tempDir], false, null, null, false);
        return EntityManager::create(['driver' => 'pdo_sqlite', 'path' => "$tempDir/db.sqlite"], $configuration);
    }

}


(new PreventOuterTransactionMiddlewareTest())->run();
