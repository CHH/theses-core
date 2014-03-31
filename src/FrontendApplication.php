<?php

namespace theses;

use theses\PostRepository;
use Silex\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FrontendApplication extends \Silex\Application
{
    use \Silex\Application\UrlGeneratorTrait;

    function __construct(array $values = [])
    {
        parent::__construct();

        $this->register(new Provider\MonologServiceProvider, [
            'monolog.logfile' => $this->share(function() {
                return $this['theses']['data_dir'] . '/app.log';
            }),
            'monolog.name' => 'theses',
        ]);

        $this->register(new Provider\UrlGeneratorServiceProvider);

        $this->register(new Provider\TwigServiceProvider);
        $this['twig.path'] = $this->share(function() {
            return $this['theses']['root_dir'];
        });

        $this['site'] = function() {
            return $this['theses']['site'];
        };

        $this['twig'] = $this->share($this->extend('twig', function($twig) {
            $twig->addGlobal('site', $this['site']);
            return $twig;
        }));

        $this->error(function(\Exception $e) {
            if (!$e instanceof HttpException) {
                return;
            }

            $code = $e->getStatusCode();

            try {
                return new Response($this['twig']->render("_error/$code.html"), $code);
            } catch (\Twig_Error_Loader $e) {}
        });

        $this->before(function(Request $request) {
            try {
                $post = $this['theses']['posts']->findByPermalink(trim($request->getRequestUri(), '/'));

                return new Response(
                    $this['twig']->render('_layouts/post.html', ['page' => $post])
                );
            } catch (\PHPCR\RepositoryException $e) {
            }
        }, static::EARLY_EVENT);

        $this->before(function(Request $request) {
            $template = $request->getRequestUri();

            if (substr($template, -1, 1) === '/') {
                $template .= 'index.html';
            } else {
                $template .= '.html';
            }

            try {
                return new Response($this['twig']->render($template));
            } catch (\Twig_Error_Loader $e) {}
        }, static::EARLY_EVENT);

        $this->get('/post/{slug}', function($slug) {
            $post = $this['theses']['posts']->findBySlug($slug);
            return $this['twig']->render('_layouts/post.html', ['page' => $post]);
        })->bind('post');

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }
}
