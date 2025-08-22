<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Cron {
    public static function init(){
        add_action('wp', [__CLASS__, 'schedule']);
        add_action('gffm_daily_cron', [__CLASS__, 'noop']);
    }
    public static function schedule(){
        if( !wp_next_scheduled('gffm_daily_cron') ){
            wp_schedule_event(time() + 3600, 'daily', 'gffm_daily_cron');
        }
    }
    public static function noop(){ /* hooked by other classes */ }
}
GFFM_Cron::init();
