<?php
  //--------------------------------------------
  // lwam_account.php - Functions for my account
  // Copyright (c) Eventerex
  //--------------------------------------------
  //---------------------------------------------------
  // Add columns for Artist and Production in My Orders
  //---------------------------------------------------
  function lwam_wc_add_my_account_orders_column( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $name ) {
      $new_columns[$key] = $name;
      // add ship-to after order status column
      if ( 'order-status' === $key ) {
        $new_columns['order-artist'] = __( 'Artist', 'lwattentionmedia' );
        $new_columns['order-production'] = __( 'Produktion', 'lwattentionmedia' );
      }
    }
    return $new_columns;
  }
  add_filter( 'woocommerce_my_account_my_orders_columns', 'lwam_wc_add_my_account_orders_column' );
  //-------------------------------
  // Add data for Production column
  //-------------------------------
  function lwam_wc_my_orders_production_column( $order ) {
    $order_id = $order->get_id();
    $album = get_post_meta( $order_id,'Albumtitel', true );
    echo $album;
  }
  add_action( 'woocommerce_my_account_my_orders_column_order-production', 'lwam_wc_my_orders_production_column' );
  //---------------------------
  // Add data for Artist column
  //---------------------------
  function lwam_wc_my_orders_artist_column( $order ) {
    $order_id = $order->get_id();
    $artist = get_post_meta( $order_id, 'Artistnamn', true );
    echo $artist;
  }
  add_action( 'woocommerce_my_account_my_orders_column_order-artist', 'lwam_wc_my_orders_artist_column' );
  //---------------------------------------------
  // Remove number of products from Amount column
  //---------------------------------------------
  function lwam_wc_my_orders_total_column( $order ) {
    $order_id = $order->get_id();
    $order_total = $order->get_formatted_order_total();
    echo $order_total;
  }
  add_action( 'woocommerce_my_account_my_orders_column_order-total', 'lwam_wc_my_orders_total_column' );

  //-------------------------------------
  // Add order again button to order view
  //-------------------------------------
  function lwam_add_order_again_button($order) {
    global $woocommerce;
    if (is_account_page()) {
      $order_id = 0;
      $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
      $template_name = strpos($url,'/order-received/') === false ? '/view-order/' : '/order-received/';
      if (strpos($url,$template_name) !== false) {
        $start = strpos($url,$template_name);
        $first_part = substr($url, $start+strlen($template_name));
        $order_id = substr($first_part, 0, strpos($first_part, '/'));
      }
      if ($order_id != 0) {
        //----------------------------------------
        // Get the 'antal' from the selected order
        //----------------------------------------
        $quantity = 1;
        $product_id = 0;
        $order = wc_get_order($order_id);
        //--------------------
        // Get the order items
        //--------------------
        $order_items = $order->get_items('line_item');
        foreach ($order_items as $order_item) {
          $lwam_linked = $order_item->get_meta('lwam_linked');
          if (!empty($lwam_linked)) {
            $product_id = $order_item->get_product_id();
            $quantity = $order_item->get_quantity();
          }
        }
        if ($product_id != 0) {
          $options = "";
          if (get_post_meta($product_id, '_lwamfq', true )) {
            $fixed_quantity = get_post_meta( $product_id, '_lwamfq', true );
            if ($fixed_quantity != null && $fixed_quantity != "null") {
              $fixed_json = html_entity_decode($fixed_quantity);
              $fixed_json = json_decode($fixed_json);
              for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
                $options .= "<option value=\"".$fixed_json[$i]->lwamfq_qty."\" ";
                if ($fixed_json[$i]->lwamfq_qty == $quantity) {
                  $options .= "selected";
                }
                $options .= ">".$fixed_json[$i]->lwamfq_desc."</option>\n";
              }
            }
          }
          print __("Antal:","lwattentionmedia")." <select id=\"order-again-quantity\">\n";
          print $options;
          print "</select>\n";
        } else {
          print "<input type=\"hidden\" id=\"order-again-quantity\">\n";
        }
        print "<button class=\"button\" onclick=\"lwam_orderagain();return false\">".__("Köp igen","lwattentionmedia")."</button>\n";
        print "<script>\n";
        print "function lwam_orderagain() {\n";
        print "  quantity = jQuery('#order-again-quantity').val();\n";
        print "  var data = {\n";
        print "    post_type: 'POST',\n";
        print "    action: 'lwam_order_again',\n";
        print "    order_id: ".$order_id.",\n";
        print "    orderagainquantity: quantity\n";
        print "  }\n";
        print "  jQuery.post(\n";
        print "    '".admin_url('admin-ajax.php')."',\n";
        print "    data,\n";
        print "    function(response){\n";
        print "      console.log(response);\n";
        print "      window.location.href = '".wc_get_checkout_url()."';\n";
        print "    }\n";
        print "  );\n";
        print "}\n";
        print "</script>\n";
      }
    }
  }
  add_action('woocommerce_order_details_after_order_table', 'lwam_add_order_again_button');
  //----------------------------------------------
  // Create a shopping cart based on the old order
  //----------------------------------------------
  function lwam_order_again() {
    global $wpdb;
    global $woocommerce;
    $response = array();
    $linkmap = array();
    $lwamprices = array();
    $lwamquantities = array();
    //----------------------
    // Super hard empty cart
    //----------------------
    WC()->cart->empty_cart(true);
    WC()->session->set('lwamprices',array());
    WC()->session->set('lwamquantities',array());
    $order_id = isset($_REQUEST["order_id"])?$_REQUEST["order_id"]:0;
    $newquantity = isset($_REQUEST["orderagainquantity"])?$_REQUEST["orderagainquantity"]:0;
    //--------------------------------------
    // Try getting the order the correct way
    //--------------------------------------
    $order = wc_get_order($order_id);
    //--------------------
    // Get the order items
    //--------------------
    $order_items = $order->get_items('line_item');
    foreach ($order_items as $order_item) {
      //-----------------------------
      // Get product etc. information
      //-----------------------------
      $product_id = $order_item->get_product_id();
      $quantity = $order_item->get_quantity();
      if ($quantity != 1) {
        if ($newquantity != 0) {
          $quantity = $newquantity;
        }
        $variation_id = 0;
        $variation = array();
        $cart_item_data = array();
        //--------------------------------------------------------------
        // We will assume that the cart item key will be the same always
        // for a given product, quantity and variation combination,
        // since we have not yet added our special sauce meta data yet
        //--------------------------------------------------------------
        $cart_item_key = WC()->cart->add_to_cart($product_id,$quantity, $variation_id,$variation,$cart_item_data);
        //------------------
        // Get the meta data
        //------------------
        $lwam_linked = $order_item->get_meta('lwam_linked');
        if (!empty($lwam_linked)) {
          $linkmap[] = $lwam_linked;
        }
        $lwam_quantities[$key] = $quantity;
        WC()->session->set('lwam'.$key,$lwam_linked);
      }
    }
    foreach ($linkmap as $linkpost) {
      foreach ($linkpost as $link) {
        $key = $link["key"];
        $price = $link["price"];
        $lwamprices[$key] = $price;
        $cart_item = WC()->cart->get_cart_item($key);
        $cart_item["data"]->set_price($price);
      }
    }

    //-----------------
    // Our session vars
    //-----------------
    WC()->session->set('lwamprices',$lwamprices);
    WC()->session->set('lwamquantities',$lwamquantities);
    //------------------------
    // Woocommerce session var
    //------------------------
    WC()->cart->set_session();
    echo json_encode($response);
    exit;
  }
  add_action( 'wp_ajax_lwam_order_again', 'lwam_order_again' );
  add_action( 'wp_ajax_nopriv_lwam_order_again', 'lwam_order_again' );
?>
