<?php

namespace Nicodinus\StorageService;

use Amp\Promise;
use Amp\Serialization\JsonSerializer;
use Amp\Serialization\SerializationException;
use Dflydev\DotAccessData\Data;

class JsonStorageService extends AbstractFileStorage
{
    /** @var JsonSerializer */
    private JsonSerializer $jsonSerializer;

    /** @var Data */
    private Data $inMemoryData;

    //

    /**
     * JsonStorage constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->jsonSerializer = JsonSerializer::withAssociativeArrays();
        $this->inMemoryData = new Data();
    }

    /**
     * @inheritDoc
     *
     * @throws SerializationException
     */
    protected function _serializedData(): string
    {
        return $this->jsonSerializer->serialize($this->inMemoryData->export());
    }

    /**
     * @inheritDoc
     *
     * @throws SerializationException
     */
    protected function _unserializeData(?string $data = null): void
    {
        $this->inMemoryData = new Data($data !== null ? $this->jsonSerializer->unserialize($data) : []);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->inMemoryData->has($key);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): bool
    {
        $has = $this->has($key);

        $this->inMemoryData->remove($key);
        Promise\rethrow($this->forceSync());

        return $has;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key)
    {
        return $this->inMemoryData->get($key, null);
    }

    /**
     * @inheritDoc
     */
    public function update(string $key, $value): void
    {
        $this->inMemoryData->set($key, $value);
        Promise\rethrow($this->forceSync());
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->update($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}