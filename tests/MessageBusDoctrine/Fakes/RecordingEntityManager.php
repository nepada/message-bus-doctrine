<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusDoctrine\Fakes;

use Doctrine\ORM\Decorator\EntityManagerDecorator;

class RecordingEntityManager extends EntityManagerDecorator
{

    /**
     * @var string[]
     */
    public array $log = [];

    public function beginTransaction(): void
    {
        $this->log[] = __METHOD__;
        parent::beginTransaction();
    }

    public function commit(): void
    {
        $this->log[] = __METHOD__;
        parent::commit();
    }

    public function rollback(): void
    {
        $this->log[] = __METHOD__;
        parent::rollback();
    }

    /**
     * @param mixed $entity
     */
    public function flush($entity = null): void
    {
        $this->log[] = __METHOD__;
        parent::flush($entity);
    }

    public function close(): void
    {
        $this->log[] = __METHOD__;
        parent::close();
    }

    /**
     * @param mixed $objectName
     */
    public function clear($objectName = null): void
    {
        $this->log[] = __METHOD__;
        parent::clear($objectName);
    }

}
