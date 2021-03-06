<?php

namespace Matthimatiker\OpcacheBundle\Tests\ByteCodeCache;

use Matthimatiker\OpcacheBundle\ByteCodeCache\ByteCodeCacheInterface;
use Matthimatiker\OpcacheBundle\ByteCodeCache\Memory;
use Matthimatiker\OpcacheBundle\ByteCodeCache\PhpOpcache;
use Matthimatiker\OpcacheBundle\ByteCodeCache\Script;
use Matthimatiker\OpcacheBundle\ByteCodeCache\ScriptCollection;
use Matthimatiker\OpcacheBundle\ByteCodeCache\Statistics;

class PhpOpcacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * System under test.
     *
     * @var PhpOpcache
     */
    protected $opcache = null;

    /**
     * The data that is passed to the cache instance.
     *
     * @var array<string, mixed>
     */
    protected $data = null;

    /**
     * The configuration data that is passed to the cache instance.
     *
     * @var array<mixed>
     */
    protected $configuration = null;

    /**
     * Initializes the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->data          = require(__DIR__ . '/_files/PhpOpcache/active_cache.php');
        $this->configuration = require(__DIR__ . '/_files/PhpOpcache/configuration.php');
        $this->opcache       = new PhpOpcache($this->data, $this->configuration);
    }

    /**
     * Cleans up the test environment.
     */
    protected function tearDown()
    {
        $this->opcache       = null;
        $this->configuration = null;
        $this->data          = null;
        parent::tearDown();
    }

    public function testImplementsInterface()
    {
        $this->assertInstanceOf(ByteCodeCacheInterface::class, $this->opcache);
    }

    public function testProvidesCorrectUsedMemory()
    {
        $memory = $this->opcache->memory();

        $this->assertInstanceOf(Memory::class, $memory);
        $this->assertEquals(
            29836904 / 1024 / 1024,
            $memory->getUsedInMb(),
            'Invalid memory usage reported.',
            0.001
        );
    }

    public function testProvidesCorrectWastedMemory()
    {
        $memory = $this->opcache->memory();

        $this->assertInstanceOf(Memory::class, $memory);
        $this->assertEquals(
            6619288 / 1024 / 1024,
            $memory->getWastedInMb(),
            'Invalid memory usage reported.',
            0.001
        );
    }

    public function testProvidesCorrectMemorySize()
    {
        $memory = $this->opcache->memory();

        $this->assertInstanceOf(Memory::class, $memory);
        $this->assertEquals(64.0, $memory->getSizeInMb(), 'Invalid memory size reported.', 0.001);
    }

    public function testProvidesCorrectNumberOfHits()
    {
        $statistics = $this->opcache->statistics();

        $this->assertInstanceOf(Statistics::class, $statistics);
        $this->assertEquals($statistics->getHits(), 5247);
    }

    public function testProvidesCorrectNumberOfMisses()
    {
        $statistics = $this->opcache->statistics();

        $this->assertInstanceOf(Statistics::class, $statistics);
        $this->assertEquals($statistics->getMisses(), 989);
    }

    public function testProvidesCorrectHitRate()
    {
        $statistics = $this->opcache->statistics();

        $this->assertInstanceOf(Statistics::class, $statistics);
        $this->assertEquals(
            84.140474663245669,
            $statistics->getHitRateInPercent(),
            'Invalid hit rate provided.',
            0.001
        );
    }

    public function testIsEnabledReturnsTrueIfDataIsAvailable()
    {
        $this->assertTrue($this->opcache->isEnabled());
    }

    public function testObjectCanBeCreatedWithoutArguments()
    {
        $this->opcache = new PhpOpcache();

        $this->assertInstanceOf(Memory::class, $this->opcache->memory());
        $this->assertInstanceOf(Statistics::class, $this->opcache->statistics());
        $this->assertInternalType('array', $this->opcache->getConfiguration());
    }

    public function testAccessLayerWorksIfNoCacheDataIsAvailable()
    {
        $this->opcache = new PhpOpcache(false);

        $this->assertFalse($this->opcache->isEnabled());
        $this->assertInstanceOf(Memory::class, $this->opcache->memory());
        $this->assertInstanceOf(Statistics::class, $this->opcache->statistics());
    }

    public function testReturnsCorrectNumberOfCachedScripts()
    {
        $scripts = $this->opcache->scripts();

        $this->assertInstanceOf(ScriptCollection::class, $scripts);
        $this->assertCount(count($this->data['scripts']), $scripts);
        $this->assertCount($this->data['opcache_statistics']['num_cached_scripts'], $scripts);
    }

    public function testProvidesDataAboutCachedScripts()
    {
        $scripts = $this->opcache->scripts();

        $this->assertInstanceOf(\Traversable::class, $scripts);
        $this->assertContainsOnly(Script::class, $scripts);
    }

    public function testCacheDeterminesMaxSlotNumberCorrectly()
    {
        $scripts = $this->opcache->scripts();

        $this->assertInstanceOf(ScriptCollection::class, $scripts);
        $this->assertEquals($this->data['opcache_statistics']['max_cached_keys'], $scripts->getSlots()->max());
    }

    public function testCacheDeterminesNumberOfUsedSlotsCorrectly()
    {
        $scripts = $this->opcache->scripts();

        $this->assertInstanceOf(ScriptCollection::class, $scripts);
        $this->assertEquals($this->data['opcache_statistics']['num_cached_scripts'], $scripts->getSlots()->used());
    }

    public function testCacheDeterminesNumberOfWastedSlotsCorrectly()
    {
        $scripts = $this->opcache->scripts();

        $this->assertInstanceOf(ScriptCollection::class, $scripts);
        $allCacheEntries = $this->data['opcache_statistics']['num_cached_keys'];
        $wasted = $allCacheEntries - $this->data['opcache_statistics']['num_cached_scripts'];
        $this->assertEquals(
            $wasted,
            $scripts->getSlots()->wasted()
        );
    }

    public function testCacheReturnsConfiguration()
    {
        $this->assertEquals($this->configuration, $this->opcache->getConfiguration());
    }
}
