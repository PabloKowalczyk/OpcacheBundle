<?php

namespace Matthimatiker\OpcacheBundle\ByteCodeCache;

/**
 * Provides access to information about the PHP 5.5 Opcache.
 */
class PhpOpcache implements ByteCodeCacheInterface
{
    /**
     * The cache data.
     *
     * @var array<sting, mixed>
     */
    protected $data = null;

    /**
     * Extracts opcache information from the given data.
     *
     * The provided data should be the result of an opcache_get_status() call.
     *
     * @param array<string, mixed>|null|false $cacheData
     */
    public function __construct($cacheData = null)
    {
        if ($cacheData === null) {
            $cacheData = opcache_get_status();
        }
        $this->data = $this->normalize($cacheData);
    }

    /**
     * Checks if the byte code cache is active.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool)$this->data['opcache_enabled'];
    }

    /**
     * Provides information about the memory usage.
     *
     * @return Memory
     */
    public function memory()
    {
        $usedInBytes   = $this->data['memory_usage']['used_memory'];
        $wastedInBytes = $this->data['memory_usage']['wasted_memory'];
        $sizeInBytes   = $usedInBytes + $wastedInBytes + $this->data['memory_usage']['free_memory'];
        return new Memory(
            $this->bytesToMb($usedInBytes),
            $this->bytesToMb($sizeInBytes),
            $this->bytesToMb($wastedInBytes)
        );
    }

    /**
     * Provides caching statistics.
     *
     * @return Statistics
     */
    public function statistics()
    {
        return new Statistics(
            $this->data['opcache_statistics']['hits'],
            $this->data['opcache_statistics']['misses']
        );
    }

    /**
     * Returns data about cached scripts.
     *
     * @return ScriptCollection
     */
    public function scripts()
    {
        $scripts = array_map(function ($scriptData) {
            /* @var $scriptData array<string, mixed> */
            return new Script(
                $scriptData['full_path'],
                $this->bytesToMb($scriptData['memory_consumption']),
                $scriptData['hits'],
                new \DateTimeImmutable('@' . $scriptData['last_used_timestamp'])
            );
        }, $this->data['scripts']);
        return new ScriptCollection($scripts, $this->calculateCacheSlots());
    }

    /**
     * Calculates cache slot statistics.
     *
     * @return ScriptSlots
     */
    protected function calculateCacheSlots()
    {
        $cachedScripts = $this->data['opcache_statistics']['num_cached_scripts'];
        $wasted        = $this->data['opcache_statistics']['num_cached_keys'] - $cachedScripts;
        return new ScriptSlots(
            $cachedScripts,
            $this->data['opcache_statistics']['max_cached_keys'],
            $wasted
        );
    }

    /**
     * Normalizes the given cache data.
     *
     * @param array<string, mixed>|boolean|mixed $cacheData
     * @return array<string, mixed>
     */
    protected function normalize($cacheData)
    {
        if (!is_array($cacheData)) {
            return $this->createFallbackData();
        }
        return $cacheData;
    }

    /**
     * Converts bytes into MB.
     *
     * @param integer $bytes
     * @return double
     */
    protected function bytesToMb($bytes)
    {
        return $bytes / 1024.0 / 1024.0;
    }

    /**
     * Creates fallback data that is used when no opcache data is available.
     *
     * For example that is the case if the opcache is disabled.
     *
     * @return array<string, mixed>
     */
    protected function createFallbackData()
    {
        return array(
            'opcache_enabled' => false,
            'memory_usage' => array(
                'used_memory'   => 0,
                'free_memory'   => 0,
                'wasted_memory' => 0
            ),
            'opcache_statistics' => array(
                'hits'   => 0,
                'misses' => 0,
                'num_cached_scripts' => 0,
                'max_cached_keys'    => 0
            ),
            'scripts' => array()
        );
    }
}
