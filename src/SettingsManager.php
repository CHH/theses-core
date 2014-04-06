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

        return $this;
    }

    function all()
    {
        return $this->getNode()->getPropertiesValues() + $this->defaults;
    }

    function get($option, $default = null)
    {
        $options = $this->getNode();

        if ($default === null and array_key_exists($option, $this->defaults)) {
            $default = $this->defaults[$option];
        }

        return $options->getPropertyValueWithDefault($option, $default);
    }
}
