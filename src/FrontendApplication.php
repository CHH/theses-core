<?php

namespace theses;

use theses\PostRepository;
use Silex\Provider;

class FrontendApplication extends \Silex\Application
{
    function __construct()
    {
        parent::__construct();

        $this->register(new Provider\TwigServiceProvider);
        $this['twig.path'] = $this->share(function() {
            return $this['template.root'];
        });

        $this['posts'] = $this->share(function() {
            return new PostRepository($this['shared']['phpcr.session']);
        });

        $this['twig'] = $this->share($this->extend('twig', function($twig) {
            $twig->addGlobal('posts', $this['posts']);
            return $twig;
        }));
    }
}
