<?php
/**
 * class.Cache.php
 * @package    ecsco\ormbase
 * @subpackage CACHE
 * @author     Christian Senkowski <cs@e-cs.co>
 * @since      20150106 14:04
 */
declare(strict_types=1);

namespace ecsco\ormbase\cache\memcached;

use ecsco\ormbase\cache\CacheAccess;
use ecsco\ormbase\cache\CacheInterface;
use ecsco\ormbase\config\Config;
use ecsco\ormbase\exception\CacheException;
use ecsco\ormbase\traits\CallTrait;
use ecsco\ormbase\traits\GetTrait;
use ecsco\ormbase\traits\NoCloneTrait;
use ecsco\ormbase\traits\SingletonTrait;

/**
 * class Cache
 * @package    ecsco\ormbase
 * @subpackage CACHE
 * @author     Christian Senkowski <cs@e-cs.co>
 * @since      20150106 14:04
 */
class Cache implements CacheInterface {

    use SingletonTrait;
    use GetTrait;
    use CallTrait;
    use NoCloneTrait;

    /**
     * @var int
     */
    private static $cacheTime = 30;

    /**
     * @var \Memcached[]
     */
    private static $memcachedInstances = [ ];

    /**
     * Has cached ?
     *
     * @param $cacheType
     * @param $identifier
     *
     * @return bool
     */
    final public function has( $cacheType, $identifier ): bool {

        if ( !isset( self::$memcachedInstances[ $cacheType ] ) ) {
            self::addInstance( $cacheType );
        }
        $cache = self::$memcachedInstances[ $cacheType ];
        $cache->get( md5( $identifier ) );
        if ( $cache->getResultCode() != \Memcached::RES_NOTFOUND ) {
            return true;
        }

        return false;
    }

    /**
     * addInstance
     *
     * @param $instanceName
     *
     * @return void
     */
    protected function addInstance( $instanceName ) {

        self::$memcachedInstances[ $instanceName ] = new \Memcached( $instanceName );
        self::$memcachedInstances[ $instanceName ]->setOption( \Memcached::OPT_PREFIX_KEY, $instanceName );
        self::$memcachedInstances[ $instanceName ]->setOption( \Memcached::OPT_BINARY_PROTOCOL, true );
        if ( !count( self::$memcachedInstances[ $instanceName ]->getServerList() ) ) {
            self::$memcachedInstances[ $instanceName ]->addServer( Config::getInstance()->getItem( 'cache', 'server' ), Config::getInstance()->getItem( 'cache', 'port' ) );
        }
    }

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
    final public function add( $cacheType, $identifier, $objects ): bool {

        if ( !isset( self::$memcachedInstances[ $cacheType ] ) ) {
            self::addInstance( $cacheType );
        }
        $cache = self::$memcachedInstances[ $cacheType ];

        $res = $cache->set( md5( $identifier ), $objects, self::$cacheTime );
        if ( $cache->getResultCode() == \Memcached::RES_NOTSTORED || !$res ) {
            throw new CacheException( "Could not add memcached objects $identifier" );
        }

        CacheAccess::$stats[ 'added' ]++;

        return true;
    }

    /**
     * Get cached content
     *
     * @param      $cacheType
     * @param bool $identifier
     *
     * @return array|mixed
     */
    final public function get( $cacheType, $identifier = false ) {

        if ( !isset( self::$memcachedInstances[ $cacheType ] ) ) {
            self::addInstance( $cacheType );
        }
        $cache = self::$memcachedInstances[ $cacheType ];
        $r = $cache->get( md5( $identifier ) );
        if ( $cache->getResultCode() == \Memcached::RES_NOTFOUND ) {
            return [ ];
        }

        CacheAccess::$stats[ 'provided' ]++;

        return $r;
    }

    /**
     * Destroy cached content
     *
     * @param      $cacheType
     * @param bool $identifier
     *
     * @return bool
     */
    final public function destroy( $cacheType, $identifier = false ): bool {

        if ( !isset( self::$memcachedInstances[ $cacheType ] ) ) {
            self::addInstance( $cacheType );
        }
        $cache = self::$memcachedInstances[ $cacheType ];
        if ( $identifier ) {
            $cache->delete( md5( $identifier ) );
        }
        else {
            if ( isset( self::$memcachedInstances[ $cacheType ] ) ) {
                self::$memcachedInstances[ $cacheType ]->flush();
            }
        }
        CacheAccess::$stats[ 'destroyed' ]++;

        return true;
    }

    /**
     * To Array
     * @return array
     */
    final public function toArray() {

        $rVal = [ ];
        if ( !self::$memcachedInstances ) {
            return [ ];
        }

        foreach ( self::$memcachedInstances AS $key => $cache ) {
            $keys = $cache->getAllKeys();
            if ( !$keys ) {
                continue;
            }
        }

        $rVal[ 'stats' ] = CacheAccess::$stats;

        return $rVal;
    }
}

/**
 *  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4 foldmethod=marker:
 */
