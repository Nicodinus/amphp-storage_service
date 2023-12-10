<?php

namespace Nicodinus\StorageService;

use Amp\File\File;
use Amp\Promise;
use Amp\Success;
use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;
use function Amp\call;
use function Amp\File\filesystem;

abstract class AbstractFileStorage implements StorageInterface
{
    /** @var File */
    private File $fileHandle;

    /** @var bool */
    private bool $isSynced;

    /** @var Mutex */
    private Mutex $mutex;

    //

    /**
     * AbstractFileStorage constructor.
     */
    protected function __construct()
    {
        $this->isSynced = true;
        $this->mutex = new LocalMutex();
    }

    /**
     * @param string $path
     *
     * @return Promise<static>
     */
    public static function open(string $path): Promise
    {
        return call(static function () use (&$path) {
            $instance = new static();
            $instance->fileHandle = yield filesystem()->openFile($path, 'c+');
            yield $instance->reload();
            return $instance;
        });
    }

    /**
     * @return string
     */
    protected abstract function _serializedData(): string;

    /**
     * @param string|null $data
     *
     * @return void
     */
    protected abstract function _unserializeData(?string $data = null): void;

    /**
     * Writes memory buffer to file
     *
     * @return Promise<void>
     */
    public function sync(): Promise
    {
        if ($this->isSynced) {
            return new Success();
        }

        return call(function () {

            $lock = yield $this->mutex->acquire();

            try {

                $this->fileHandle->seek(0, File::SEEK_SET);
                yield $this->fileHandle->truncate(0);

                yield $this->fileHandle->write($this->_serializedData());
                $this->isSynced = true;

            } finally {
                $lock->release();
            }

        });
    }

    /**
     * Reads file to memory
     *
     * @return Promise<void>
     */
    public function reload(): Promise
    {
        if (!$this->isSynced) {
            throw new \RuntimeException("Storage is not synced!");
        }

        return call(function () {

            $lock = yield $this->mutex->acquire();

            try {

                $this->fileHandle->seek(0, File::SEEK_SET);

                $data = "";
                while (!$this->fileHandle->eof()) {
                    $data .= yield $this->fileHandle->read();
                }

                if (\strlen($data) > 0) {
                    $this->_unserializeData($data);
                    $this->isSynced = true;
                } else {
                    $this->isSynced = false;
                    $this->_unserializeData(null);
                }

            } finally {
                $lock->release();
            }

            yield $this->sync();

        });
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        Promise\rethrow($this->close());
    }

    /**
     * @return Promise<void>
     */
    public function close(): Promise
    {
        return call(function () {
            yield $this->sync();
            return $this->fileHandle->close();
        });
    }

    /**
     * @return Promise<void>
     */
    public function forceSync(): Promise
    {
        $this->isSynced = false;
        return $this->sync();
    }
}