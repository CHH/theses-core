<?php

namespace theses\plugin;

/**
 * A simple demo plugin
 */
class HelloWorldPlugin
    implements Plugin, SettingsEnabled
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

    function register(\theses\Theses $core)
    {
    }
}
