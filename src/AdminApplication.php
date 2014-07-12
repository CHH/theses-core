<?php

namespace theses;

use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\Matcher\Voter;

/**
 * TODO: Use KnpMenu for menus
 */
class AdminApplication extends \Silex\Application
{
    use \Silex\Application\UrlGeneratorTrait;
    use \Silex\Application\TranslationTrait;
    use \Silex\Application\FormTrait;
    use \Silex\Application\SecurityTrait;

    function __construct(array $values = [])
    {
        parent::__construct();

        $app = $this;

        $app['debug'] = true;

        $app['menu'] = [
            [
                'route' => 'posts',
                'label' => 'Posts',
                'icon' => 'list'
            ],
            [
                'route' => 'settings',
                'label' => 'Settings',
                'icon' => 'cogs'
            ],
        ];

        $app['menu.settings'] = [
            'core' => [
                'label' => 'Core',
                'items' => [
                    [
                        'label' => 'General',
                        'route' => 'settings'
                    ],
                    [
                        'label' => 'Media',
                        'url' => ''
                    ],
                    [
                        'label' => 'Users',
                        'route' => 'users'
                    ]
                ]
            ],
            // Section for plugin settings panels
            'plugins' => [
                'label' => 'Plugins',
                'items' => []
            ]
        ];

        $app['menu.main'] = $app->share(function() use ($app) {
            $menu = $app['knp_menu.factory']->createItem('Root');

            if ($token = $app['security']->getToken()) {
                $user = $token->getUser();
                $menu->addChild('CurrentUser', [
                    'label' => isset($user->nickname) ? $user->nickname : $user->displayName,
                    'route' => 'dashboard',
                    'attributes' => ['class' => 'user'],
                    'extras' => [
                        'user' => $user
                    ],
                ]);
            }

            $menu->addChild('Posts', [
                'label' => 'Posts',
                'route' => 'posts',
                'extras' => ['icon' => 'list'],
            ]);

            $menu->addChild('Settings', [
                'label' => 'Settings',
                'route' => 'settings',
                'extras' => ['icon' => 'cog'],
            ]);

            $menu->addChild('Logout', [
                'label' => "Logout",
                'uri' => '/admin/logout',
                'extras' => ['icon' => 'power-off'],
            ]);

            return $menu;
        });

        $app['menu.settings'] = $app->share(function() use ($app) {
            $menu = $app['knp_menu.factory']->createItem('Settings');

            $core = $menu->addChild('Core', [
                'label' => 'Core',
            ]);

            $core->addChild('General', [
                'label' => 'General',
                'route' => 'settings',
            ]);
            $core->addChild('Media', [
                'label' => 'Media',
                'uri' => '',
            ]);

            $core->addChild('Users', [
                'label' => 'Users',
                'route' => 'users',
            ]);

            $menu->addChild('Plugins', [
                'label' => 'Plugins',
            ]);

            return $menu;
        });

        $app['knp_menu.menus'] = [
            'main' => 'menu.main',
            'settings' => 'menu.settings',
        ];

        $app['knp_menu.matcher.configure'] = $app->protect(function($matcher) use ($app) {
            $routeVoter = new Voter\RouteVoter;
            $routeVoter->setRequest($app['request']);

            $matcher->addVoter($routeVoter);
            $matcher->addVoter(new Voter\UriVoter($app['request']->getRequestUri()));
        });

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider);
        $app->register(new \Silex\Provider\MonologServiceProvider, [
            'monolog.name' => 'theses.admin',
        ]);

        $app['monolog.logfile'] = function() use ($app) {
            return $app['theses']['data_dir'] . '/app.log';
        };

        $app->register(new \Silex\Provider\TwigServiceProvider, [
            'twig.path' => __DIR__ . '/../resources/admin/templates'
        ]);

        $app->register(new \Silex\Provider\SecurityServiceProvider);

        $app['security.firewalls'] = array(
            'login' => array(
                'pattern' => '^/login$',
            ),
            'admin' => array(
                'pattern' => '^/',
                'form' => array('login_path' => '/login', 'check_path' => '/login_check'),
                'users' => function() use ($app) {
                    return new auth\UserProvider($app['theses']['db']);
                },
                    'logout' => ['logout_path' => '/logout']
                ),
            );

        $app['twig'] = $app->share($app->extend('twig', function(\Twig_Environment $twig) {
            $twig->addExtension(new \Twig_Extensions_Extension_Text());
            return $twig;
        }));

        $app['posts'] = $app->share(function() use ($app) {
            return $app['theses']['posts'];
        });

        $app['user.salt'] = $_SERVER['SALT'];

        $app->register(new \Silex\Provider\SessionServiceProvider);
        $app->register(new \Silex\Provider\FormServiceProvider);
        $app->register(new \Silex\Provider\TranslationServiceProvider);
        $app->register(new \Silex\Provider\ValidatorServiceProvider());
        $app->register(new \theses\MarkdownProvider);
        $app->register(new \SilexGravatar\GravatarExtension, [
            'gravatar.options' => [
                'default' => 'retro'
            ]
        ]);

        $app->register(new \Knp\Menu\Integration\Silex\KnpMenuServiceProvider);

        $app->mount('/', new \theses\AdminControllerProvider);

        $app->get('/login', function(Request $request) use ($app) {
            return $app['twig']->render('login.html', array(
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ));
        })->bind('admin_login');

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }
}
