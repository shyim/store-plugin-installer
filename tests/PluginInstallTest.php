<?php

use PHPUnit\Framework\TestCase;
use Shyim\ComposerPlugin;

class PluginInstallTest extends TestCase
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

    public function testInstallSinglePlugin()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'SwagLiveshopping' => '3.2.0',
                ],
            ],
        ]);

        ComposerPlugin::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagLiveShopping', $this->output->getOutput());
        $this->assertContains('3.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagLiveShopping');
    }

    public function testInstallMultiplePlugin()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'SwagLiveshopping' => '3.2.0',
                    'SwagTicketSystem' => '2.2.0',
                ],
            ],
        ]);

        ComposerPlugin::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagLiveShopping', $this->output->getOutput());
        $this->assertContains('3.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagLiveShopping');

        $this->assertContains('SwagTicketSystem', $this->output->getOutput());
        $this->assertContains('2.2.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagTicketSystem');
    }

    public function testInstallByCode()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'Swag369885808847' => '3.3.0',
                ],
            ],
        ]);

        ComposerPlugin::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagDigitalPublishing', $this->output->getOutput());
        $this->assertContains('3.3.0', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagDigitalPublishing');
    }

    public function testInstallPluginWithLeftTrialVersion()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'NetiStoreLocator' => '5.3.0',
                ],
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not download plugin NetiStoreLocator in version 5.3.0 maybe not a valid licence for this version');

        ComposerPlugin::installPlugins($event);
    }

    public function testInstallWithConstraint()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'SwagLiveshopping' => '^3',
                ],
            ],
        ]);

        ComposerPlugin::installPlugins($event);

        $this->assertContains('Successfully loggedin in the account', $this->output->getOutput());
        $this->assertContains($this->testHost, $this->output->getOutput());
        $this->assertContains('SwagLiveShopping', $this->output->getOutput());
        $this->assertContains('with version 4', $this->output->getOutput());
        $this->assertFileExists('./custom/plugins/SwagLiveShopping');
    }

    public function testDidYouMean()
    {
        $event = $this->getMockedEvent([
            'plugins' => [
                'production' => [
                    'paypal' => '^3',
                ],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('#Did you mean some#');
        ComposerPlugin::installPlugins($event);
    }

    /**
     * @param array $data
     *
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
