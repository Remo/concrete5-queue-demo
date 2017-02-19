<?php
namespace Concrete\Package\QueueDemo;

use Concrete\Core\Support\Facade\Facade;
use Concrete\Package\QueueDemo\Src\Console\Command\ProcessNotifications;
use Package;

class Controller extends Package
{
    protected $pkgHandle = 'queue_demo';
    protected $appVersionRequired = '5.8';
    protected $pkgVersion = '1.0';

    public function getPackageName()
    {
        return t('Queue Demo');
    }

    public function getPackageDescription()
    {
        return t('Installs the Queue Demo Package');
    }

    /**
     * Executed for every request, required to register common assets, commands, event and libraries.
     */
    public function on_start()
    {
        $this->registerCommands();
    }

    /**
     * Registers our console commands when executed on the console
     */
    protected function registerCommands()
    {
        $app = Facade::getFacadeApplication();
        if ($app->isRunThroughCommandLineInterface()) {
            $console = $app->make('console');
            $console->add(new ProcessNotifications());
        }
    }
}