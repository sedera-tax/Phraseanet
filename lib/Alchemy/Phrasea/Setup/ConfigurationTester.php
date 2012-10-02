<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Probe\Probe31;
use Alchemy\Phrasea\Setup\Version\Probe\Probe35;

class ConfigurationTester
{
    private $app;
    private $probes;
    private $versionProbes;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->versionProbes = array(
            new Probe31($this->app),
            new Probe35($this->app),
        );

    }

    public function registerProbe(ProbeInterface $probe)
    {
        $this->probes[] = $probe;
    }

    /**
     * Return true if got the latest configuration file.
     *
     * @return type
     */
    public function isInstalled()
    {
        return file_exists(__DIR__ . '/../../../../config/config.yml')
            && file_exists(__DIR__ . '/../../../../config/connexions.yml')
            && file_exists(__DIR__ . '/../../../../config/services.yml');
    }

    /**
     *
     */
    public function isUpToDate()
    {
        return $this->isInstalled() && !$this->isUpgradable();
    }

    /**
     *
     */
    public function isBlank()
    {
        return !$this->isMigrable() && !$this->isUpgradable() && !$this->isInstalled();
    }

    /**
     *
     * @return boolean
     */
    public function isUpgradable()
    {
        $upgradable = version_compare($this->app['phraseanet.appbox']->get_version(), $this->app['phraseanet.version'], "<");

        if (!$upgradable) {
            foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                if (version_compare($databox->get_version(), $this->app['phraseanet.version'], "<")) {
                    $upgradable = true;
                    break;
                }
            }
        }

        return $upgradable;
    }

    /**
     *
     * @return type
     */
    public function isMigrable()
    {
        return (Boolean) $this->getMigrations();
    }

    public function getMigrations()
    {
        $migrations = array();

        foreach($this->versionProbes as $probe) {
            if($probe->isMigrable()) {
                $migrations[] = $probe->getMigration();
            }
        }

        return $migrations;
    }
}
