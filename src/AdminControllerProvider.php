<?php

namespace theses;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPCR\Util\NodeHelper;
use iter;
use Symfony\Component\Validator\Constraints as Assert;
use theses\User;

class AdminControllerProvider implements \Silex\ControllerProviderInterface
{
    function connect(\Silex\Application $app)
    {
        $routes = $app['controllers_factory'];

        $app->get('/login', function (Request $request) use ($app) {
            return $app['twig']->render('login.html', [
                'error'         => $app['security.last_error']($request),
                'last_username' => $app['session']->get('_security.last_username'),
            ]);
        })->bind('admin_login');

        $routes->get('/', function() use ($app) {
            $stream = [
                [
                    'type' => 'note',
                    'title' => 'Lean PHP',
                    'excerpt' => "Lorem ipsum",
                    'icon' => 'comment-o'
                ],
                [
                    'type' => 'link',
                    'href' => 'http://xkcd.com',
                    'title' => 'Most awesome comic site EVER',
                    'description' => '',
                    'icon' => 'share-square-o'
                ],
                [
                    'type' => 'image',
                    'image' => 'http://lorempixel.com/1920/1080/cats',
                    'description' => 'So cute ;)',
                    'icon' => 'picture-o'
                ]
            ];

            return $app['twig']->render("index.html", compact('stream'));
        })->bind('dashboard');

        $routes->get('/settings', function() use ($app) {
            $options = $app['theses']['system_settings']->all();

            $form = $app['form.factory']->createBuilder(new form\SystemSettingsType, $options)->getForm();

            return $app['twig']->render('settings/index.html', [
                'form' => $form->createView()
            ]);
        })->bind('settings');

        $routes->post('/settings/update', function(Request $request) use ($app) {
            $form = $app['form.factory']->createBuilder(new form\SystemSettingsType)->getForm();
            $form->handleRequest($request);

            $app['theses']['system_settings']->set($form->getData());

            return $app->redirect($app->path('settings'));
        })->bind('settings_update');

        $routes->get('/settings/users', function() use ($app) {
            $users = $app['theses']['db']->fetchAll('SELECT * FROM users ORDER BY users.id ASC');

            return $app['twig']->render('users/index.html', [
                'users' => $users
            ]);
        })->bind('users');

        $routes->match('/settings/users/create', function(Request $request) use ($app) {
            $defaults = ['enabled' => true];
            $form = $app['form.factory']->createBuilder(new form\UserType, $defaults)->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $user = User::fromAttributes($data);
                $encoder = $app['security.encoder_factory']->getEncoder(get_class($user));
                $data['password'] = $encoder->encodePassword($data['password'], $user->getSalt());

                $app['theses']['db']->insert('users', $data);
                return $app->redirect($app->path('users'));
            }

            return $app['twig']->render('users/create.html', ['form' => $form->createView()]);
        })->bind('user_create');

        $routes->match('/settings/users/{id}/edit', function (Request $request, $id) use ($app) {
            $user = $app['theses']['db']->fetchAssoc('select * from users where users.id=:id', [':id' => $id]);
            $form = $app['form.factory']->createBuilder(new form\UserType, $user)->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if (empty($data['password'])) {
                    unset($data['password']);
                } else {
                    $user = User::fromAttributes($data);
                    $encoder = $app['security.encoder_factory']->getEncoder(get_class($user));
                    $data['password'] = $encoder->encodePassword($data['password'], $user->getSalt());
                }

                $data['enabled'] = $data['enabled'] ? 'true' : 'false';

                $app['theses']['db']->update('users', $data, ['id' => $id]);
                return $app->redirect($app->path('user_edit', ['id' => $id]));
            }

            return $app['twig']->render('users/edit.html', ['user' => $user, 'form' => $form->createView()]);
        })->bind('user_edit');

        $routes->get('/settings/users/{id}/delete', function($id) use ($app) {
            $app['theses']['db']->delete('users', ['id' => $id]);
            return $app->redirect($app->path('users'));
        })->bind('user_delete');

        $routes->get('/updater/upgrade', function() use ($app) {
            $upgradeManager = new UpgradeManager;
            $migrations = $upgradeManager->upgradeDatabaseSchema($app['theses']['db']);

            return $app->json(['success' => true, 'migrations' => $migrations]);
        });

        return $routes;
    }
}
