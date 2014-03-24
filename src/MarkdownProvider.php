<?php

namespace theses;

use Silex\ServiceProviderInterface;
use Silex\Application;

class MarkdownProvider implements ServiceProviderInterface
{
    function register(Application $app)
    {
        $app['markdown'] = $app->share(function() {
            return new \cebe\markdown\Markdown;
        });

        $app['twig'] = $app->share($app->extend('twig', function(\Twig_Environment $twig) use ($app) {
            $twig->addFilter(new \Twig_SimpleFilter('markdown', function($string) use ($app) {
                return $app['markdown']->parse($string);
            }, ['is_safe' => ['html']]));

            $twig->addFilter(new \Twig_SimpleFilter('inline_markdown', function($string) use ($app) {
                return $app['markdown']->parseParagraph($string);
            }, ['is_safe' => ['html']]));

            return $twig;
        }));
    }

    function boot(Application $app)
    {
    }
}
