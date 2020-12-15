<?php
  //----------------------------------
  // Show order meta fields in e-mails
  //----------------------------------
  function lwam_woocommerce_email_before_order_table($order,$sent_to_admin,$plain_text,$email) {
    global $woocommerce;
    echo '<p><strong>'.__('Albumtitel',"lwattentionmedia").'</strong> <br/>' . get_post_meta( $order->get_id(), 'Albumtitel', true ) . '</p>';
    echo '<p><strong>'.__('Artistnamn',"lwattentionmedia").'</strong> <br/>' . get_post_meta( $order->get_id(), 'Artistnamn', true ) . '</p>';
  }
  add_action('woocommerce_email_before_order_table','lwam_woocommerce_email_before_order_table',10,4);

  function lwam_albumtitel_checkout_field( $checkout ) {
    woocommerce_form_field(
      'lwam_albumtitel',
      array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Albumtitel',"lwattentionmedia" ),
        'placeholder' => __( 'Skriv albumtitel här',"lwattentionmedia" ),
        'required'    => false,
      ),
      $checkout->get_value( 'lwam_albumtitel' )
    );
  }
  add_action( 'woocommerce_after_order_notes', 'lwam_albumtitel_checkout_field' );

  function lwam_albumtitel_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['lwam_albumtitel'] ) ) {
      update_post_meta( $order_id, 'Albumtitel', sanitize_text_field( $_POST['lwam_albumtitel'] ) );
    }
  }
  add_action( 'woocommerce_checkout_update_order_meta', 'lwam_albumtitel_checkout_field_update_order_meta' );

  function lwam_artistnamn_checkout_field( $checkout ) {
    woocommerce_form_field(
      'lwam_artistnamn',
      array(
        'type'        => 'text',
        'class'       => array( 'form-row-wide' ),
        'label'       => __( 'Artistnamn',"lwattentionmedia" ),
        'placeholder' => __( 'Skriv artistnamn här',"lwattentionmedia" ),
        'required'    => false,
      ),
      $checkout->get_value( 'lwam_artistnamn' )
    );
  }
  add_action( 'woocommerce_after_order_notes', 'lwam_artistnamn_checkout_field' );

  function lwam_artistnamn_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['lwam_artistnamn'] ) ) {
      update_post_meta( $order_id, 'Artistnamn', sanitize_text_field( $_POST['lwam_artistnamn'] ) );
    }
  }
  add_action( 'woocommerce_checkout_update_order_meta', 'lwam_artistnamn_checkout_field_update_order_meta' );
  //------------------------------------------------------------------------
  // Function below possibly replaces woocommerce_checkout_update_order_meta
  //------------------------------------------------------------------------
  add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    $custom_fields = array(
      'lwam_artistnamn',
      'lwam_albumtitel',
    );

    foreach ( $custom_fields as $field_name ) {
      if ( isset( $data[ $field_name ] ) ) {
        $meta_key = '_' . $field_name;
        $field_value = $data[ $field_name ]; // WC will handle sanitation
        $order->update_meta_data( $meta_key, $field_value );
      }
    }
  }, 10, 2 );

  function lwam_hide_createaccount() {
    print "<script>\n";
    print "jQuery(document).ready(function() {\n";
    print "  var box = document.getElementById('createaccount');\n";
    print "  if (box) {\n";
    print "    box.checked = true;\n";
    print "    createp = document.getElementsByClassName('create-account');\n";
    print "    for (var i=0;i<createp.length;i++) {\n";
    print "      createp[i].style.display = 'none';\n";
    print "    }\n";
    print "  }\n";
    print "});\n";
    print "</script>\n";
  }
  add_action( 'wp_footer','lwam_hide_createaccount',1000);
?>
