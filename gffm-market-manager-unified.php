<?php
/**
 * Plugin Name: GFFM Market Manager — Unified (Magic Link Fix)
 * Description: Unified Market Manager with Vendor↔User bridge, Magic-Link login that authenticates users, Vendor dashboard with SCF fields + Weekly Highlight.
 * Version: 4.1.1
 * Author: ADK Web Solutions
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: gffm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define('GFFM_VERSION','4.1.1');
define('GFFM_DIR', plugin_dir_path(__FILE__));
define('GFFM_URL', plugin_dir_url(__FILE__));

$gffm_includes = array(
  'class-gffm-roles.php',
  'class-gffm-post-types.php',
  'class-gffm-settings.php',
  'class-gffm-admin.php',
  'class-gffm-enrollment.php',
  'class-gffm-waitlist.php',
  'class-gffm-export.php',
  'class-gffm-invoices.php',
  'class-gffm-cron.php',
  'class-gffm-rest.php',
  'class-gffm-vendor-users.php',
  'class-gffm-highlights.php',
  'class-gffm-portal.php',
);

foreach( $gffm_includes as $file ) { $path = GFFM_DIR . 'includes/' . $file; if( file_exists($path) ) require_once $path; }

register_activation_hook(__FILE__, function(){
    if ( class_exists('GFFM_Roles') ) { GFFM_Roles::add_roles(); }
    if ( class_exists('GFFM_Post_Types') ) { GFFM_Post_Types::init(); flush_rewrite_rules(); }
});

add_action('admin_enqueue_scripts', function($hook){
    if( strpos($hook, 'gffm') !== false ){
        wp_enqueue_style('gffm-admin', GFFM_URL.'assets/css/admin.css', [], GFFM_VERSION);
        wp_enqueue_script('gffm-admin', GFFM_URL.'assets/js/admin.js', [], GFFM_VERSION, true);
    }
});