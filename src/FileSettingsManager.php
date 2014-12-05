<?php

namespace theses;

class FileSettingsManager implements SettingsManagerInterface
{
    private $settings;
    private $configFile;
    private $defaults;
    private $namespace;
    private $dirty = false;

    function __construct($configFile, array $defaults = [])
    {
        $this->configFile = $configFile;
        $this->defaults = $defaults;

        if (false === realpath($this->configFile)) {
            $this->settings = new \StdClass;
        }
    }

    private function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = json_decode(file_get_contents($this->configFile)) ?: new \StdClass;
            $this->dirty = false;
        }

        return $this->settings;
    }

    function save()
    {
        if ($this->dirty) {
            file_put_contents($this->configFile, json_encode($this->getSettings(), JSON_PRETTY_PRINT));
            $this->dirty = false;
        }
    }

    /**
     * Set one or many settings
     *
     * @param string|array $spec Setting name as string or an array of settings
     * @param mixed $value
     */
    function set($setting, $value = null)
    {
        $settings = $this->getSettings();

        if (is_array($setting) && $value === null) {
            foreach ($setting as $s => $v) {
                $settings->{$s} = $v;
            }
        } else {
            $settings->{$setting} = $value;
        }

        $this->dirty = true;
    }

    /**
     * Returns all settings as an array of setting-value pairs
     *
     * @return array
     */
    function all()
    {
        $this->getSettings();
        return (array) $this->getSettings();
    }

    /**
     * Safely get a setting with default
     */
    function get($setting, $default = null)
    {
        $settings = $this->getSettings();

        if ($default === null and array_key_exists($setting, $this->defaults)) {
            $default = $this->defaults[$setting];
        }

        if ($this->has($setting)) {
            return $settings->{$setting};
        } else {
            return $default;
        }
    }

    function has($setting)
    {
        $settings = $this->getSettings();
        return isset($settings->{$setting});
    }
}
