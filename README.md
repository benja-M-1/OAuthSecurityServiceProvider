Silex OAuth authentication provider
===================================

This provider allows you to add an OAuth security authentication provider based on https://github.com/KnpLabs/KnpOAuthBundle to your Silex project.

Installation
============

Use composer :

    ...
    "require": {
        ...
        "benjam1/oauth-security-service-provider": "dev-master"
    }

Then run ```php composer.phar install```

Configuration
=============

KnpOAuthBundle rely on Buzz, so you first have to setup a buzz client:

    $app['buzz.client.factory'] = $app->protect(function ($client) use ($app) {
        return $app->share(function () use ($client, $app) {
            $clients = array(
                'curl'      => '\Buzz\Client\Curl',
                'multicurl' => '\Buzz\Client\MultiCurl',
                'stream'    => '\Buzz\Client\FileGetContent',
            );

            if (false == isset($clients[$client])) {
                throw new \InvalidArgumentException(sprintf('The client "%s" does not exist, curl, multicurl and stream availables.', $client));
            }

            $client = $clients[$client];

            return new $client();
        });
    });

    $this['buzz.client'] = $app['buzz.client.factory']('curl');

You can use the silex Buzz extension of Marc instead: https://github.com/marcw/silex-buzz-extension but I didn't test it.

When Buzz is set up you can configure the security service:


        // app.php

        $app->register(new OAuthSecurityServiceProvider());

        $app['security.firewalls'] = array(
            'front' => array(
                'pattern' => '^.*',
                'oauth' => array(
                    'oauth_provider'    => 'google',
                    'infos_url'         => 'https://www.googleapis.com/oauth2/v1/userinfo',
                    'username_path'     => 'email',
                    'scope'             => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
                    'login_path'        => '/login',
                    'check_path'        => '/login_check',
                    'failure_path'      => '/',
                    'client_id'         => 'yourclientid,
                    'secret'            => 'yourapplicationsecret',
                ),
            ),
        );

Add your user provider (see http://silex.sensiolabs.org/doc/providers/security.html#defining-a-custom-user-provider to do that) and you are done.

More information about Silex: http://silex.sensiolabs.org/. 
