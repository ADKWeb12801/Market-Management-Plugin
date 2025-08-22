<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Export {
    public static function render_page(){
        if( ! current_user_can('gffm_manage')) wp_die(__('You do not have permission.','gffm'));
        echo '<div class="wrap gffm-admin"><h1>'.esc_html__('Export / Import','gffm').'</h1>';

        if( isset($_POST['gffm_do_export']) && check_admin_referer('gffm_export') ){
            $data = self::export_data();
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="gffm-export-'.date('Ymd-His').'.json"');
            echo wp_json_encode($data);
            exit;
        }

        if( isset($_POST['gffm_do_import']) && check_admin_referer('gffm_import') && !empty($_FILES['gffm_import_file']['tmp_name']) ){
            $json = file_get_contents($_FILES['gffm_import_file']['tmp_name']);
            $data = json_decode($json, true);
            self::import_data($data);
            echo '<div class="updated"><p>'.esc_html__('Import complete.','gffm').'</p></div>';
        }

        echo '<h2>'.esc_html__('Export','gffm').'</h2>';
        echo '<form method="post">';
        wp_nonce_field('gffm_export');
        echo '<p><button class="button button-primary" name="gffm_do_export" value="1">'.esc_html__('Download Export (JSON)','gffm').'</button></p>';
        echo '</form>';

        echo '<hr/><h2>'.esc_html__('Import','gffm').'</h2>';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('gffm_import');
        echo '<p><input type="file" name="gffm_import_file" accept="application/json" required /></p>';
        echo '<p><button class="button" name="gffm_do_import" value="1">'.esc_html__('Import JSON','gffm').'</button></p>';
        echo '</form>';

        echo '</div>';
    }

    public static function export_data(){
        $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
        $cpt = $use_internal ? 'gffm_vendor' : 'vendor';

        $vendors = get_posts(['post_type'=>$cpt,'posts_per_page'=>-1]);
        $arr = [
            'vendors'=>[],
            'enrollments'=>[],
            'invoices'=>[],
            'settings'=>[
                'notification_email'=> get_option('gffm_notification_email',''),
                'use_internal_vendors'=> get_option('gffm_use_internal_vendors','no'),
                'max_vendors'=> (int)get_option('gffm_max_vendors',0),
            ]
        ];
        foreach($vendors as $v){
            $arr['vendors'][] = [
                'ID' => $v->ID,
                'title' => $v->post_title,
                'status' => $v->post_status,
                '_gffm_enabled' => get_post_meta($v->ID,'_gffm_enabled',true),
                '_email' => get_post_meta($v->ID,'_email',true),
            ];
        }

        $enrolls = get_posts(['post_type'=>'gffm_enrollment','posts_per_page'=>-1]);
        foreach($enrolls as $e){
            $arr['enrollments'][] = [
                'ID'=>$e->ID,
                'title'=>$e->post_title,
                '_email'=>get_post_meta($e->ID,'_email',true),
                '_notes'=>get_post_meta($e->ID,'_notes',true),
                '_status'=>get_post_meta($e->ID,'_status',true),
            ];
        }

        $invoices = get_posts(['post_type'=>'gffm_invoice','posts_per_page'=>-1]);
        foreach($invoices as $i){
            $arr['invoices'][] = [
                'ID'=>$i->ID,
                'title'=>$i->post_title,
                '_vendor_id'=>get_post_meta($i->ID,'_vendor_id',true),
                '_amount_due'=>get_post_meta($i->ID,'_amount_due',true),
                '_due_date'=>get_post_meta($i->ID,'_due_date',true),
                '_status'=>get_post_meta($i->ID,'_status',true),
            ];
        }
        return $arr;
    }

    public static function import_data($data){
        if(!is_array($data)) return;

        if(isset($data['settings'])){
            foreach(['notification_email'=>'gffm_notification_email','use_internal_vendors'=>'gffm_use_internal_vendors','max_vendors'=>'gffm_max_vendors'] as $k=>$opt){
                if(isset($data['settings'][$k])){
                    update_option($opt, $data['settings'][$k]);
                }
            }
        }

        // vendors cannot be fully recreated if using existing CPT; we only update meta matches by title
        if(isset($data['vendors']) && is_array($data['vendors'])){
            foreach($data['vendors'] as $v){
                $pid = 0;
                if( !empty($v['ID']) ){
                    $pid = absint($v['ID']);
                }
                if($pid && get_post($pid)){
                    // update meta
                    if(isset($v['_gffm_enabled'])) update_post_meta($pid,'_gffm_enabled', $v['_gffm_enabled']);
                    if(isset($v['_email'])) update_post_meta($pid,'_email', sanitize_email($v['_email']));
                }
            }
        }

        if(isset($data['enrollments'])){
            foreach($data['enrollments'] as $e){
                $pid = wp_insert_post(['post_type'=>'gffm_enrollment','post_title'=>$e['title'] ?? 'Enrollment','post_status'=>'publish']);
                if($pid){
                    if(isset($e['_email'])) update_post_meta($pid,'_email', sanitize_email($e['_email']));
                    if(isset($e['_notes'])) update_post_meta($pid,'_notes', sanitize_text_field($e['_notes']));
                    if(isset($e['_status'])) update_post_meta($pid,'_status', sanitize_text_field($e['_status']));
                }
            }
        }

        if(isset($data['invoices'])){
            foreach($data['invoices'] as $i){
                $pid = wp_insert_post(['post_type'=>'gffm_invoice','post_title'=>$i['title'] ?? 'Invoice','post_status'=>'publish']);
                if($pid){
                    foreach(['_vendor_id','_amount_due','_due_date','_status'] as $m){
                        if(isset($i[$m])) update_post_meta($pid,$m, sanitize_text_field($i[$m]));
                    }
                }
            }
        }
    }
}
