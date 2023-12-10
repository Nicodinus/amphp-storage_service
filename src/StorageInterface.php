<?php

namespace Nicodinus\StorageService;

interface StorageInterface extends \ArrayAccess
{
    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function update(string $key, $value): void;

    /**
     * @param string $key
     *
     * @return bool Returns true if key was exists
     */
    public function remove(string $key): bool;
}