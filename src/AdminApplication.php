<?php

namespace theses;

class AdminApplication extends \Silex\Application
{
    use \Silex\Application\UrlGeneratorTrait;
    use \Silex\Application\TranslationTrait;
    use \Silex\Application\FormTrait;
    use \Silex\Application\SecurityTrait;

    function __construct()
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
            [
                'url' => '/admin/logout',
                'label' => 'Logout',
                'icon' => 'power-off'
            ]
        ];

        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider);
        $app->register(new \Silex\Provider\MonologServiceProvider, [
            'monolog.name' => 'admin',
            'monolog.logfile' => __DIR__ . '/../data/admin.log',
        ]);

        $app->register(new \Silex\Provider\TwigServiceProvider, [
            'twig.path' => __DIR__ . '/templates'
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
                    return new theses\auth\UserProvider($app['shared']['db']);
                },
                    'logout' => ['logout_path' => '/logout']
                ),
            );

        $app['twig'] = $app->share($app->extend('twig', function(\Twig_Environment $twig) {
            $twig->addExtension(new \Twig_Extensions_Extension_Text());
            return $twig;
        }));

        $app['posts'] = $app->share(function() use ($app) {
            return new \theses\PostRepository($app['shared']['phpcr.session']);
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
    }
}
