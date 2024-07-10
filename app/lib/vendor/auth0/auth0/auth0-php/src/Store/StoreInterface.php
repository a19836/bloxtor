<?php
declare(strict_types=1);

namespace Auth0\SDK\Store;

/**
 * Interface StoreInterface
 *
 * @package Auth0\SDK\Store
 */
interface StoreInterface
{
    /**
     * Set a value on the store
     *
     * @param string $key   Key to set.
     * @param mixed  $value Value to set.
     *
     * @return void
     */
    public function set(string $key, $value);

    /**
     * Get a value from the store by a given key.
     *
     * @param string      $key     Key to get.
     * @param null|string $default Return value if key not found.
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Remove a value from the store
     *
     * @param string $key Key to delete.
     *
     * @return void
     */
    public function delete(string $key);
}
