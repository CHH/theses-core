<?php

namespace theses\plugin;

use Symfony\Component\Form\FormBuilderInterface;
use theses\AdminApplication;
use theses\Theses;

abstract class Plugin implements PluginInterface
{
    private $core;
    private $settings;

    function register(Theses $core)
    {
        $this->core = $core;
    }

    function getSettingsPanel(FormBuilderInterface $builder)
    {
    }

    function defineAdminRoutes(AdminApplication $app)
    {
    }

    function core()
    {
        return $this->core;
    }

    function settings()
    {
        if (null === $this->settings) {
            $this->settings = $this->core['settings_factory']($this->getMetadata()['name']);
        }

        return $this->settings;
    }

    private function getMetadata()
    {
    }
}
