<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Settings {
    public static function init(){
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function menu(){
        add_menu_page(
            __('Market Manager','gffm'),
            __('Market Manager','gffm'),
            'gffm_manage',
            'gffm',
            [__CLASS__, 'render_dashboard'],
            'dashicons-analytics',
            56
        );

        add_submenu_page('gffm', __('Settings','gffm'), __('Settings','gffm'), 'gffm_manage', 'gffm_settings', [__CLASS__, 'render_settings']);
        add_submenu_page('gffm', __('Vendor Assignment','gffm'), __('Vendor Assignment','gffm'), 'gffm_manage', 'gffm_assignment', ['GFFM_Admin', 'render_assignment']);
        add_submenu_page('gffm', __('Export / Import','gffm'), __('Export / Import','gffm'), 'gffm_manage', 'gffm_export', ['GFFM_Export', 'render_page']);
        add_submenu_page('gffm', __('Invoices','gffm'), __('Invoices','gffm'), 'gffm_manage', 'edit.php?post_type=gffm_invoice');
        add_submenu_page('gffm', __('Enrollments','gffm'), __('Enrollments','gffm'), 'gffm_manage', 'edit.php?post_type=gffm_enrollment');
    }

    public static function register_settings(){
        register_setting('gffm_settings', 'gffm_notification_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => ''
        ]);
        register_setting('gffm_settings', 'gffm_use_internal_vendors', [
            'type' => 'string',
            'sanitize_callback' => function($v){ return $v==='yes'?'yes':'no'; },
            'default' => 'no'
        ]);
        register_setting('gffm_settings', 'gffm_max_vendors', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
    }

    public static function render_dashboard(){
        if( ! current_user_can('gffm_manage')) wp_die(__('You do not have permission.','gffm'));
        echo '<div class="wrap gffm-admin">';
        echo '<div class="gffm-admin-h"><img class="gffm-logo" src="'.esc_url(GFFM_URL.'assets/logo.png').'" alt="logo"/><h1>'.esc_html__('Market Manager','gffm').'</h1></div>';
        echo '<div class="gffm-cards">';
        echo '<a class="gffm-card" href="'.admin_url('admin.php?page=gffm_settings').'"><h3>'.esc_html__('Settings','gffm').'</h3><p>'.esc_html__('Configure emails, vendors mode, capacity, and more.','gffm').'</p></a>';
        echo '<a class="gffm-card" href="'.admin_url('admin.php?page=gffm_assignment').'"><h3>'.esc_html__('Vendor Assignment','gffm').'</h3><p>'.esc_html__('Link existing vendor records or enable them for this system.','gffm').'</p></a>';
        echo '<a class="gffm-card" href="'.admin_url('admin.php?page=gffm_export').'"><h3>'.esc_html__('Export / Import','gffm').'</h3><p>'.esc_html__('Backup or migrate your data (CSV/JSON).','gffm').'</p></a>';
        echo '<a class="gffm-card" href="'.admin_url('edit.php?post_type=gffm_enrollment').'"><h3>'.esc_html__('Enrollments','gffm').'</h3><p>'.esc_html__('View & manage vendor signups and waitlist.','gffm').'</p></a>';
        echo '<a class="gffm-card" href="'.admin_url('edit.php?post_type=gffm_invoice').'"><h3>'.esc_html__('Invoices','gffm').'</h3><p>'.esc_html__('Create invoices and send reminders.','gffm').'</p></a>';
        echo '</div></div>';
    }

    public static function render_settings(){
        if( ! current_user_can('gffm_manage')) wp_die(__('You do not have permission.','gffm'));
        echo '<div class="wrap gffm-admin"><h1>'.esc_html__('Settings','gffm').'</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('gffm_settings');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th><label for="gffm_notification_email">'.esc_html__('Notification Email Override','gffm').'</label></th>';
        echo '<td><input type="email" name="gffm_notification_email" id="gffm_notification_email" value="'.esc_attr(get_option('gffm_notification_email','')).'" class="regular-text" />';
        echo '<p class="description">'.esc_html__('If set, enrollment & invoice emails will be sent here instead of the default site admin email.','gffm').'</p></td></tr>';

        echo '<tr><th><label for="gffm_use_internal_vendors">'.esc_html__('Use Internal Vendors CPT','gffm').'</label></th>';
        $use = get_option('gffm_use_internal_vendors','no');
        echo '<td><select name="gffm_use_internal_vendors" id="gffm_use_internal_vendors">';
        echo '<option value="no" '.selected($use,'no',false).'>'.esc_html__('No — use existing vendors CPT (e.g., vendor)','gffm').'</option>';
        echo '<option value="yes" '.selected($use,'yes',false).'>'.esc_html__('Yes — use plugin CPT (gffm_vendor)','gffm').'</option>';
        echo '</select></td></tr>';

        echo '<tr><th><label for="gffm_max_vendors">'.esc_html__('Max Active Vendors (0 = unlimited)','gffm').'</label></th>';
        echo '<td><input type="number" min="0" step="1" name="gffm_max_vendors" id="gffm_max_vendors" value="'.esc_attr(get_option('gffm_max_vendors',0)).'"/></td></tr>';

        echo '</table>';
        submit_button();
        echo '</form></div>';
    }

    // helper to get notification target
    public static function notify_email(){
        $over = get_option('gffm_notification_email', '');
        if ( is_email($over) ){
            return $over;
        }
        return get_option('admin_email');
    }
}
GFFM_Settings::init();
