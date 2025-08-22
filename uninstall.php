<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { die; }
delete_option('gffm_notification_email');
delete_option('gffm_use_internal_vendors');
delete_option('gffm_max_vendors');
