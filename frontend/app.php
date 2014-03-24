<?php

$app = new theses\FrontendApplication;

$app['debug'] = true;
$app['template.root'] = __DIR__ .'/../../';

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html');
});

return $app;
