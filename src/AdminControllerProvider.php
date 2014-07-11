<?php

namespace theses;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPCR\Util\NodeHelper;
use iter;
use Symfony\Component\Validator\Constraints as Assert;

class AdminControllerProvider implements \Silex\ControllerProviderInterface
{
    function connect(\Silex\Application $app)
    {
        $routes = $app['controllers_factory'];

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

        $routes->get('/posts', function() use ($app) {
            $posts = $app['posts']->findAll();

            return $app['twig']->render('posts/index.html', [
                'posts' => $posts
            ]);
        })->bind('posts');

        $routes->match('/posts/new', function(Request $req) use ($app) {
            $post = $app['posts']->create();

            if ($req->isMethod("POST")) {
                $data = $req->get('post');

                $post->modify([
                    'title' => $data['title'],
                    'content' => $data['content'],
                ]);

                if (isset($data['custom'])) {
                    $custom = [];
                    foreach ($data['custom'] as $prop) {
                        if (!empty($prop['property'])) {
                            $custom[ $prop['property'] ] = $prop['value'];
                        }
                    }

                    $post->modify([
                        'userProperties' => $custom
                    ]);
                }

                if (empty($data['title'])) {
                    $app['session']->getFlashBag()->add('error', 'Post title cannot be empty');
                    goto render;
                }

                $app['posts']->insert($post);

                return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
            }

        render:
            return $app['twig']->render('posts/create.html', ['post' => $post]);
        })->bind('posts_create');

        $routes->match('/posts/{slug}/edit', function(Request $req, $slug) use ($app) {
            $post = $app['posts']->findBySlug($slug);;

            if ($req->isMethod('POST')) {
                $data = $req->get('post');
                $attributes = [];

                if ($req->get('publish') !== null) {
                    $post->publish();
                }

                if ($req->get('unpublish') !== null) {
                    $post->unpublish();
                }

                if (isset($data['custom'])) {
                    $custom = [];
                    foreach ($data['custom'] as $prop) {
                        if (!empty($prop['property'])) {
                            $custom[ $prop['property'] ] = $prop['value'];
                        }
                    }

                    $attributes['userProperties'] = $custom;
                }

                if (empty($data['title'])) {
                    $app['session']->getFlashBag()->add('error', 'Post title cannot be empty');
                    goto redirect;
                }

                $attributes['title'] = $data['title'];
                $attributes['content'] = $data['content'];
                $attributes['slug'] = (new \Cocur\Slugify\Slugify)->slugify($attributes['title']);

                $post->modify($attributes);

                $app['posts']->update($post);

            redirect:
                return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
            }

            return $app['twig']->render('posts/edit.html', [
                'post' => $post
            ]);
        })->bind('posts_edit');

        $routes->get('/posts/{id}/delete', function($id) use ($app) {
            $session = $app['theses']['phpcr.session'];

            $post = $session->getNodeByIdentifier($id);
            $post->remove();

            $session->save();

            return $app->redirect($app->path('posts'));
        })->bind('posts_delete');

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
            $form = $app['form.factory']->createBuilder(new form\UserType)->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $data['password'] = $app['security.encoder.digest']->encodePassword($data['password'], $app['user.salt']);

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
                    $data['password'] = $app['security.encoder.digest']->encodePassword($data['password'], $app['user.salt']);
                }

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
            $upgradeManger = new UpgradeManager;
            $migrations = $upgradeManger->upgradeDatabaseSchema($app['theses']['db']);

            return $app->json(['success' => true, 'migrations' => $migrations]);
        });

        return $routes;
    }
}
