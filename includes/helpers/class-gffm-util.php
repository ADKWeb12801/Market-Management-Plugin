<?php
defined('ABSPATH') || exit;

class GFFM_Util {
  public static function week_start_day(): string {
    $opt = get_option('gffm_week_start_day', 'saturday');
    $valid = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    return in_array(strtolower($opt), $valid, true) ? strtolower($opt) : 'saturday';
  }

  public static function week_key(?string $date = null): string {
    $ts = $date ? strtotime($date) : current_time('timestamp');
    $start = self::shift_to_week_start($ts, self::week_start_day());
    return wp_date('Y-\\WW', $start);
  }

  private static function shift_to_week_start(int $ts, string $startDay): int {
    $map = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6];
    $target = $map[$startDay] ?? 6;
    $current = (int) wp_date('w', $ts);
    $diff = $current - $target;
    if ($diff < 0) { $diff += 7; }
    return strtotime("-{$diff} days", $ts);
  }

  public static function current_user_vendor_id(): int {
    $uid = get_current_user_id();
    return (int) get_user_meta($uid, '_gffm_vendor_id', true);
  }

  public static function can_edit_vendor(int $vendor_id, int $user_id = 0): bool {
    $user_id = $user_id ?: get_current_user_id();
    if (user_can($user_id, 'manage_options') || user_can($user_id, 'gffm_manage')) return true;
    $linked = (int) get_user_meta($user_id, '_gffm_vendor_id', true);
    return $linked && $linked === (int)$vendor_id;
  }
}
