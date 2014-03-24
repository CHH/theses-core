<?php

namespace theses;

use Stack\Builder;

class Theses extends \Pimple
{
    protected $engines = [];
    protected $plugins = [];

    function __construct()
    {
        $this->initSharedServices();
    }

    function bootstrap()
    {
        $stack = new Builder;

        $map = $this->engines;
        $map['/admin'] = \Stack\lazy(function() use ($appRoot, $shared) {
            $admin = new AdminApplication;
            $admin['theses'] = $admin->share(function() {
                return $this;
            });

            $admin->boot();

            return $admin;
        });

        $stack->push(Stack\UrlMap::class, $map);

        $frontend = new FrontendApplication;
        $frontend['theses'] = $frontend->share(function() {
            return $this;
        });
        $frontend->boot();

        return $stack->resolve($frontend);
    }

    function usePlugin(plugin\Plugin $plugin)
    {
        $this->plugins[] = $plugin;
        return $this;
    }

    function addEngine(\Silex\Application $app, $path)
    {
        $app['theses'] = $this;
        $this->engines[$path] = $app;

        return $this;
    }

    private function initSharedServices()
    {
        $app = $this;

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
