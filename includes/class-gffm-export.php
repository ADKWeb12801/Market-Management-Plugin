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
            $dry = ! empty($_POST['gffm_import_dry']);
            $dedupe = ! empty($_POST['gffm_import_dedupe']);
            $summary = self::import_data($data, $dry, $dedupe);
            echo '<div class="updated"><p>'.esc_html__('Import complete.','gffm').'</p>';
            foreach($summary as $section=>$c){
                echo '<p>'.esc_html(ucfirst($section).": created {$c['created']}, updated {$c['updated']}, skipped {$c['skipped']}").'</p>';
            }
            if($dry){ echo '<p>'.esc_html__('Dry run: no data written.','gffm').'</p>'; }
            echo '</div>';
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
        echo '<p><label><input type="checkbox" name="gffm_import_dry" value="1" /> '.esc_html__('Dry Run','gffm').'</label></p>';
        echo '<p><label><input type="checkbox" name="gffm_import_dedupe" value="1" /> '.esc_html__('Dedupe','gffm').'</label></p>';
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
            $linked = (int) get_post_meta($v->ID,'_gffm_linked_user',true);
            $email = '';
            if($linked){
                $u = get_userdata($linked);
                if($u){ $email = $u->user_email; }
            }
            $arr['vendors'][] = [
                'ID' => $v->ID,
                'slug' => $v->post_name,
                'title' => $v->post_title,
                'status' => $v->post_status,
                '_gffm_enabled' => get_post_meta($v->ID,'_gffm_enabled',true),
                '_email' => get_post_meta($v->ID,'_email',true),
                '_gffm_portal_enabled' => get_post_meta($v->ID,'_gffm_portal_enabled',true),
                'linked_user_email' => $email,
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

    public static function import_data($data, $dry_run = false, $dedupe = false){
        $summary = [
            'vendors'=>['created'=>0,'updated'=>0,'skipped'=>0],
            'enrollments'=>['created'=>0,'updated'=>0,'skipped'=>0],
            'invoices'=>['created'=>0,'updated'=>0,'skipped'=>0],
        ];
        if(!is_array($data)) return $summary;

        if(isset($data['settings'])){
            foreach(['notification_email'=>'gffm_notification_email','use_internal_vendors'=>'gffm_use_internal_vendors','max_vendors'=>'gffm_max_vendors'] as $k=>$opt){
                if(isset($data['settings'][$k]) && !$dry_run){
                    update_option($opt, $data['settings'][$k]);
                }
            }
        }

        if(isset($data['vendors']) && is_array($data['vendors'])){
            $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
            $cpt = $use_internal ? 'gffm_vendor' : 'vendor';
            foreach($data['vendors'] as $v){
                $pid = 0;
                if(!empty($v['ID']) && get_post($v['ID'])){
                    $pid = absint($v['ID']);
                } elseif(!empty($v['slug'])){
                    $existing = get_page_by_path(sanitize_title($v['slug']), OBJECT, $cpt);
                    if($existing) $pid = $existing->ID;
                } elseif(!empty($v['title'])){
                    $existing = get_page_by_title($v['title'], OBJECT, $cpt);
                    if($existing) $pid = $existing->ID;
                }
                if($pid){
                    if(!$dry_run){
                        if(isset($v['_gffm_enabled'])) update_post_meta($pid,'_gffm_enabled',$v['_gffm_enabled']);
                        if(isset($v['_email'])) update_post_meta($pid,'_email', sanitize_email($v['_email']));
                        if(isset($v['_gffm_portal_enabled'])) update_post_meta($pid,'_gffm_portal_enabled',$v['_gffm_portal_enabled']);
                        if(!empty($v['linked_user_email'])){
                            $u = get_user_by('email', $v['linked_user_email']);
                            if(!$u){
                                $uid = wp_create_user($v['linked_user_email'], wp_generate_password(12,false), $v['linked_user_email']);
                                if(!is_wp_error($uid)) $u = get_userdata($uid);
                            }
                            if($u){
                                $u->add_role('gffm_vendor');
                                update_user_meta($u->ID,'_gffm_vendor_id',$pid);
                                update_post_meta($pid,'_gffm_linked_user',$u->ID);
                            }
                        }
                    }
                    $summary['vendors']['updated']++;
                } else {
                    if($dedupe && !empty($v['title'])){
                        $existing = get_page_by_title($v['title'], OBJECT, $cpt);
                        if($existing){
                            if(!$dry_run){
                                $pid = $existing->ID;
                                if(isset($v['_gffm_enabled'])) update_post_meta($pid,'_gffm_enabled',$v['_gffm_enabled']);
                                if(isset($v['_email'])) update_post_meta($pid,'_email', sanitize_email($v['_email']));
                                if(isset($v['_gffm_portal_enabled'])) update_post_meta($pid,'_gffm_portal_enabled',$v['_gffm_portal_enabled']);
                            }
                            $summary['vendors']['updated']++;
                            continue;
                        }
                    }
                    if(!$dry_run){
                        $pid = wp_insert_post([
                            'post_type'=>$cpt,
                            'post_title'=>$v['title'] ?? 'Vendor',
                            'post_status'=>$v['status'] ?? 'publish',
                            'post_name'=>$v['slug'] ?? '',
                        ]);
                        if(isset($v['_gffm_enabled'])) update_post_meta($pid,'_gffm_enabled',$v['_gffm_enabled']);
                        if(isset($v['_email'])) update_post_meta($pid,'_email', sanitize_email($v['_email']));
                        if(isset($v['_gffm_portal_enabled'])) update_post_meta($pid,'_gffm_portal_enabled',$v['_gffm_portal_enabled']);
                        if(!empty($v['linked_user_email'])){
                            $u = get_user_by('email', $v['linked_user_email']);
                            if(!$u){
                                $uid = wp_create_user($v['linked_user_email'], wp_generate_password(12,false), $v['linked_user_email']);
                                if(!is_wp_error($uid)) $u = get_userdata($uid);
                            }
                            if($u){
                                $u->add_role('gffm_vendor');
                                update_user_meta($u->ID,'_gffm_vendor_id',$pid);
                                update_post_meta($pid,'_gffm_linked_user',$u->ID);
                            }
                        }
                    }
                    $summary['vendors']['created']++;
                }
            }
        }

        if(isset($data['enrollments'])){
            foreach($data['enrollments'] as $e){
                $pid = 0;
                if($dedupe && !empty($e['title'])){
                    $existing = get_page_by_title($e['title'],'OBJECT','gffm_enrollment');
                    if($existing) $pid = $existing->ID;
                }
                if($pid){
                    if(!$dry_run){
                        if(isset($e['_email'])) update_post_meta($pid,'_email', sanitize_email($e['_email']));
                        if(isset($e['_notes'])) update_post_meta($pid,'_notes', sanitize_text_field($e['_notes']));
                        if(isset($e['_status'])) update_post_meta($pid,'_status', sanitize_text_field($e['_status']));
                    }
                    $summary['enrollments']['updated']++;
                } else {
                    if(!$dry_run){
                        $pid = wp_insert_post(['post_type'=>'gffm_enrollment','post_title'=>$e['title'] ?? 'Enrollment','post_status'=>'publish']);
                        if(isset($e['_email'])) update_post_meta($pid,'_email', sanitize_email($e['_email']));
                        if(isset($e['_notes'])) update_post_meta($pid,'_notes', sanitize_text_field($e['_notes']));
                        if(isset($e['_status'])) update_post_meta($pid,'_status', sanitize_text_field($e['_status']));
                    }
                    $summary['enrollments']['created']++;
                }
            }
        }

        if(isset($data['invoices'])){
            foreach($data['invoices'] as $i){
                $pid = 0;
                if($dedupe && !empty($i['title']) && !empty($i['_due_date'])){
                    $existing = get_posts([
                        'post_type'=>'gffm_invoice',
                        'meta_key'=>'_due_date',
                        'meta_value'=>$i['_due_date'],
                        'posts_per_page'=>-1
                    ]);
                    if($existing){
                        foreach($existing as $ex){
                            if($ex->post_title === $i['title']){ $pid = $ex->ID; break; }
                        }
                    }
                }
                if($pid){
                    if(!$dry_run){
                        foreach(['_vendor_id','_amount_due','_due_date','_status'] as $m){
                            if(isset($i[$m])) update_post_meta($pid,$m, sanitize_text_field($i[$m]));
                        }
                    }
                    $summary['invoices']['updated']++;
                } else {
                    if(!$dry_run){
                        $pid = wp_insert_post(['post_type'=>'gffm_invoice','post_title'=>$i['title'] ?? 'Invoice','post_status'=>'publish']);
                        foreach(['_vendor_id','_amount_due','_due_date','_status'] as $m){
                            if(isset($i[$m])) update_post_meta($pid,$m, sanitize_text_field($i[$m]));
                        }
                    }
                    $summary['invoices']['created']++;
                }
            }
        }
        return $summary;
    }
}
