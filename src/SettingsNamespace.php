<?php

namespace theses;

class SettingsNamespace implements SettingsManagerInterface
{
    private $parent;
    private $namespace;
    private $defaults;

    function __construct(SettingsManagerInterface $parent, $namespace, array $defaults = [])
    {
        $this->parent = $parent;
        $this->namespace = $namespace;
        $this->defaults = $defaults;
    }

    private function getSettings()
    {
        return $this->parent->get($this->namespace, new \StdClass);
    }

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

        $this->parent->set($this->namespace, $settings);
    }

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

    function all()
    {
        return (array) $this->getSettings();
    }

    function save()
    {
        $this->parent->save();
    }
}
