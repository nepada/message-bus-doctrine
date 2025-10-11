<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusDoctrine\Middleware;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Nepada\MessageBusDoctrine\Middleware\TransactionMiddleware;
use NepadaTests\Environment;
use NepadaTests\MessageBusDoctrine\Fakes\CallbackMiddleware;
use NepadaTests\MessageBusDoctrine\Fakes\RecordingEntityManager;
use NepadaTests\MessageBusDoctrine\Fixtures\TestCommand;
use NepadaTests\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class TransactionMiddlewareTest extends TestCase
{

    public function testChangesArePersistedWhenTransactionSucceeds(): void
    {
        $entityManager = $this->createEntityManager();
        $callback = function () use ($entityManager): void {
            $entityManager->log[] = 'db operations';
        };

        $this->runInTransactionMiddleware($callback, $entityManager);

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'db operations',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::flush',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::commit',
            ],
            $entityManager->log,
        );
    }

    public function testChangesArePersistedWhenTransactionSucceedsAndHasItsOwnNestedTransaction(): void
    {
        $entityManager = $this->createEntityManager();
        $callback = function () use ($entityManager): void {
            $entityManager->beginTransaction();
            $entityManager->log[] = 'db operations';
            $entityManager->flush();
            $entityManager->commit();
        };

        $this->runInTransactionMiddleware($callback, $entityManager);

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'db operations',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::flush',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::commit',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::flush',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::commit',
            ],
            $entityManager->log,
        );
    }

    public function testEntityManagerIsClosedWhenTransactionFails(): void
    {
        $entityManager = $this->createEntityManager();

        Assert::exception(
            function () use ($entityManager): void {
                $this->runInTransactionMiddleware(
                    function () use ($entityManager): void {
                        $entityManager->log[] = 'db operations';
                        throw new \RuntimeException('Error');
                    },
                    $entityManager,
                );
            },
            \RuntimeException::class,
        );

        Assert::false($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'db operations',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::rollback',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::close',
            ],
            $entityManager->log,
        );
    }

    public function testNothingIsDoneWhenMainTransactionIsRolledBackInsideAndExceptionIsThrown(): void
    {
        $entityManager = $this->createEntityManager();
        Assert::exception(
            function () use ($entityManager): void {
                $this->runInTransactionMiddleware(
                    function () use ($entityManager): void {
                        $entityManager->rollback();
                        throw new \RuntimeException('Error');
                    },
                    $entityManager,
                );
            },
            \RuntimeException::class,
        );

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::rollback',
            ],
            $entityManager->log,
        );
    }

    public function testNothingIsDoneWhenMainTransactionIsCommittedInsideAndExceptionIsThrown(): void
    {
        $entityManager = $this->createEntityManager();

        Assert::exception(
            function () use ($entityManager): void {
                $this->runInTransactionMiddleware(
                    function () use ($entityManager): void {
                        $entityManager->commit();
                        throw new \RuntimeException('Error');
                    },
                    $entityManager,
                );
            },
            \RuntimeException::class,
        );

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::commit',
            ],
            $entityManager->log,
        );
    }

    public function testTransactionNestingLevelMismatchExceptionIsRaisedMainTransactionIsRolledBackInside(): void
    {
        $entityManager = $this->createEntityManager();
        $callback = function () use ($entityManager): void {
            $entityManager->rollback();
        };

        Assert::exception(
            function () use ($callback, $entityManager): void {
                $this->runInTransactionMiddleware($callback, $entityManager);
            },
            \LogicException::class,
            'Transaction nesting level mismatch. Expected level: 1, got: 0.',
        );

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::rollback',
            ],
            $entityManager->log,
        );
    }

    public function testTransactionNestingLevelMismatchExceptionIsRaisedWhenMainTransactionIsCommittedInside(): void
    {
        $entityManager = $this->createEntityManager();
        $callback = function () use ($entityManager): void {
            $entityManager->commit();
        };

        Assert::exception(
            function () use ($callback, $entityManager): void {
                $this->runInTransactionMiddleware($callback, $entityManager);
            },
            \LogicException::class,
            'Transaction nesting level mismatch. Expected level: 1, got: 0.',
        );

        Assert::true($entityManager->isOpen());
        Assert::false($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::commit',
            ],
            $entityManager->log,
        );
    }

    public function testTransactionNestingLevelMismatchExceptionIsRaisedWhenNewTransactionIsStartedInside(): void
    {
        $entityManager = $this->createEntityManager();
        $callback = function () use ($entityManager): void {
            $entityManager->beginTransaction();
        };

        Assert::exception(
            function () use ($callback, $entityManager): void {
                $this->runInTransactionMiddleware($callback, $entityManager);
            },
            \LogicException::class,
            'Transaction nesting level mismatch. Expected level: 1, got: 2.',
        );

        Assert::true($entityManager->isOpen());
        Assert::true($entityManager->getConnection()->isTransactionActive());
        Assert::same(
            [
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
                'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction',
            ],
            $entityManager->log,
        );
    }

    /**
     * @param-immediately-invoked-callable $callback
     */
    private function runInTransactionMiddleware(callable $callback, EntityManagerInterface $entityManager): void
    {
        $middleware = new TransactionMiddleware($entityManager);
        $stack = new StackMiddleware([$middleware, new CallbackMiddleware($callback)]);
        $envelope = new Envelope(new TestCommand());
        $middleware->handle($envelope, $stack);
    }

    private function createEntityManager(): RecordingEntityManager
    {
        $tempDir = Environment::getTempDir();
        $configuration = ORMSetup::createAttributeMetadataConfiguration([$tempDir]);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => "$tempDir/db.sqlite"]);
        @$connection->executeQuery('SELECT 1'); // force connection creation and suppress deprecation error of DBAL 2.x on PHP 8.5
        $entityManger = new EntityManager($connection, $configuration);
        $entityManger->getConnection()->setNestTransactionsWithSavepoints(true);

        return new RecordingEntityManager($entityManger);
    }

}


(new TransactionMiddlewareTest())->run();
