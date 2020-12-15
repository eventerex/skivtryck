<?php
  //---------------------------------------
  // lwam_specter.php - Modify Specter data
  // Copyright (c) Eventerex
  //---------------------------------------

  add_filter( 'wc_specter_published_comment', 'lwam_wc_specter_published_comment', 10, 2 );
  function lwam_wc_specter_published_comment( $published_comment, $order ) {
    global $woocommerce;
    $order_id = $order->get_id();
    $artist = get_post_meta( $order_id, 'Artistnamn', true );
    $album = get_post_meta( $order_id,'Albumtitel', true );
    // Remember that $published_comment contains info about coupon codes if used in the order.
    // Let's add the customer note to $published_comment so the possible coupon code info don't get overwritten.
    $published_comment .= wptexturize( $order->customer_note );
    $published_comment .= wptexturize( " ".$artist." ".$album);
    return $published_comment;
  }
?>
