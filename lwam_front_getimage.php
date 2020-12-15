<?php
  //-----------------------------------------------------------
  // lwam_front_getimage.php - get product image for front page
  // AJAX back end
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------------------
  //-----------------
  // Wordpress stuffs
  //-----------------
  $parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
  require_once( $parse_uri[0] . 'wp-load.php' );
  //-----------------------------------------
  // Do not forget to invoke the wpdb object!
  //-----------------------------------------
  global $wpdb;
  //------------
  // Get the sku
  //------------
  $sku = $_REQUEST["sku"];
  $q = "SELECT ";
  $q .= $wpdb->prefix."postmeta.post_id ";
  $q .= " FROM ";
  $q .= $wpdb->prefix."postmeta ";
  $q .= " INNER JOIN ";
  $q .= $wpdb->prefix."posts ";
  $q .= " ON ";
  $q .= $wpdb->prefix."postmeta.post_id = ";
  $q .= $wpdb->prefix."posts.id ";
  $q .= " WHERE ";
  $q .= $wpdb->prefix."postmeta.meta_key = '_sku' ";
  $q .= " AND ".$wpdb->prefix."postmeta.meta_value = '".$sku."' ";
  $q .= " AND ".$wpdb->prefix."posts.post_status = 'publish' ";
  $t = $wpdb->get_results($q, ARRAY_A);
  $url = "";
  foreach ($t as $r) {
    $qm = "SELECT ";
    $qm .= $wpdb->prefix."postmeta.post_id ";
    $qm .= ",".$wpdb->prefix."postmeta.meta_key ";
    $qm .= ",".$wpdb->prefix."postmeta.meta_value ";
    $qm .= " FROM ";
    $qm .= $wpdb->prefix."postmeta ";
    $qm .= " WHERE ";
    $qm .= $wpdb->prefix."postmeta.meta_key='_lwam_front_image_url' ";
    $qm .= " AND ".$wpdb->prefix."postmeta.post_id = ".$r["post_id"];
    $tm = $wpdb->get_results($qm,ARRAY_A);
    foreach ($tm as $rm) {
      if ($rm["meta_value"] != "") {
        $url = $rm["meta_value"];
      }
    }
  }
  $return = array(
     "sku" => $sku,
     "url" => $url,
  );
  print json_encode($return);
?>
