<?php
namespace GFFM\Market; if(!defined('ABSPATH')) exit;
class Admin{
  public function hooks(){
    add_action('admin_menu',[$this,'menus']);
  }
  public function menus(){
    add_menu_page('Market','Market','read','gffm_market',[$this,'overview'],'dashicons-store',58);
    add_submenu_page('gffm_market','Manager Dashboard','Manager Dashboard',Roles::CAP,'gffm_manager',[$this,'manager_page']);
    add_submenu_page('gffm_market','Treasurer','Treasurer',Roles::CAP,'gffm_treasurer',[$this,'treasurer_page']);
    add_submenu_page('gffm_market','Highlights','Highlights',Roles::CAP,'edit.php?post_type=gffm_special');
    add_submenu_page('gffm_market','Invoices','Invoices',Roles::CAP,'edit.php?post_type=gffm_invoice');
    add_submenu_page('gffm_market','Applications','Applications',Roles::CAP,'edit.php?post_type=gffm_application');
    add_submenu_page('gffm_market','Exports','Exports',Roles::CAP,'gffm_exports',[$this,'exports']);
    add_submenu_page('gffm_market','Settings','Settings','manage_options','gffm-market',[new Settings(),'render']);
  }
  public function overview(){
    echo '<div class="wrap gffm-admin">';
    echo '<div class="gffm-admin-h">';
    echo '<img class="gffm-logo" src="'.esc_url(GFFM_MM_LOGO).'" alt="GFFM" />';
    echo '<h1>GFFM Market Manager</h1>';
    echo '<p class="desc">Manage vendors, specials, invoices, waitlist & enrollment. All settings are available to Administrators.</p>';
    echo '</div>';
    echo '<div class="gffm-cards">';
    $cards = [
      ['Manager','Review applications & enrollment','admin.php?page=gffm_manager'],
      ['Treasurer','Create invoices & receipts','admin.php?page=gffm_treasurer'],
      ['Highlights','Weekly vendor highlights','edit.php?post_type=gffm_special'],
      ['Invoices','All invoices','edit.php?post_type=gffm_invoice'],
      ['Applications','Waitlist & applications','edit.php?post_type=gffm_application'],
      ['Settings','Global plugin settings','options-general.php?page=gffm-market'],
    ];
    foreach($cards as $c){
      echo '<a class="gffm-card" href="'.esc_url(admin_url($c[2])).'"><h3>'.$c[0].'</h3><p>'.$c[1].'</p></a>';
    }
    echo '</div></div>';
  }
  public function manager_page(){
    if(!current_user_can(Roles::CAP) && !current_user_can('manage_options')) wp_die('Not allowed');
    $apps=new \WP_Query(['post_type'=>'gffm_application','post_status'=>'pending','posts_per_page'=>10,'orderby'=>'date','order'=>'DESC']);
    echo '<div class="wrap"><h1><img src="'.esc_url(GFFM_MM_LOGO).'" style="height:28px;vertical-align:middle;margin-right:8px">Manager</h1>';
    echo '<h2>Pending Applications</h2>';
    if($apps->have_posts()){
      echo '<table class="widefat"><thead><tr><th>Business</th><th>Contact</th><th>Categories</th><th>Docs</th><th>Actions</th></tr></thead><tbody>';
      while($apps->have_posts()){ $apps->the_post(); $pid=get_the_ID();
        echo '<tr><td><a href="'.esc_url(get_edit_post_link()).'">'.esc_html(get_the_title()).'</a></td>';
        echo '<td>'.esc_html(get_post_meta($pid,'applicant_name',true)).' — '.esc_html(get_post_meta($pid,'email',true)).'</td>';
        echo '<td>'.esc_html(get_post_meta($pid,'categories',true)).'</td><td>';
        $ins=get_post_meta($pid,'insurance_id',true); $perm=get_post_meta($pid,'permits_id',true);
        if($ins) echo '<a href="'.esc_url(wp_get_attachment_url($ins)).'" target="_blank">Insurance</a> ';
        if($perm) echo '<a href="'.esc_url(wp_get_attachment_url($perm)).'" target="_blank">Permits</a>';
        echo '</td><td>';
        echo '<a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=gffm_app_action&do=approve&pid='.$pid),'gffm_app_action')).'">Approve</a> ';
        echo '<a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=gffm_app_action&do=reject&pid='.$pid),'gffm_app_action')).'">Reject</a>';
        echo '</td></tr>';
      }
      wp_reset_postdata();
      echo '</tbody></table>';
    } else {
      echo '<p>No new applications.</p>';
    }
    echo '</div>';
  }
  public function treasurer_page(){
    if(!current_user_can(Roles::CAP) && !current_user_can('manage_options')) wp_die('Not allowed');
    $vendors=get_posts(['post_type'=>Util::vendor_cpt(),'numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
    $types=Util::options()['bylaw_fee_types'];
    $unpaid=new \WP_Query(['post_type'=>'gffm_invoice','post_status'=>['gffm_unpaid','gffm_overdue'],'posts_per_page'=>20,'orderby'=>'date','order'=>'DESC']);
    $paid=new \WP_Query(['post_type'=>'gffm_invoice','post_status'=>['publish'],'posts_per_page'=>10,'orderby'=>'date','order'=>'DESC']);
    echo '<div class="wrap"><h1><img src="'.esc_url(GFFM_MM_LOGO).'" style="height:28px;vertical-align:middle;margin-right:8px">Treasurer</h1>';
    echo '<h2>Create Invoice</h2>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('gffm_create_invoice');
    echo '<input type="hidden" name="action" value="gffm_create_invoice">';
    print '<p><label>Vendor: <select name="vendor_id" required><option value="">— Select —</option>';
    foreach($vendors as $v){ print '<option value="'.$v->ID.'">'.esc_html($v->post_title).'</option>'; }
    print '</select></label></p>';
    print '<p><label>Type: <select name="invoice_type">'; foreach($types as $t){ print '<option>'.esc_html($t).'</option>'; } print '</select></label></p>';
    print '<p><label>Amount: $ <input type="number" step="0.01" name="amount" required></label></p>';
    print '<p><label>Due Date: <input type="date" name="due_date" value="'.esc_attr(date('Y-m-d', strtotime('+30 days'))).'">';
    print ' <span class="description">Preset tips: May 1 (Membership), Aug 1 (Summer balance), Oct 31 (Winter)</span></label></p>';
    print '<p><label>Send to Email (optional): <input type="email" name="vendor_email"></label></p>';
    print '<p><label>Note:<br><textarea name="note" rows="3" style="width:420px"></textarea></label></p>';
    print '<p><button class="button button-primary">Create & Email Invoice</button></p>';
    echo '</form>';

    echo '<h2>Unpaid & Overdue</h2>';
    if($unpaid->have_posts()){
      echo '<table class="widefat"><thead><tr><th>#</th><th>Vendor</th><th>Type</th><th>Amount</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
      while($unpaid->have_posts()){ $unpaid->the_post(); $pid=get_the_ID();
        $vid=(int)get_post_meta($pid,'gffm_vendor_id',true);
        echo '<tr><td>'.esc_html(get_post_meta($pid,'invoice_number',true)).'</td><td>'.esc_html(get_the_title($vid)).'</td><td>'.esc_html(get_post_meta($pid,'invoice_type',true)).'</td><td>$'.esc_html(number_format((float)get_post_meta($pid,'amount',true),2)).'</td><td>'.esc_html(get_post_meta($pid,'due_date',true)).'</td><td>'.esc_html(get_post_status($pid)).'</td><td>';
        echo '<a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=gffm_mark_paid&pid='.$pid),'gffm_mark_paid')).'">Mark Paid</a>';
        echo '</td></tr>';
      } wp_reset_postdata(); echo '</tbody></table>';
    } else { echo '<p>Nothing outstanding.</p>'; }

    echo '<h2>Recent Paid</h2>';
    if($paid->have_posts()){
      echo '<table class="widefat"><thead><tr><th>#</th><th>Vendor</th><th>Type</th><th>Amount</th><th>Paid At</th></tr></thead><tbody>';
      while($paid->have_posts()){ $paid->the_post(); $pid=get_the_ID(); $vid=(int)get_post_meta($pid,'gffm_vendor_id',true);
        echo '<tr><td>'.esc_html(get_post_meta($pid,'invoice_number',true)).'</td><td>'.esc_html(get_the_title($vid)).'</td><td>'.esc_html(get_post_meta($pid,'invoice_type',true)).'</td><td>$'.esc_html(number_format((float)get_post_meta($pid,'amount',true),2)).'</td><td>'.esc_html(get_post_meta($pid,'paid_at',true)).'</td></tr>';
      } wp_reset_postdata(); echo '</tbody></table>';
    } else { echo '<p>No recent payments.</p>'; }
    echo '</div>';
  }
  public function exports(){
    if(!current_user_can(Roles::CAP) && !current_user_can('manage_options')) wp_die('Not allowed');
    echo '<div class="wrap"><h1>Exports</h1>';
    echo '<p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=gffm_export&type=vendors'),'gffm_export')).'">Vendors CSV</a></p>';
    echo '<form method="get" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="gffm_export">';
    wp_nonce_field('gffm_export');
    echo '<p><label>From: <input type="date" name="from" required></label> ';
    echo '<label>To: <input type="date" name="to" required></label> ';
    echo '<button class="button button-primary">Download Invoices CSV</button></p></form></div>';
  }
}
