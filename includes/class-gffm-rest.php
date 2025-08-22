<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Rest {
    public static function init(){
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }
    public static function routes(){
        register_rest_route('gffm/v1','/vendors', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => function($req){
                $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
                $cpt = $use_internal ? 'gffm_vendor' : 'vendor';
                $posts = get_posts(['post_type'=>$cpt,'posts_per_page'=>-1]);
                $out = [];
                foreach($posts as $p){
                    $out[] = ['id'=>$p->ID,'title'=>$p->post_title,'enabled'=>get_post_meta($p->ID,'_gffm_enabled',true)];
                }
                return rest_ensure_response($out);
            }
        ]);
    }
}
GFFM_Rest::init();
