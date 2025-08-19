<?php
/**
 * Plugin Name: GFFM Market Manager
 * Description: Vendor dashboard + weekly specials, Manager & Treasurer dashboards, invoices with reminders/receipts, vendor waitlist & enrollment, branded emails.
 * Version: 2.2.0
 * Author: ADK Web Solutions
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('GFFM_MM_VER','2.2.0');
define('GFFM_MM_DIR', plugin_dir_path(__FILE__));
define('GFFM_MM_URL', plugin_dir_url(__FILE__));
define('GFFM_MM_LOGO','https://glensfallsfarmersmarket.com/wp-content/uploads/2023/12/Correct-GFFM-Logo-optimized.png');

spl_autoload_register(function($class){
  if (strpos($class,'GFFM\\Market\\')!==0) return;
  $path = strtolower(str_replace('GFFM\\Market\\','',$class));
  $file = GFFM_MM_DIR.'includes/'.$path.'.php';
  if (file_exists($file)) require_once $file;
});
require_once GFFM_MM_DIR.'includes/helpers.php';

register_activation_hook(__FILE__, function(){
  GFFM\Market\Roles::add_role();
  GFFM\Market\Roles::grant_caps();
  GFFM\Market\Post_Types::register();
  GFFM\Market\Fees::register_cpt();
  GFFM\Market\Invoices::register_cpt();
  GFFM\Market\Applications::register_cpt();
  flush_rewrite_rules();
  GFFM\Market\Cron::schedule_all();
  // auto-detect vendor CPT
  $opts = get_option('gffm_mm_options', []);
  if (empty($opts['vendor_post_type'])) {
    $found = null;
    foreach (get_post_types(['public'=>true],'objects') as $pt) {
      if (in_array($pt->name, ['vendor','vendors','gffm_vendor'])) {
        $counts = wp_count_posts($pt->name); $total = 0; foreach((array)$counts as $v) $total += (int)$v;
        if ($total>0 && $pt->name!=='gffm_vendor') { $found=$pt->name; break; }
        if (in_array($pt->name,['vendor','vendors'])) $found=$pt->name;
      }
    }
    if ($found) { $opts['vendor_post_type']=$found; $opts['register_vendor_cpt']=($found==='gffm_vendor')?1:0; update_option('gffm_mm_options',$opts); }
  }
});

register_deactivation_hook(__FILE__, function(){
  GFFM\Market\Cron::clear_all();
  flush_rewrite_rules();
});

add_action('init', function(){
  GFFM\Market\Post_Types::register();
  GFFM\Market\Fees::register_cpt();
  GFFM\Market\Invoices::register_cpt();
  GFFM\Market\Applications::register_cpt();
  GFFM\Market\Status::register();
});

add_action('plugins_loaded', function(){
  (new GFFM\Market\Assets())->hooks();
  (new GFFM\Market\User_Meta())->hooks();
  (new GFFM\Market\Shortcodes())->hooks();
  (new GFFM\Market\Dashboard())->hooks();
  (new GFFM\Market\Settings())->hooks();
  (new GFFM\Market\Cron())->hooks();
  (new GFFM\Market\Admin())->hooks();
  (new GFFM\Market\Fees())->hooks();
  (new GFFM\Market\Export())->hooks();
  (new GFFM\Market\Invoices())->hooks();
  (new GFFM\Market\Applications())->hooks();
  (new GFFM\Market\Emails())->hooks();
});
