<?php

namespace theses;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPCR\Util\NodeHelper;
use iter;

class AdminControllerProvider implements \Silex\ControllerProviderInterface
{
    function connect(\Silex\Application $app)
    {
        $routes = $app['controllers_factory'];

        $routes->get('/', function() use ($app) {
            return $app['twig']->render("index.html");
        })->bind('dashboard');

        $routes->get('/posts', function() use ($app) {
            $posts = $app['posts']->findAll();

            return $app['twig']->render('posts/index.html', [
                'posts' => $posts
            ]);
        })->bind('posts');

        $routes->match('/posts/new', function(Request $req) use ($app) {
            if ($req->isMethod("POST")) {
                $data = $req->get('post');

                $post = $app['posts']->create();
                $post->title = $data['title'];
                $post->rawContent = $data['content'];

                $app['posts']->insert($post);

                return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
            }

            $post = $app['posts']->create();
            return $app['twig']->render('posts/create.html', ['post' => $post]);
        })->bind('posts_create');

        $routes->match('/posts/{slug}/edit', function(Request $req, $slug) use ($app) {
            //$session = $app['shared']['phpcr.session'];
            $post = $app['posts']->findBySlug($slug);;

            if ($req->isMethod('POST')) {
                $data = $req->get('post');

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

                    $post->userProperties = $custom;
                }

                $post->title = $data['title'];
                $post->content = $data['content'];
                $post->slug = (new \Cocur\Slugify\Slugify)->slugify($post->title);

                $app['posts']->update($post);

                return $app->redirect($app->path('posts_edit', ['slug' => $post->getSlug()]));
            }

            return $app['twig']->render('posts/edit.html', ['post' => $post]);
        })->bind('posts_edit');

        $routes->get('/posts/{id}/delete', function($id) use ($app) {
            $session = $app['theses']['phpcr.session'];

            $post = $session->getNodeByIdentifier($id);
            $post->remove();

            $session->save();

            return $app->redirect($app->path('posts'));
        })->bind('posts_delete');

        $routes->get('/settings', function() use ($app) {
            return $app['twig']->render('settings/index.html');
        })->bind('settings');

        $routes->get('/users', function() use ($app) {
            $users = $app['theses']['db']->fetchAll('SELECT * FROM users ORDER BY users.id ASC');

            return $app['twig']->render('users/index.html', [
                'users' => $users
            ]);
        })->bind('users');

        $routes->match('/users/create', function(Request $request) use ($app) {
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

        $routes->match('/users/{id}/edit', function (Request $request, $id) use ($app) {
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

        $routes->get('/users/{id}/delete', function($id) use ($app) {
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
