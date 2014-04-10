<?php

namespace theses;

use Symfony\Component\HttpFoundation\Request;

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
                'items' => [
                    // Sample items
                    [
                        'label' => 'S3',
                        'url' => ''
                    ]
                ]
            ]
        ];

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
