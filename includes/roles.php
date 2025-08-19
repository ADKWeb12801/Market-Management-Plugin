<?php
namespace GFFM\Market; if(!defined('ABSPATH')) exit;
class Roles{
  const CAP = 'gffm_manage_market';
  public static function add_role(){
    add_role('market_vendor','Market Vendor',['read'=>true,'edit_posts'=>true,'upload_files'=>true]);
    add_role('market_manager','Market Manager',['read'=>true,'edit_posts'=>true,'upload_files'=>true,self::CAP=>true,'manage_options'=>true]);
    add_role('market_treasurer','Market Treasurer',['read'=>true,'edit_posts'=>true,'upload_files'=>true,self::CAP=>true]);
  }
  public static function grant_caps(){
    foreach(['administrator','editor'] as $role){
      $r=get_role($role); if($r) $r->add_cap(self::CAP);
    }
  }
}