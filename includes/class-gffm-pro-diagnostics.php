<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GFFM_Pro_Diagnostics {
    public static function init() {
        add_shortcode('gffm_pro_diag', array(__CLASS__, 'diag_shortcode'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }
    public static function menu() {
        add_submenu_page('gffm','Pro Diagnostics','Pro Diagnostics','manage_options','gffm-pro-diag',array(__CLASS__,'render'));
    }
    public static function render() {
        echo '<div class="wrap"><h1>GFFM Pro Diagnostics</h1>';
        echo '<ul>';
        echo '<li>Shortcode gffm_booth_map registered: '.(shortcode_exists('gffm_booth_map')?'yes':'no').'</li>';
        echo '<li>Shortcode gffm_directory registered: '.(shortcode_exists('gffm_directory')?'yes':'no').'</li>';
        echo '<li>Shortcode gffm_pro_diag registered: '.(shortcode_exists('gffm_pro_diag')?'yes':'no').'</li>';
        echo '</ul></div>';
    }
    public static function diag_shortcode() {
        return '<div class="gffm-pro-diag">gffm_pro_diag shortcode rendered.</div>';
    }
}
