<?php

namespace theses\plugin;

class S3Plugin extends AbstractPlugin
{
    static $info = ['name' => 'S3', 'version' => '0.0.1'];

    static function getSettings($form)
    {
        return $form
            ->add('accessKeyId', 'text', ['label' => 'Access Key ID'])
            ->add('accessKeySecret', 'text', ['label' => 'Access Key Secret'])
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

    static function getMainMenuEntries()
    {
    }
}
