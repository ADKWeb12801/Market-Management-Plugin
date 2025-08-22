<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Post_Types {
    public static function init(){
        add_action('init', [__CLASS__, 'register']);
    }

    public static function register(){
        // Optional internal Vendors CPT (can be disabled if site already has a 'vendor' CPT)
        $use_internal = get_option('gffm_use_internal_vendors', 'no') === 'yes';

        if( $use_internal ){
            register_post_type('gffm_vendor', [
                'label' => __('Vendors','gffm'),
                'public' => false, 'show_ui' => true,
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
                'menu_icon' => 'dashicons-store',
                'capability_type' => 'post',
            ]);
        }

        register_post_type('gffm_invoice', [
            'label' => __('Invoices','gffm'),
            'public' => false, 'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-spreadsheet',
        ]);

        register_post_type('gffm_enrollment', [
            'label' => __('Enrollments','gffm'),
            'public' => false, 'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-groups',
        ]);
    }
}
GFFM_Post_Types::init();
