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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string|null $objectName
     */
    public function clear($objectName = null): void
    {
        if (func_num_args() > 0) {
            throw new \LogicException('Calling ' . __METHOD__ . '() with any arguments to clear specific entities is deprecated');
        }
        $this->log[] = __METHOD__;
        parent::clear();
    }

}
