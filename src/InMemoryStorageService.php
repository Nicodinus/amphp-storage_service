<?php

namespace Nicodinus\StorageService;

use Dflydev\DotAccessData\Data;

class InMemoryStorageService implements StorageInterface
{
    use ArrayAccessStorageTrait;

    //

    /** @var Data */
    private Data $inMemoryData;

    /**
     * InMemoryStorageService constructor.
     */
    public function __construct()
    {
        $this->inMemoryData = new Data();
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
    }
}