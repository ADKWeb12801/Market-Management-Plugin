<?php
defined('ABSPATH') || exit;

class GFFM_OAuth {
    public static function init() {
        add_action('init', [__CLASS__, 'route']);
    }

    private static function redirect_uri(): string {
        return home_url('/vendor-portal/');
    }

    public static function route() {
        if ( empty($_GET['gffm_oauth']) ) {
            return;
        }
        $provider = sanitize_text_field(wp_unslash($_GET['gffm_oauth']));
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        if ( ! $state || ! get_transient('gffm_oauth_state_' . $state) ) {
            return;
        }
        if ( ! isset($_GET['code']) ) {
            $client_id = get_option('gffm_auth_' . $provider . '_client_id');
            if ( ! $client_id ) {
                return;
            }
            $redirect = add_query_arg('gffm_oauth', $provider, self::redirect_uri());
            if ( 'google' === $provider ) {
                $url = add_query_arg([
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect,
                    'response_type' => 'code',
                    'scope' => 'openid email profile',
                    'state' => $state,
                ], 'https://accounts.google.com/o/oauth2/v2/auth');
            } elseif ( 'facebook' === $provider ) {
                $url = add_query_arg([
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect,
                    'response_type' => 'code',
                    'scope' => 'email,public_profile',
                    'state' => $state,
                ], 'https://www.facebook.com/v20.0/dialog/oauth');
            } else {
                return;
            }
            wp_redirect($url);
            exit;
        }
        delete_transient('gffm_oauth_state_' . $state);
        $code = sanitize_text_field(wp_unslash($_GET['code']));
        if ( 'google' === $provider ) {
            self::handle_google($code);
        } elseif ( 'facebook' === $provider ) {
            self::handle_facebook($code);
        }
    }

    private static function handle_google(string $code) {
        $client_id = get_option('gffm_auth_google_client_id');
        $client_secret = get_option('gffm_auth_google_client_secret');
        if ( ! $client_id || ! $client_secret ) {
            return;
        }
        $redirect = add_query_arg('gffm_oauth', 'google', self::redirect_uri());
        $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code' => $code,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $redirect,
                'grant_type' => 'authorization_code',
            ],
        ]);
        if ( is_wp_error($resp) ) {
            return;
        }
        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if ( empty($body['id_token']) ) {
            return;
        }
        $parts = explode('.', $body['id_token']);
        if ( count($parts) < 2 ) {
            return;
        }
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if ( ! $payload || ($payload['aud'] ?? '') !== $client_id ) {
            return;
        }
        if ( empty($payload['email']) || empty($payload['email_verified']) ) {
            return;
        }
        self::login_or_create($payload['email'], $payload['name'] ?? '');
        wp_safe_redirect(self::redirect_uri());
        exit;
    }

    private static function handle_facebook(string $code) {
        $client_id = get_option('gffm_auth_facebook_app_id');
        $client_secret = get_option('gffm_auth_facebook_app_secret');
        if ( ! $client_id || ! $client_secret ) {
            return;
        }
        $redirect = add_query_arg('gffm_oauth', 'facebook', self::redirect_uri());
        $resp = wp_remote_get('https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect,
            'client_secret' => $client_secret,
            'code' => $code,
        ]));
        if ( is_wp_error($resp) ) {
            return;
        }
        $data = json_decode(wp_remote_retrieve_body($resp), true);
        if ( empty($data['access_token']) ) {
            return;
        }
        $info = wp_remote_get('https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,name,email',
            'access_token' => $data['access_token'],
        ]));
        if ( is_wp_error($info) ) {
            return;
        }
        $profile = json_decode(wp_remote_retrieve_body($info), true);
        if ( empty($profile['email']) ) {
            return;
        }
        self::login_or_create($profile['email'], $profile['name'] ?? '');
        wp_safe_redirect(self::redirect_uri());
        exit;
    }

    private static function login_or_create(string $email, string $name) {
        $user = get_user_by('email', $email);
        if ( ! $user ) {
            $username = sanitize_user(current(explode('@', $email)), true);
            $uid = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email,
                'display_name' => $name,
                'role' => 'gffm_vendor',
            ]);
            if ( is_wp_error($uid) ) {
                return;
            }
            $user = get_user_by('id', $uid);
        } else {
            $user->set_role('gffm_vendor');
        }
        wp_set_auth_cookie($user->ID, true);
    }
}
GFFM_OAuth::init();
