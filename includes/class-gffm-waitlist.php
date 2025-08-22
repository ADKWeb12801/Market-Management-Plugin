<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Waitlist {
    public static function promote_to_active($enrollment_id, $vendor_post_id){
        if(!current_user_can('gffm_manage')) return false;
        // Mark vendor as enabled
        update_post_meta($vendor_post_id, '_gffm_enabled', '1');
        // Update enrollment status
        update_post_meta($enrollment_id, '_status', 'promoted');
        return true;
    }
}
