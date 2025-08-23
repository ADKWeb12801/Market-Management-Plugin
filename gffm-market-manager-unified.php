<?php
/**
 * Plugin Name: GFFM Market Manager — Unified
 * Description: Unified Market Manager with Vendor↔User bridge and vendor dashboard with SCF fields + Weekly Highlight.
 * Version: 4.3.0
 * Author: ADK Web Solutions
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: gffm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define('GFFM_VERSION','4.3.0');
define('GFFM_DIR', plugin_dir_path(__FILE__));
define('GFFM_URL', plugin_dir_url(__FILE__));

require_once GFFM_DIR . 'includes/class-gffm-roles.php';
require_once GFFM_DIR . 'includes/class-gffm-post-types.php';
require_once GFFM_DIR . 'includes/class-gffm-settings.php';
require_once GFFM_DIR . 'includes/class-gffm-admin.php';
require_once GFFM_DIR . 'includes/class-gffm-enrollment.php';
require_once GFFM_DIR . 'includes/class-gffm-waitlist.php';

require_once GFFM_DIR . 'includes/helpers/class-gffm-util.php';
require_once GFFM_DIR . 'includes/portal/class-gffm-vendor-link.php';
require_once GFFM_DIR . 'includes/portal/class-gffm-portal-account.php';
require_once GFFM_DIR . 'includes/portal/class-gffm-portal.php';

require_once GFFM_DIR . 'includes/highlights/class-gffm-highlights.php';

register_activation_hook(__FILE__, function(){
    if ( class_exists('GFFM_Roles') ) {
        GFFM_Roles::add_roles();
    } else {
        add_role('gffm_vendor', 'Vendor', ['read'=>true,'upload_files'=>true]);
        $admin = get_role('administrator');
        if ( $admin && ! $admin->has_cap('gffm_manage') ) {
            $admin->add_cap('gffm_manage');
        }
    }
    if ( class_exists('GFFM_Post_Types') ) { GFFM_Post_Types::init(); flush_rewrite_rules(); }
});

add_action('admin_enqueue_scripts', function($hook){
    if( strpos($hook, 'gffm') !== false ){
        wp_enqueue_style('gffm-admin', GFFM_URL.'assets/css/admin.css', [], GFFM_VERSION);
        wp_enqueue_script('gffm-admin', GFFM_URL.'assets/js/admin.js', [], GFFM_VERSION, true);
        wp_localize_script('gffm-admin', 'gffmAdmin', [
            'i18n' => [
                'jsonValid' => __('JSON valid','gffm'),
                'jsonInvalid' => __('Invalid JSON','gffm'),
            ],
        ]);
    }
});
