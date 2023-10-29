<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusDoctrine\Middleware;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Nepada\MessageBusDoctrine\Middleware\ClearEntityManagerMiddleware;
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
class ClearEntityManagerMiddlewareTest extends TestCase
{

    public function testEntityManagerIsNotClearedwhenInActiveTransaction(): void
    {
        $entityManager = $this->createEntityManager();
        $entityManager->beginTransaction();
        $middleware = new ClearEntityManagerMiddleware($entityManager, true, true, true);
        $callback = function () use ($entityManager): void {
            $entityManager->log[] = 'db operations';
        };

        $this->runInClearEntityManagerMiddleware($callback, $middleware);

        Assert::same(['NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::beginTransaction', 'db operations'], $entityManager->log);
    }

    /**
     * @dataProvider getStates
     */
    public function testClearOnStart(bool $isEnabled): void
    {
        $entityManager = $this->createEntityManager();
        $middleware = new ClearEntityManagerMiddleware($entityManager, $isEnabled, false, false);
        $callback = function () use ($entityManager): void {
            $entityManager->log[] = 'db operations';
        };

        $this->runInClearEntityManagerMiddleware($callback, $middleware);

        $expectedLog = $isEnabled ? ['NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::clear', 'db operations'] : ['db operations'];
        Assert::same($expectedLog, $entityManager->log);
    }

    /**
     * @dataProvider getStates
     */
    public function testClearOnError(bool $isEnabled): void
    {
        $entityManager = $this->createEntityManager();
        $middleware = new ClearEntityManagerMiddleware($entityManager, false, false, $isEnabled);

        Assert::exception(
            function () use ($entityManager, $middleware): void {
                $this->runInClearEntityManagerMiddleware(
                    function () use ($entityManager): void {
                        $entityManager->log[] = 'db operations';
                        throw new \Exception();
                    },
                    $middleware,
                );
            },
            \Throwable::class,
        );

        $expectedLog = $isEnabled ? ['db operations', 'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::clear'] : ['db operations'];
        Assert::same($expectedLog, $entityManager->log);
    }

    /**
     * @dataProvider getStates
     */
    public function testClearOnSuccess(bool $isEnabled): void
    {
        $entityManager = $this->createEntityManager();
        $middleware = new ClearEntityManagerMiddleware($entityManager, false, $isEnabled, false);
        $callback = function () use ($entityManager): void {
            $entityManager->log[] = 'db operations';
        };

        $this->runInClearEntityManagerMiddleware($callback, $middleware);

        $expectedLog = $isEnabled ? ['db operations', 'NepadaTests\\MessageBusDoctrine\\Fakes\\RecordingEntityManager::clear'] : ['db operations'];
        Assert::same($expectedLog, $entityManager->log);
    }

    private function runInClearEntityManagerMiddleware(callable $callback, ClearEntityManagerMiddleware $middleware): void
    {
        $stack = new StackMiddleware([$middleware, new CallbackMiddleware($callback)]);
        $envelope = new Envelope(new TestCommand());
        $middleware->handle($envelope, $stack);
    }

    private function createEntityManager(): RecordingEntityManager
    {
        $tempDir = Environment::getTempDir();
        $configuration = Setup::createAnnotationMetadataConfiguration([$tempDir], false, null, null, false);
        $entityManger = EntityManager::create(['driver' => 'pdo_sqlite', 'path' => "$tempDir/db.sqlite"], $configuration);

        return new RecordingEntityManager($entityManger);
    }

    /**
     * @return mixed[]
     */
    protected function getStates(): array
    {
        return [
            [true],
            [false],
        ];
    }

}


(new ClearEntityManagerMiddlewareTest())->run();
