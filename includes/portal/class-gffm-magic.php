<?php
defined('ABSPATH') || exit;

class GFFM_Magic {
  public static function issue_token(int $user_id, int $ttl = 86400): string {
    $exp = time() + max(60, $ttl);
    $data = $user_id . '|' . $exp;
    $sig  = hash_hmac('sha256', $data, wp_salt('auth'));
    return base64_encode($data . '|' . $sig);
  }

  public static function parse_token(string $token) {
    $raw = base64_decode($token, true);
    if (!$raw) return false;
    $parts = explode('|', $raw);
    if (count($parts) !== 3) return false;
    [$uid, $exp, $sig] = $parts;
    $calc = hash_hmac('sha256', $uid . '|' . $exp, wp_salt('auth'));
    if (!hash_equals($calc, $sig)) return false;
    if (time() > (int) $exp) return false;
    return (int) $uid;
  }

  public static function route() {
    if (!isset($_GET['gffm_magic'])) return;
    $uid = self::parse_token(sanitize_text_field(wp_unslash($_GET['gffm_magic'])));
    if ($uid) {
      wp_set_auth_cookie($uid, true);
      wp_safe_redirect(home_url('/vendor-portal/'));
      exit;
    }
  }
}
add_action('init', ['GFFM_Magic','route']);
