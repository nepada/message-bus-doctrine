<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusDoctrine\Middleware;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Nepada\MessageBusDoctrine\Middleware\PreventOuterTransactionMiddleware;
use Nepada\MessageBusDoctrine\Middleware\TransactionMiddleware;
use NepadaTests\Environment;
use NepadaTests\MessageBusDoctrine\Fixtures\TestCommand;
use NepadaTests\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackMiddleware;
use Tester\Assert;
use function method_exists;

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
        $configuration = ORMSetup::createAttributeMetadataConfiguration([$tempDir]);
        if (PHP_VERSION_ID >= 8_04_00 && method_exists($configuration, 'enableNativeLazyObjects')) {
            $configuration->enableNativeLazyObjects(true);
        }
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => "$tempDir/db.sqlite"]);
        @$connection->executeQuery('SELECT 1'); // force connection creation and suppress deprecation error of DBAL 2.x on PHP 8.5
        return new EntityManager($connection, $configuration);
    }

}


(new PreventOuterTransactionMiddlewareTest())->run();
