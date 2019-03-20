<?php

namespace Shyim;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class LocalCacheTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp()
    {
        parent::setUp();
        $this->root = vfsStream::setUp('cache');
    }

    public function testGetPlugin()
    {
        $this->initCacheWithPlugin();

        LocalCache::init(vfsStream::url('cache'));

        $this->assertEquals(vfsStream::url('cache/.shopware-plugins/SwagDigitalPublishing-3.3.0.zip'), LocalCache::getPlugin('SwagDigitalPublishing', '3.3.0'));
    }

    public function testCleanByPath()
    {
        $this->initCacheWithPlugin();
        $fName = vfsStream::url('cache/.shopware-plugins/SwagDigitalPublishing-3.3.0.zip');
        LocalCache::cleanByPath($fName);

        $this->assertFileNotExists($fName);
    }

    public function testInitCrateCacheDirectory()
    {
        LocalCache::init(vfsStream::url('cache'));
        $this->assertDirectoryExists(vfsStream::url('cache/.shopware-plugins'));
    }

    public function testGetCachePath()
    {
        LocalCache::init(vfsStream::url('cache'));
        $this->assertEquals(vfsStream::url('cache/.shopware-plugins/SwagDigitalPublishing-3.3.0.zip'), LocalCache::getCachePath('SwagDigitalPublishing', '3.3.0'));
    }

    private function initCacheWithPlugin()
    {
        $plugins = vfsStream::newDirectory('.shopware-plugins', 0777)->at($this->root);
        vfsStream::newFile('SwagDigitalPublishing-3.3.0.zip', 0777)->at($plugins);
    }
}
