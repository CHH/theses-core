<?php

namespace theses;

use Stack\Builder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

            $this['dispatcher']->dispatch(Events::ADMIN_BOOT, new event\ApplicationBootEvent($admin));

            return $admin;
        });

        $stack->push(\Stack\UrlMap::class, $map);

        $frontend = \Stack\lazy(function() {
            $frontend = $this['frontend.engine'];
            $frontend->boot();

            $this['dispatcher']->dispatch(Events::FRONTEND_BOOT, new event\ApplicationBootEvent($frontend));

            return $frontend;
        });

        // Load config in its own scope
        call_user_func(function($app) {
            require($this['init_file']);
        }, $this);

        return $stack->resolve($frontend);
    }

    function run()
    {
        $kernel = $this->bootstrap();
        \Stack\run($kernel);

        $this['settings_store']->save();
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
            $admin->extendShared('menu.main', function($menu) use ($name, $options) {
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
            $admin->extendShared('menu.main', function($menu) use ($name, $options) {
                $menu['Settings']['Plugins']->addChild($name, $options);
                return $menu;
            });
            return $admin;
        });

        return $this;
    }

    function getPlugins()
    {
        return $this->plugins;
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

        if ($plugin instanceof EventSubscriberInterface) {
            $this->extendShared('dispatcher', function($dispatcher) use ($plugin) {
                $dispatcher->addSubscriber($plugin);
                return $dispatcher;
            });
        }

        foreach ($parameters as $param => $value) {
            $this[$param] = $value;
        }

        return $this;
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

            foreach ($this->plugins as $plugin) {
                if ($plugin instanceof plugin\SettingsEnabled) {
                    $this->initPluginSettings($admin, $plugin);
                }
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

        $app['system_settings.defaults'] = function () {
            return [
                'siteUrl' => isset($app['site_url']) ? $app['site_url'] : 'http://localhost',
                'permalinkStrategy' => PostRepository::PERMALINK_DATE_TITLE,
            ];
        };

        $app['settings_store'] = $app->share(function () {
            return new FileSettingsManager($this['config_file']);
        });

        $app['system_settings'] = $app->share(function () {
            return $this['settings_factory']('system', $this['system_settings.defaults']);
        });

        $app['settings_factory'] = $app->protect(function ($namespace, array $defaults = []) {
            return new SettingsNamespace($this['settings_store'], $namespace, $defaults);
        });

        $app['posts'] = $app->share(function () use ($app) {
            return new PostRepository(
                $app['phpcr.session'],
                $app['post.factory'],
                $app['dispatcher']
            );
        });

        $this['site'] = $this->share(function () {
            return new Site($this);
        });

        $app['db'] = $app->share(function () {
            $options = [];

            if (isset($_SERVER['DATABASE_URL'])) {
                $options = parse_url($_SERVER['DATABASE_URL']);
                // Compatibility with Heroku Postgres which uses postgres:// schemes
                // in database URLs
                if (@$options['scheme'] === 'postgres') {
                    $driver = 'pdo_pgsql';
                } else {
                    $driver = 'pdo_' . @$options['scheme'];
                }

                $connectionOptions = [
                    'driver' => $driver,
                    'host' => $options['host'],
                    'user' => @$options['user'],
                    'password' => @$options['pass'],
                    'dbname' => trim(@$options['path'], '/'),
                ];
            } else {
                $systemSettings = $this['system_settings'];

                $connectionOptions = [
                    'driver' => $systemSettings->get('databaseDriver'),
                    'host' => $systemSettings->get('databaseHost'),
                    'user' => $systemSettings->get('databaseUser'),
                    'password' => $systemSettings->get('databasePassword'),
                    'dbname' => $systemSettings->get('databaseName'),
                ];
            }

            return \Doctrine\DBAL\DriverManager::getConnection($connectionOptions);
        });

        $app['converter.markdown'] = $app->share(function () {
            return new MarkdownConverter(new \cebe\markdown\GithubMarkdown);
        });

        $app['slugify'] = $app->share(function () {
            return new \Cocur\Slugify\Slugify();
        });

        $app['dispatcher'] = $app->share(function () {
            $dispatcher = new EventDispatcher;
            $this->initCoreEventHandlers($dispatcher);

            return $dispatcher;
        });

        $app['phpcr.workspace'] = 'default';

        $app['phpcr.repository'] = $app->share(function () use ($app) {
            $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
            $repository = $factory->getRepository(['jackalope.doctrine_dbal_connection' => $app['db']]);

            return $repository;
        });

        $app['phpcr.session'] = function () use ($app) {
            return $app['phpcr.repository']->login(new \PHPCR\SimpleCredentials(null, null), $app['phpcr.workspace']);
        };
    }

    private function initCoreEventHandlers($dispatcher)
    {
        $dispatcher->addListener(Events::ADMIN_BOOT, function (event\ApplicationBootEvent $event) {
            $app = $event->getApplication();
            foreach ($this->plugins as $plugin) {
                if ($plugin instanceof plugin\MenuEnabled) {
                    $app->extendShared('menu.main', function ($menu) use ($plugin) {
                        foreach ((array) $plugin->getMainMenuEntries() as $name => $menuEntry) {
                            $menu->addChild($name, $menuEntry);
                        }

                        return $menu;
                    });
                }
            }
        });

        $dispatcher->addSubscriber($this['converter.markdown']);
    }

    private function initPluginSettings(AdminApplication $admin, plugin\Plugin $plugin)
    {
        $info = $plugin::getPluginInfo();
        $pluginSlug = $this['slugify']->slugify($info['name']);
        $pluginRoute = 'plugin_' . $pluginSlug;

        $admin->extendShared('menu.main', function ($menu) use ($info, $pluginRoute) {
            $menu['Settings']['Plugins']->addChild($info['name'], [
                'label' => $info['name'],
                'route' => $pluginRoute,
            ]);

            return $menu;
        });

        $admin->match(
            "/settings/$pluginSlug",
            function (Request $request) use ($admin, $plugin, $pluginSlug, $pluginRoute) {
                $info = $plugin::getPluginInfo();
                $settings = $this['settings_factory']($pluginSlug, $plugin::getSettingsDefaults() ?: []);

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
