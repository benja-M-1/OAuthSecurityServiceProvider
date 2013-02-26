<?php

namespace Benjamin\Provider;

use Silex\Provider\SecurityServiceProvider;
use Silex\Application;
use Knp\Bundle\OAuthBundle\Security\Core\Authentication\Provider\OAuthProvider;
use Knp\Bundle\OAuthBundle\Security\Http\Firewall\OAuthListener;
use Knp\Bundle\OAuthBundle\Security\Http\EntryPoint\OAuthEntryPoint;

/**
 * OAuthSecurityServiceProvider class.
 *
 * @author Benjamin Grandfond <benjamin.grandfond@gmail.com>
 */
class OAuthSecurityServiceProvider extends SecurityServiceProvider
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        parent::register($app);

        $that = $this;

        $app['security.authentication_listener.factory.oauth'] = $app->protect(function ($name, $options) use ($app) {
            if (!isset($app['security.oauth.provider'])) {
                $app['security.oauth.provider'] = $app['security.oauth.provider.factory']($options);
            }

            if (!isset($app['security.entry_point.'.$name.'.oauth'])) {
                $app['security.entry_point.'.$name.'.oauth'] = $app['security.entry_point.oauth._proto']($name, $options);
            }

            if (!isset($app['security.authentication_listener.'.$name.'.oauth'])) {
                $app['security.authentication_listener.'.$name.'.oauth'] = $app['security.authentication_listener.oauth._proto']($name, $options);
            }

            if (!isset($app['security.authentication_provider.'.$name])) {
                $app['security.authentication_provider.'.$name] = $app['security.authentication_provider.oauth._proto']($name);
            }

            return array(
                'security.authentication_provider.'.$name,
                'security.authentication_listener.'.$name.'.oauth',
                'security.entry_point.'.$name.'.oauth',
                'form'
            );
        });

        $app['security.entry_point.oauth._proto'] = $app->protect(function($name, $options) use ($app) {
            return $app->share(function () use ($app, $name, $options) {
                $loginPath = isset($options['login_path']) ? $options['login_path'] : '/login';
                $checkPath = isset($options['check_path']) ? $options['check_path'] : '/login_check';

                return new OAuthEntryPoint(
                    $app['security.http_utils'],
                    $app['security.oauth.provider'],
                    $checkPath,
                    $loginPath
                );
            });
        });

        $app['security.oauth.provider.factory'] = $app->protect(function($options) use ($app) {
            $classes = array(
                'oauth'    => 'Knp\\Bundle\\OAuthBundle\\Security\\Http\\OAuth\\OAuthProvider',
                'facebook' => 'Knp\\Bundle\\OAuthBundle\\Security\\Http\\OAuth\\FacebookProvider',
                'github'   => 'Knp\\Bundle\\OAuthBundle\\Security\\Http\\OAuth\\GithubProvider',
                'google'   => 'Knp\\Bundle\\OAuthBundle\\Security\\Http\\OAuth\\GoogleProvider',
            );

            $type = isset($options['oauth_provider']) ? $options['oauth_provider'] : 'oauth';

            if (false == isset($classes[$type])) {
                throw new \InvalidArgumentException(sprintf('The provider "%s" does not exist.', $type));
            }

            $class = $classes[$type];

            return $app->share(function () use ($app, $class, $options) {
                return new $class(
                    $app['buzz.client'],
                    $app['security.http_utils'],
                    $options
                );
            });
        });

        $app['security.authentication_listener.oauth._proto'] = $app->protect(function ($name, $options) use ($app, $that) {
            return $app->share(function () use ($app, $name, $options, $that) {
                $that->addFakeRoute('match', $tmp = isset($options['check_path']) ? $options['check_path'] : '/login_check', str_replace('/', '_', ltrim($tmp, '/')));

                if (!isset($app['security.authentication.success_handler.'.$name])) {
                    $app['security.authentication.success_handler.'.$name] = $app['security.authentication.success_handler._proto']($name, $options);
                }

                if (!isset($app['security.authentication.failure_handler.'.$name])) {
                    $app['security.authentication.failure_handler.'.$name] = $app['security.authentication.failure_handler._proto']($name, $options);
                }

                $listener = new OAuthListener(
                    $app['security'],
                    $app['security.authentication_manager'],
                    $app['security.session_strategy'],
                    $app['security.http_utils'],
                    $name,
                    $app['security.authentication.success_handler.'.$name],
                    $app['security.authentication.failure_handler.'.$name],
                    $options,
                    $app['logger'],
                    $app['dispatcher']
                );

                $listener->setOAuthProvider($app['security.oauth.provider']);

                return $listener;
            });
        });

        $app['security.authentication_provider.oauth._proto'] = $app->protect(function ($name) use ($app) {
            return $app->share(function() use ($app, $name) {
                return new OAuthProvider(
                    $app['security.user_provider.'.$name],
                    $app['security.oauth.provider']
                );
            });

        });
    }
}
