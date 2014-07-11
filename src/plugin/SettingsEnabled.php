<?php

namespace theses\plugin;

interface SettingsEnabled
{
    static function getSettingsDefaults();
    static function getSettings($form);
}
