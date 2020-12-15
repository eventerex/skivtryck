<?php
  function lwam_admin_order_meta($order){
    echo '<p><strong>'.__('Albumtitel',"lwattentionmedia").':</strong> <br/>' . get_post_meta( $order->get_id(), 'lwam_albumtitel', true ) . '</p>';
    echo '<p><strong>'.__('Artistnamn',"lwattentionmedia").':</strong> <br/>' . get_post_meta( $order->get_id(), 'lwam_artistnamn', true ) . '</p>';
  }
  add_action('woocommerce_admin_order_data_after_billing_address', 'lwam_admin_order_meta', 10, 1);

 
  function lwam_view_order_and_thankyou_page($order_id) {
    print "<table class=\"woocommerce-table shop_table\">\n";
    print "  <thead>\n";
    print "    <span class=\"woocommerce-column__title\" style=\"font-size: 26px;\">Produktion</span>\n";
    print "  </thead>\n";
    print "  <tbody>\n";
    print "    <tr>\n";
    print "      <th>".__("Artistnamn","lwattentionmedia")."</th>\n";
    print "      <td>".get_post_meta($order_id,'Artistnamn', true )."</td>\n";
    print "    </tr>\n";
    print "    <tr>\n";
    print "      <th>".__("Albumtitel","lwattentionmedia")."</th>\n";
    print "      <td>".get_post_meta($order_id,'Albumtitel', true )."</td>\n";
    print "    </tr>\n";
    print "  </tbody>\n";
    print "</table>\n";
  }
  add_action( 'woocommerce_thankyou', 'lwam_view_order_and_thankyou_page', 20 );
  add_action( 'woocommerce_view_order', 'lwam_view_order_and_thankyou_page', 20 );

  add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'change_formatted_meta_data', 20, 2 );
  function change_formatted_meta_data( $meta_data, $item ) {
    $new_meta = array();
    foreach ( $meta_data as $id => $meta_array ) {
      // We are removing the meta with the key 'something' from the whole array.
      if ( 'lwam_cart_item_key' === $meta_array->key ) { continue; }
      $new_meta[ $id ] = $meta_array;
    }
    return $new_meta;
  }
?>
