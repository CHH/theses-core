<?php

namespace theses\plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPlugin
    implements Plugin, SettingsEnabled, MenuEnabled, EventSubscriberInterface
{
    static $info;

    static function getSubscribedEvents()
    {
        return [];
    }

    static function getPluginInfo()
    {
        return static::$info;
    }

    static function getSettingsDefaults()
    {
        return [];
    }

    static function getSettings($form)
    {
        return $form;
    }

    static function getMainMenuEntries()
    {
        return [];
    }

    function register(\theses\Theses $core)
    {
    }
}
