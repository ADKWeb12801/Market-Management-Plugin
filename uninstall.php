<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { die; }
if ( get_option('gffm_remove_data_on_uninstall','no') === 'yes' ) {
    $opts = [
        'gffm_notification_email',
        'gffm_use_internal_vendors',
        'gffm_max_vendors',
        'gffm_week_start_day',
        'gffm_profile_map_json',
        'gffm_invite_subject',
        'gffm_invite_body',
        'gffm_auth_login_branding',
        'gffm_append_vendor_role',
        'gffm_enforce_email_login',
        'gffm_remove_data_on_uninstall',
    ];
    foreach ( $opts as $opt ) { delete_option( $opt ); }
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gffm_%' OR option_name LIKE '_transient_timeout_gffm_%'" );
}
