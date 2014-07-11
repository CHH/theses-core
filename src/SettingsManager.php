<?php

namespace theses;

use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;

class SettingsManager
{
    protected $session;
    protected $namespace;
    protected $defaults;

    function __construct(SessionInterface $session, $namespace, array $defaults = [])
    {
        $this->session = $session;
        $this->namespace = $namespace;
        $this->defaults = $defaults;
    }

    private function getNode()
    {
        return NodeHelper::createPath($this->session, '/theses/settings/' . $this->namespace);
    }

    /**
     * Set one or many settings
     *
     * @param string|array $spec Setting name as string or an array of settings
     * @param mixed $value
     */
    function set($spec, $value = null)
    {
        $node = $this->getNode();

        if (is_array($spec)) {
            foreach ($spec as $option => $value) {
                $node->setProperty($option, $value);
            }
        } else {
            $node->setProperty($spec, $value);
        }

        $this->session->save();
    }

    /**
     * Returns all settings as an array of setting-value pairs
     *
     * @return array
     */
    function all()
    {
        $properties = $this->getNode()->getPropertiesValues();

        $allowedPropertyNames = array_filter(array_keys($properties), function($prop) {
            return strpos($prop, 'jcr:', 0) === false;
        });

        return array_intersect_key($properties, array_flip($allowedPropertyNames)) + $this->defaults;
    }

    /**
     * Safely get a setting with default
     */
    function get($option, $default = null)
    {
        $options = $this->getNode();

        if ($default === null and array_key_exists($option, $this->defaults)) {
            $default = $this->defaults[$option];
        }

        return $options->getPropertyValueWithDefault($option, $default);
    }
}
