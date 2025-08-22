<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { die; }
delete_option('gffm_notification_email');
delete_option('gffm_use_internal_vendors');
delete_option('gffm_max_vendors');
delete_option('gffm_week_start_day');
delete_option('gffm_profile_map_json');
delete_option('gffm_invite_subject');
delete_option('gffm_invite_body');
