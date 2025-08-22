<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'GFFM_Roles' ) ) {
    class GFFM_Roles {
        public static function add_roles(){
            add_role('gffm_manager', __('Market Manager','gffm'), [
                'read' => true,
                'gffm_manage' => true,
                'publish_gffm_highlights' => true,
                'edit_posts' => false,
            ]);

            self::ensure_vendor_role();

            // map caps to admin too
            $admin = get_role('administrator');
            if ( $admin ) {
                if ( ! $admin->has_cap('gffm_manage') ) {
                    $admin->add_cap('gffm_manage');
                }
                if ( ! $admin->has_cap('publish_gffm_highlights') ) {
                    $admin->add_cap('publish_gffm_highlights');
                }
            }
        }

        public static function ensure_vendor_role(){
            if ( ! get_role('gffm_vendor') ) {
                add_role('gffm_vendor', __('Vendor','gffm'), [
                    'read' => true,
                    'upload_files' => true,
                ]);
            }
        }
    }
}
