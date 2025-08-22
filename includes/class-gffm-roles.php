<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Roles {
    public static function add_roles(){
        add_role('gffm_manager', __('Market Manager','gffm'), [
            'read' => true,
            'gffm_manage' => true,
            'edit_posts' => false,
        ]);

        // map caps to admin too
        $admin = get_role('administrator');
        if($admin && !$admin->has_cap('gffm_manage')){
            $admin->add_cap('gffm_manage');
        }
    }
}
