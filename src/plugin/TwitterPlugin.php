<?php

namespace theses\plugin;

use theses\Events;
use theses\Theses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Twitter Plugin
 *
 * @plugin twitter
 * @author Christoph Hochstrasser <me@christophh.net>
 * @homepage http://github.com/CHH/theses-core
 */
class TwitterPlugin extends Plugin
{
    function register(Theses $core)
    {
        $settings = $core['settings_factory']('twitter', [
            'enabled' => true
        ]);

        $core->addSettingsMenuEntry('Twitter', ['route' => 'twitter_settings']);

        $core['twitter.app'] = $core->share(function() use ($core, $settings) {
            $config = $core['twitter.config'];

            if ($settings->get('accessToken')) {
                $config['access_token'] = $settings->get('accessToken');
                $config['access_token_secret'] = $settings->get('accessTokenSecret');
            }

            return new \TTools\App($config);
        });

        $core->on(Events::POST_PUBLISH, function($event) use ($core, $settings) {
            if (!$settings->get('enabled')) {
                return;
            }

            $post = $event->getPost();
            $tweetTemplate = $settings->get('tweet');

            $variables = [
                'title' => $post->getTitle(),
                'url' => $core['system_settings']->get('siteUrl') . $post->getUrl()
            ];

            $vars = array_map(
                function($key) { return '{' . $key . '}'; },
                array_keys($variables)
            );

            $tweet = str_replace($vars, array_values($variables), $tweetTemplate);

            $core['twitter.app']->update($tweet);
        });

        $core['admin.engine'] = $core->share(
            $core->extend('admin.engine', function($admin) use ($core, $settings) {
                $admin['twig.loader.filesystem'] = $admin->share(
                    $admin->extend('twig.loader.filesystem', function($loader) {
                        $loader->addPath(__DIR__ . '/resources/twitter', 'twitter');
                        return $loader;
                    })
                );

                $admin['twitter.settings.form'] = $admin->protect(function($data) use ($admin) {
                    return $admin->form($data)
                        ->add('enabled', 'checkbox', ['label' => 'Enable Twitter Sharing'])
                        ->add('tweet', 'textarea', ['attr' => ['rows' => 3]]);
                });

                $admin->get('/settings/twitter', function(Request $request) use ($admin, $core, $settings) {
                    return $admin['twig']->render('@twitter/settings.html', [
                        'twitterUser' => $core['twitter.app']->getCredentials(),
                        'settings' => $admin['twitter.settings.form']($settings->all())->getForm()->createView(),
                    ]);
                })->bind('twitter_settings');

                $admin->post('/settings/twitter/connect', function() use ($admin, $core) {
                    return $admin->redirect($core['twitter.app']->getLoginUrl(
                        'http://localhost:8001/admin/settings/twitter/finish_connect'
                    ));
                })->bind('twitter_connect');

                $admin->post('/settings/twitter/disconnect', function() use ($admin, $core, $settings) {
                    $settings->set([
                        'accessToken' => null,
                        'accessTokenSecret' => null,
                    ]);

                    $core['twitter.app']->logout();

                    return $admin->redirect($admin->path('twitter_settings'));
                })->bind('twitter_disconnect');

                $admin->match('/settings/twitter/finish_connect', function(Request $request) use ($admin, $core, $settings) {
                    $user = $core['twitter.app']->getUser();

                    $settings->set([
                        'accessToken' => $user['access_token'],
                        'accessTokenSecret' => $user['access_token_secret'],
                    ]);

                    return $admin->redirect($admin->path('twitter_settings'));
                });

                $admin->post('/settings/twitter/save', function(Request $request) use ($admin, $core, $settings) {
                    $form = $admin['twitter.settings.form']($settings->all())->getForm();
                    $form->handleRequest($request);

                    $settings->set($form->getData());

                    return $admin->redirect($admin->path('twitter_settings'));
                })->bind('twitter_settings_save');

                return $admin;
            })
        );
    }
}
