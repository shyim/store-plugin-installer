<?php

use Shyim\PluginInstaller;

class PluginInstallTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Composer\IO\BufferIO
     */
    private $output;

    /**
     * @var string
     */
    private $testHost;

    public function setUp()
    {
        $this->testHost = parse_url(getenv('SHOP_URL'), PHP_URL_HOST);
    }

    /**
     * @throws Exception
     */
    public function testInstallSinglePlugin()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'SwagLiveshopping' => '3.2.0'
                ]
            ]
        ]);

        PluginInstaller::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagLiveShopping', $this->output->getOutput());
        $this->assertContains('3.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagLiveShopping');
    }

    /**
     * @throws Exception
     */
    public function testInstallMultiplePlugin()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'SwagLiveshopping' => '3.2.0',
                    'SwagTicketSystem' => '2.2.0'
                ]
            ]
        ]);

        PluginInstaller::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagLiveShopping', $this->output->getOutput());
        $this->assertContains('3.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagLiveShopping');

        $this->assertContains('SwagTicketSystem', $this->output->getOutput());
        $this->assertContains('2.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagTicketSystem');
    }

    /**
     * @throws Exception
     */
    public function testInstallByCode()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'Swag369885808847' => '3.3.0',
                ]
            ]
        ]);

        PluginInstaller::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagDigitalPublishing', $this->output->getOutput());
        $this->assertContains('3.3.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagDigitalPublishing');
    }

    /**
     * @param array $data
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockedEvent(array $data)
    {
        $data = array_merge($data);

        $package = $this->getMockBuilder(\Composer\Package\RootPackage::class)
            ->disableOriginalConstructor()
            ->getMock();


        $package->method('getExtra')
            ->willReturn($data);

        $composer = $this->getMockBuilder(\Composer\Composer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $composer->method('getPackage')
            ->willReturn($package);

        $config = $this->getMockBuilder(\Composer\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $composer->method('getConfig')
            ->willReturn($config);

        $event = $this->getMockBuilder(\Composer\Script\Event::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->method('getComposer')
            ->willReturn($composer);

        $this->output = new \Composer\IO\BufferIO();

        $event->method('getIO')
            ->willReturn($this->output);

        return $event;
    }
}