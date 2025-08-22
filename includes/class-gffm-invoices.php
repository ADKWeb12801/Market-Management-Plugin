<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Invoices {
    public static function init(){
        add_action('add_meta_boxes', [__CLASS__, 'metabox']);
        add_action('save_post_gffm_invoice', [__CLASS__, 'save'], 10, 2);
        add_action('gffm_daily_cron', [__CLASS__, 'send_due_reminders']);
    }

    public static function metabox(){
        add_meta_box('gffm_invoice_meta', __('Invoice Details','gffm'), [__CLASS__, 'render_box'], 'gffm_invoice', 'normal', 'default');
    }

    public static function render_box($post){
        wp_nonce_field('gffm_invoice_meta','gffm_invoice_meta_nonce');
        $vendor_id = get_post_meta($post->ID, '_vendor_id', true);
        $amount_due = get_post_meta($post->ID, '_amount_due', true);
        $due_date  = get_post_meta($post->ID, '_due_date', true);
        $status    = get_post_meta($post->ID, '_status', true);

        $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
        $cpt = $use_internal ? 'gffm_vendor' : 'vendor';
        $vendors = get_posts(['post_type'=>$cpt,'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);

        echo '<p><label>'.esc_html__('Vendor','gffm').'<br/><select name="gffm_vendor_id">';
        echo '<option value="">'.esc_html__('Select vendor','gffm').'</option>';
        foreach($vendors as $v){
            echo '<option value="'.esc_attr($v->ID).'" '.selected($vendor_id, $v->ID,false).'>'.esc_html($v->post_title).'</option>';
        }
        echo '</select></label></p>';

        echo '<p><label>'.esc_html__('Amount Due (numbers only)','gffm').'<br/>';
        echo '<input type="number" min="0" step="0.01" name="gffm_amount_due" value="'.esc_attr($amount_due).'"/></label></p>';

        echo '<p><label>'.esc_html__('Due Date (YYYY-MM-DD)','gffm').'<br/>';
        echo '<input type="date" name="gffm_due_date" value="'.esc_attr($due_date).'"/></label></p>';

        echo '<p><label>'.esc_html__('Status','gffm').'<br/>';
        echo '<select name="gffm_status">';
        foreach(['draft','sent','paid','overdue'] as $st){
            echo '<option value="'.esc_attr($st).'" '.selected($status,$st,false).'>'.esc_html(ucfirst($st)).'</option>';
        }
        echo '</select></label></p>';

        echo '<p><a href="'.esc_url(admin_url('admin-post.php?action=gffm_send_invoice&post_id='.$post->ID)).'" class="button">'.esc_html__('Send Invoice Email','gffm').'</a></p>';
    }

    public static function save($post_id, $post){
        if( !isset($_POST['gffm_invoice_meta_nonce']) || !wp_verify_nonce($_POST['gffm_invoice_meta_nonce'],'gffm_invoice_meta') ) return;
        update_post_meta($post_id, '_vendor_id', absint($_POST['gffm_vendor_id'] ?? 0));
        update_post_meta($post_id, '_amount_due', sanitize_text_field($_POST['gffm_amount_due'] ?? ''));
        update_post_meta($post_id, '_due_date', sanitize_text_field($_POST['gffm_due_date'] ?? ''));
        update_post_meta($post_id, '_status', sanitize_text_field($_POST['gffm_status'] ?? 'draft'));
    }

    public static function send_due_reminders(){
        $today = date('Y-m-d');
        $q = new WP_Query([
            'post_type'=>'gffm_invoice',
            'posts_per_page'=>-1,
            'meta_query'=>[
                ['key'=>'_due_date','value'=>$today,'compare'=>'<='],
                ['key'=>'_status','value'=>['sent','overdue'],'compare'=>'IN']
            ]
        ]);
        if($q->have_posts()){
            while($q->have_posts()){ $q->the_post();
                $pid = get_the_ID();
                $vendor_id = get_post_meta($pid,'_vendor_id',true);
                $email = get_post_meta($vendor_id,'_email',true);
                if( !is_email($email) ){
                    // fallback: notify admin
                    $email = GFFM_Settings::notify_email();
                }
                $amount = get_post_meta($pid,'_amount_due',true);
                $due = get_post_meta($pid,'_due_date',true);
                $body = sprintf(__('Invoice #%d is due. Amount: %s. Due: %s.','gffm'), $pid, $amount, $due);
                wp_mail($email, __('Invoice Reminder','gffm'), $body);
                update_post_meta($pid,'_status','overdue');
            }
            wp_reset_postdata();
        }
    }
}
add_action('init', ['GFFM_Invoices','init']);

// send invoice email (manual button)
add_action('admin_post_gffm_send_invoice', function(){
    if( ! current_user_can('gffm_manage')) wp_die(__('No permission','gffm'));
    $post_id = absint($_GET['post_id'] ?? 0);
    if($post_id){
        $vendor_id = get_post_meta($post_id,'_vendor_id',true);
        $email = get_post_meta($vendor_id,'_email',true);
        if( !is_email($email) ){
            $email = GFFM_Settings::notify_email();
        }
        $amount = get_post_meta($post_id,'_amount_due',true);
        $due = get_post_meta($post_id,'_due_date',true);
        $body = sprintf(__('Invoice #%d. Amount: %s. Due: %s.','gffm'), $post_id, $amount, $due);
        wp_mail($email, __('Invoice','gffm'), $body);
        update_post_meta($post_id,'_status','sent');
    }
    wp_safe_redirect(admin_url('post.php?post='.$post_id.'&action=edit'));
    exit;
});
