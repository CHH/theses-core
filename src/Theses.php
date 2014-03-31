<?php

namespace theses;

use Stack\Builder;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Theses extends \Pimple
{
    protected $plugins = [];

    function __construct(array $values = [])
    {
        $this->initSharedServices();

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    function bootstrap()
    {
        $stack = new Builder;

        $map = [];
        $map['/admin'] = \Stack\lazy(function() {
            $admin = $this['admin.engine'];
            $admin->boot();

            return $admin;
        });

        $stack->push(\Stack\UrlMap::class, $map);

        $frontend = \Stack\lazy(function() {
            $frontend = $this['frontend.engine'];

            // Load config in its own scope
            call_user_func(function($app) {
                require($this['config_file']);
            }, $this);

            $frontend->boot();

            return $frontend;
        });

        $this->bootPlugins();

        return $stack->resolve($frontend);
    }

    function run()
    {
        \Stack\run($this->bootstrap());
    }

    function usePlugin(plugin\Plugin $plugin)
    {
        $this->plugins[] = $plugin;
        return $this;
    }

    function bootPlugins()
    {
    }

    private function initSharedServices()
    {
        $app = $this;

        $app['frontend.engine'] = $app->share(function() {
            $frontend = new FrontendApplication;
            $frontend['theses'] = $frontend->share(function() {
                return $this;
            });
            return $frontend;
        });

        $app['admin.engine'] = $app->share(function() {
            $admin = new AdminApplication;
            $admin['theses'] = $admin->share(function() {
                return $this;
            });

            return $admin;
        });

        $app['post.route'] = 'post';

        $app['post.url_generator'] = $app->protect(function(Post $post) {
            return $this['frontend.engine']->path($this['post.route'], ['slug' => $post->getSlug()]);
        });

        $app['post.factory'] = $app->protect(function() {
            return new Post($this['dispatcher'], $this['post.url_generator']);
        });

        $app['posts'] = $app->share(function() use ($app) {
            return new PostRepository(
                $app['phpcr.session'],
                $app['post.factory']
            );
        });

        $this['site'] = $this->share(function() {
            return new Site($this, $this['posts']);
        });

        $app['db'] = $app->share(function() {
            $options = parse_url($_SERVER['DATABASE_URL']);

            if ($options['scheme'] === 'postgres') {
                $driver = 'pdo_pgsql';
            } else {
                $driver = 'pdo_' . $options['scheme'];
            }

            return \Doctrine\DBAL\DriverManager::getConnection(array(
                'driver'    => $driver,
                'host'      => $options['host'],
                'user'      => @$options['user'],
                'password'  => @$options['pass'],
                'dbname'    => trim($options['path'], '/'),
            ));
        });

        $app['converter.markdown'] = $app->share(function() {
            return new MarkdownConverter(new \cebe\markdown\GithubMarkdown);
        });

        $app['dispatcher'] = $app->share(function() {
            $dispatcher = new EventDispatcher;
            $dispatcher->addSubscriber($this['converter.markdown']);

            return $dispatcher;
        });

        $app['phpcr.workspace'] = 'default';

        $app['phpcr.repository'] = $app->share(function () use ($app) {
            $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
            $repository = $factory->getRepository(array('jackalope.doctrine_dbal_connection' => $app['db']));

            return $repository;
        });

        $app['phpcr.session'] = function () use ($app) {
            return $app['phpcr.repository']->login(new \PHPCR\SimpleCredentials(null, null), $app['phpcr.workspace']);
        };
    }
}
