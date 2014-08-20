<?php

namespace theses;

use Stack\Builder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class Theses extends \Pimple
{
    use ContainerUtilitiesTrait;

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
            $frontend->boot();

            return $frontend;
        });

        // Load config in its own scope
        call_user_func(function($app) {
            require($this['config_file']);
        }, $this);

        $this->bootPlugins();

        return $stack->resolve($frontend);
    }

    function run()
    {
        \Stack\run($this->bootstrap());
    }

    function on($event, $listener)
    {
        $this['dispatcher']->addListener($event, $listener);

        return $this;
    }

    function getSiteUrl()
    {
        return $this['system_settings']->get('siteUrl');
    }

    function addAdminMenuEntry($name, array $options = [])
    {
        if (isset($options['icon'])) {
            $options['extras']['icon'] = $options['icon'];
        }

        $this->extendShared('admin.engine', function($admin) use ($name, $options) {
            $admin->extendShared('menu.main', function($menu) use ($name, $options) {
                $menu->addChild($name, $options);
                return $menu;
            });
            return $admin;
        });

        return $this;
    }

    function addSettingsMenuEntry($name, array $options = [])
    {
        $this->extendShared('admin.engine', function($admin) use ($name, $options) {
            $admin->extendShared('menu.settings', function($menu) use ($name, $options) {
                $menu['Settings']->addChild($name, $options);
                return $menu;
            });
            return $admin;
        });

        return $this;
    }

    function addPluginSettingsMenuEntry($name, array $options = [])
    {
        $this->extendShared('admin.engine', function($admin) use ($name, $options) {
            $admin->extendShared('menu.settings', function($menu) use ($name, $options) {
                $menu['Plugins']->addChild($name, $options);
                return $menu;
            });
            return $admin;
        });

        return $this;
    }

    function usePlugin(plugin\Plugin $plugin, array $parameters = [])
    {
        $this->plugins[] = $plugin;

        $plugin->register($this);

        $rc = new \ReflectionClass($plugin);
        $viewsPath = dirname($rc->getFilename()) . '/Resources/views';

        if (is_dir($viewsPath)) {
            $this->extendShared('admin.engine', function($admin) use ($rc, $viewsPath) {
                $admin->extendShared('twig.loader.filesystem', function($loader) use ($rc, $viewsPath) {
                    $loader->addPath($viewsPath, $rc->getShortName());
                    return $loader;
                });
                return $admin;
            });
        }

        foreach ($parameters as $param => $value) {
            $this[$param] = $value;
        }

        return $this;
    }

    function bootPlugins()
    {
        foreach ($this->plugins as $plugin) {
        }
    }

    private function initSharedServices()
    {
        $app = $this;

        $app['frontend.engine'] = $app->share(function () {
            $frontend = new FrontendApplication;
            $frontend['theses'] = $frontend->share(function () {
                return $this;
            });
            return $frontend;
        });

        $app['admin.engine'] = $app->share(function () {
            $admin = new AdminApplication;
            $admin['theses'] = $admin->share(function () {
                return $this;
            });

            $plugins = array_filter($this->plugins, function ($plugin) {
                return $plugin instanceof plugin\SettingsEnabled;
            });

            foreach ($plugins as $plugin) {
                $this->initPluginSettings($admin, $plugin);
            }

            return $admin;
        });

        $app['post.route'] = 'post';

        $app['post.url_generator'] = $app->protect(function (Post $post) {
            return $this['frontend.engine']->path($this['post.route'], ['slug' => $post->getSlug()]);
        });

        $app['post.factory'] = $app->protect(function (array $attributes = []) {
            return new Post($attributes, $this['dispatcher'], $this['post.url_generator']);
        });

        $app['system_settings.defaults'] = [
            'siteUrl' => 'http://localhost',
            'permalinkStrategy' => PostRepository::PERMALINK_DATE_TITLE,
        ];

        $app['system_settings'] = $app->share(function () {
            return $this['settings_factory']('system', $this['system_settings.defaults']);
        });

        $app['settings_factory'] = $app->protect(function ($namespace, array $defaults = []) {
            return new SettingsManager($this['phpcr.session'], $namespace, $defaults);
        });

        $app['posts'] = $app->share(function () use ($app) {
            return new PostRepository(
                $app['phpcr.session'],
                $app['post.factory'],
                $app['dispatcher']
            );
        });

        $this['site'] = $this->share(function () {
            return new Site($this, $this['posts']);
        });

        $app['db'] = $app->share(function () {
            $options = parse_url($_SERVER['DATABASE_URL']);

            // Compatibility with Heroku Postgres which uses postgres:// schemes
            // in database URLs
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

        $app['converter.markdown'] = $app->share(function () {
            return new MarkdownConverter(new \cebe\markdown\GithubMarkdown);
        });

        $app['dispatcher'] = $app->share(function () {
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

    private function initPluginSettings(AdminApplication $admin, plugin\Plugin $plugin)
    {
        $info = $plugin::getPluginInfo();
        $pluginSlug = (new \Cocur\Slugify\Slugify)->slugify($info['name']);
        $pluginRoute = 'plugin_' . $pluginSlug;

        $admin->extendShared('menu.settings', function ($menu) use ($info, $pluginRoute) {
            $menu['Plugins']->addChild($info['name'], [
                'label' => $info['name'],
                'route' => $pluginRoute,
            ]);

            return $menu;
        });

        $admin->match(
            "/settings/$pluginSlug",
            function (Request $request) use ($admin, $plugin, $pluginSlug, $pluginRoute) {
                $info = $plugin::getPluginInfo();
                $settings = $this['settings_factory']($info['name'], $plugin::getSettingsDefaults() ?: []);

                $settingsForm = $plugin::getSettings($admin->form($settings->all()))->getForm();
                $settingsForm->handleRequest($request);

                if ($settingsForm->isValid()) {
                    $data = $settingsForm->getData();
                    $settings->set($data);

                    return $admin->redirect($admin->path($pluginRoute));
                }

                return $admin['twig']->render('plugin_settings_pane.html', [
                    'pluginSlug' => $pluginSlug,
                    'form' => $settingsForm->createView(),
                    'pluginInfo' => $info,
                    'pluginRoute' => $pluginRoute,
                ]);
            }
        )->bind($pluginRoute);
    }
}
