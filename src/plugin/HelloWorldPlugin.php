<?php

namespace theses\plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use theses\Events;

/**
 * A simple demo plugin
 */
class HelloWorldPlugin
    implements Plugin, SettingsEnabled, EventSubscriberInterface
{
    static function getPluginInfo()
    {
        return [
            'name' => 'Hello World',
            'version' => '0.42.0',
            'author' => [
                'name' => 'Christoph Hochstrasser',
                'email' => 'hello@christophh.net',
                'homepage' => 'http://christophh.net',
            ]
        ];
    }

    static function getSettingsDefaults()
    {
        return [
            'enabled' => false
        ];
    }

    static function getSettings($form)
    {
        return $form
            ->add('enabled', 'checkbox')
            ->add('foo', 'text')
            ;
    }

    static function getSubscribedEvents()
    {
        return [
            Events::POST_AFTER_SAVE => 'onSave'
        ];
    }

    function onSave($event)
    {
    }

    function register(\theses\Theses $core)
    {
    }
}
