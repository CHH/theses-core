<?php

namespace theses\plugin;

class S3Plugin
    implements Plugin, SettingsEnabled
{
    static function getPluginInfo()
    {
        return [
            'name' => 'S3'
        ];
    }

    function register(\theses\Theses $core)
    {
    }

    static function getSettings($form)
    {
        return $form
            ->add('bucket', 'text', [
                'attr' => [
                    'placeholder' => 'e.g. www.my-website.com'
                ]
            ])
        ;
    }

    static function getSettingsDefaults()
    {
        return [];
    }
}
