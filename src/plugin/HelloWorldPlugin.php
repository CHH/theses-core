<?php

namespace theses\plugin;

class HelloWorldPlugin implements PluginInterface
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

    static function getSettings($builder)
    {
        return $builder
            ->add('foo', 'text')
            ->add('enabled', 'checkbox')
            ;
    }

    function register(\theses\Theses $core)
    {
    }
}
