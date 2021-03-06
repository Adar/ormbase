<?php
/**
 * CacheInterface.php
 * @package    ecsco\ormbase
 * @subpackage CACHE
 */

declare(strict_types=1);

namespace ecsco\ormbase\cache;

/**
 * CacheInterface
 * @package    ecsco\ormbase
 * @subpackage CACHE
 */
interface CacheInterface {

    /**
     * Get an instance
     * @return \ecsco\ormbase\cache\CacheInterface
     */
    public static function getInstance();

    /**
     * Has cached ?
     *
     * @param $cacheType
     * @param $identifier
     *
     * @return boolean
     */
    public function has( $cacheType, $identifier ): bool;

    /**
     * Add content to cache
     *
     * @param $cacheType
     * @param $identifier
     * @param $objects
     *
     * @return bool
     * @throws \ecsco\ormbase\exception\CacheException
     */
    public function add( $cacheType, $identifier, $objects ): bool;

    /**
     * Get cached content
     *
     * @param string $cacheType
     * @param string $identifier
     *
     * @return mixed
     */
    public function get( string $cacheType, string $identifier = null );

    /**
     * Destroy cached content
     *
     * @param      $cacheType
     * @param bool $identifier
     *
     * @return bool
     */
    public function destroy( $cacheType, $identifier = false ): bool;

    /**
     * To Array
     * @return array
     */
    public function toArray();
}
