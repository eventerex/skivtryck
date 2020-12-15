<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
  //------------------------------------------------
  // lwam_shoppingcart.php - Shopping cart functions
  // Copyright (c) Lightweb
  //------------------------------------------------

  //-------------------------------------
  // Display quantity BEFORE product name
  //-------------------------------------
  function add_sku_in_cart( $title, $values, $cart_item_key ) {
    $quantity = $values['quantity'];
    $title = '<b>'.$quantity.'</b> x '.$title;
    return $title;
  } 
  add_filter( 'woocommerce_cart_item_name', 'add_sku_in_cart', 20, 3);
  //------------------
  // Empty cart button
  //------------------
  function lwam_empty_cart() {
    global $woocommerce;
    $woocommerce->cart->empty_cart();
  }
  add_action('wp_ajax_nopriv_lwam_empty_cart','lwam_empty_cart');
  add_action('wp_ajax_lwam_empty_cart','lwam_empty_cart');
  function lwam_empty_cart_button() {
    global $woocommerce;
    if (is_cart() || is_checkout()) {
      print "<script>\n";
      print "jQuery(document.body).on('updated_cart_totals',function(){\n";
      print "  lwam_empty_cart_button();\n";
      print "});\n";
      print "jQuery(document.body).on('updated_wc_div',function(){\n";
      print "  lwam_empty_cart_button();\n";
      print "});\n";
      print "jQuery(document.body).on('updated_checkout',function(){\n";
      print "  lwam_empty_cart_button();\n";
      print "});\n";
      print "jQuery(document).ready(function() {\n";
      print "  lwam_empty_cart_button();\n";
      print "});\n";
      print "function lwam_empty_cart_button() {\n";
      $text = get_option("lwam_text_under_empty_cart");
      print "  button = jQuery('#lwam_empty_cart_button');\n";
      print "  if (button.length == 0) {\n";
      print "    jQuery('tr.order-total').after(";
      print "      '<tr>";
      print "         <td colspan=\"2\">";
      print "           <button id=\"lwam_empty_cart_button\" class=\"button lwam_empty_cart_button\" onclick=\"lwam_empty_cart();return false;\">";
      print "             ".__("Töm varukorgen","lwattentionmedia");
      print "           </button>";
    // Try to use customer country if available.
    $ctry = "";
    if ( method_exists( WC()->customer, 'get_billing_country' ) &&
      ! empty( WC()->customer->get_billing_country() ) &&
      strlen( WC()->customer->get_billing_country() ) === 2
    ) {
      $ctry = "cust: ".WC()->customer->get_billing_country( 'edit' );
    }
    $base_location = wc_get_base_location();
    $ctry = "base: ".$base_location['country'];

    //print "<br>".$ctry;
      if (!empty($text)) {
        print "<br>";
        print $text;
      }
      print "         </td>";
      print "       </tr>'\n";
      print "    );\n";
      print "  }\n";
      print "}\n";
      print "function lwam_empty_cart() {\n";
      print "  jQuery.post(\n";
      print "    '".admin_url('admin-ajax.php')."',{\n";
      print "      'action': 'lwam_empty_cart',\n";
      print "    },\n";
      print "    function(){\n";
      print "      window.location.href = '".$woocommerce->cart->get_checkout_url()."';\n";
      print "    }\n";
      print "  );\n";
      print "}\n";
      print "</script>\n";
    }
  }
  add_action( 'wp_footer','lwam_empty_cart_button',1000);
  //==========================
  // Mecka mecka med cart icon
  //==========================
  function lwam_menucart_item_count() {
    global $woocommerce;
    print "<script>\n";
    print "jQuery(document).ready(function() {\n";
    print "  lwam_menucart_item_count();\n";
    print "});\n";
    print "function lwam_menucart_item_count() {\n";
    print "  jQuery('.et-cart-info').attr('href','".$woocommerce->cart->get_checkout_url()."');\n";
    print "  jQuery('.et-cart-info > span').html('');\n";
    print "  var data = {\n";
    print "    action: 'lwam_menucart_get_item_count',\n";
    print "  };\n";
    print "  jQuery.ajax({\n";
    print "    url: '".admin_url('admin-ajax.php')."',\n";
    print "    data: data,\n";
    print "    dataType: 'json',\n";
    print "    success: function(result) {\n";
    print "      var numberlines = result.numberlines;\n";
    print "      jQuery('.et-cart-info > span').html(numberlines);\n";
    print "    }\n";
    print "  });\n";
    print "}\n";
    print "</script>\n";
    if (is_product()) {
      print "<script>\n";
      print "jQuery(document).ready(function() {\n";
      print "  jQuery('.button.wc-forward').attr('href','".$woocommerce->cart->get_checkout_url()."');\n";
      print "});\n";
      print "</script>\n";
    }
  }
  add_action( 'wp_footer','lwam_menucart_item_count',1010);
  function lwam_hidemenucart_count() {
    print "<script>\n";
    print "jQuery(document).ready(function() {\n";
    print "  jQuery('.et-cart-info > span').html('');\n";
    print "});\n";
    print "</script>\n";
  }
  add_action('wp-footer','lwam_hidemenucart_count',20);
  //-----------------------------------
  // Backend to count items in some way
  //-----------------------------------
  function lwam_menucart_get_item_count() {
    global $woocommerce;
    $cart = WC()->cart->get_cart();
    $numberlines = 0;
    foreach ($cart as $key => $value) {
      $thisitem = WC()->session->get('lwam'.$key,array());
      if (count($thisitem) > 0) {
        $numberlines++;
      }
    }
    $return = array(
      'numberlines' => $numberlines,
    );
    print json_encode($return);
    die();
  }
  add_action('wp_ajax_nopriv_lwam_menucart_get_item_count','lwam_menucart_get_item_count');
  add_action('wp_ajax_lwam_menucart_get_item_count','lwam_menucart_get_item_count');

  //===================
  // CART ITEM QUANTITY
  //===================
  function lwam_woocommerce_cart_item_quantity($product_quantity,$cart_item_key) {
    global $woocommerce;
    $return = "";
    // Now shall we see here... we need to do what? Find all the stafflings IF this is a main product. Grah.
    // ..and hmmm yes we need to not allow changing of the add-on quantity. This'll be very exciting.
    //------------------------------------------
    // Find out if this product is an add-on....
    //------------------------------------------
    // if WC()->session['lwam'.$cart_item_key] is not an array, we should no be able change quantity lah
    $thisitem = WC()->session->get('lwam'.$cart_item_key,array());
    if (count($thisitem) < 1) {
      $product_quantity = '';
    }
    return $product_quantity;
  }
  add_filter('woocommerce_cart_item_quantity','lwam_woocommerce_cart_item_quantity',10,2);
  //-----------------------------------------------------
  // Save the linked items and old cart keys to the order
  //-----------------------------------------------------
  add_action('woocommerce_thankyou','lwam_copy_cartmeta_order',20);
  function lwam_copy_cartmeta_order($order_id) {
    // The cart still exists here the first time around. We could match by product id and quantity with 
    // the cart item? No, we match by line number, THIS WORKS!
    // Get order and loop items
    $mycart = WC()->cart->get_cart();
    $order = wc_get_order($order_id);
    $linenumber = 1;
    foreach ($order->get_items() as $item_id => $item_product) {
      $cartline = 1;
      foreach ($mycart as $key => $value) {
        if ($cartline == $linenumber) {
          // Save the linked stuffs
          $lwam_linked = $value["lwam_linked"];
          // Save the current cart item key to the order
          $lwam_cart_item_key = $key;
          wc_add_order_item_meta($item_id,'lwam_linked',$lwam_linked,true);
          wc_add_order_item_meta($item_id,'lwam_cart_item_key',$lwam_cart_item_key,true);
        }
        $cartline++;
      }
      $linenumber++;
    }
  }
   
  //-------------------------------------------------------------------------
  // Add linked items information to order meta - THIS FUNCTION IS DEPRECATED
  //-------------------------------------------------------------------------
  add_action('woocommerce_checkout_create_order_line_item','lwam_add_order_item_meta',10,4);
  function lwam_add_order_item_meta($item, $cart_item_key,$values,$order) {
     if (isset($values["lwam_linked"])) { $item->update_meta_data('lwam_linked',$item["lwam_linked"]); }
     if (isset($values["lwam_cart_item_key"])) { $item->update_meta_data('lwam_cart_item_key',$item["lwam_cart_item_key"]); }
  }
  //----------------------------------------------------------------------------------------
  // New function to add linked items information to each order item replaces function above
  //----------------------------------------------------------------------------------------
  add_action('woocommerce_new_order_item', 'lwam_saveMetaData', 10, 3); // or use just 2 instead of 3; if you don't need order id
  function lwam_saveMetaData($itemId, $item, $orderId) {
    if (isset($item->legacy_values['lwam_linked'])) {
      wc_add_order_item_meta($itemId, 'lwam_linked', $item->legacy_values['lwam_linked']);
    }
    if (isset($item->legacy_values['lwam_cart_item_key'])) {
      wc_add_order_item_meta($itemId, 'lwam_cart_item_key', $item->legacy_values['lwam_cart_item_key']);
    }
  }
  //====================
  // ADDING ITEM TO CART
  //====================
  //-------------------------------------------------------------------------------------------
  // Add a hidden field before the add to cart button to indicate that there are extra products
  //-------------------------------------------------------------------------------------------
  add_action('woocommerce_before_add_to_cart_button', 'lwam_product_extra_item_indicator');
  function lwam_product_extra_item_indicator () {
    echo "<input id='_add_additional_item' type='hidden' value='1' name='_add_additional_item' />";
  }
  //-------------------------------------------------------------------------
  // Handle adding additional item(s) to the cart when add-to-cart is clicked
  //-------------------------------------------------------------------------
  add_action('woocommerce_add_to_cart', 'lwam_add_additional_item_to_cart');
  function lwam_add_additional_item_to_cart ($cart_item_key) {
    global $woocommerce;
    if (isset($_POST['_add_additional_item'])) {
      //-----------------------------------------------------------
      // Here we unset the hidden field to not get an infinite loop
      //-----------------------------------------------------------
      unset($_POST['_add_additional_item']);
      lwam_add_extra_to_cart_action($cart_item_key);
    }
  }
  // Allow adding of same product multiple times but on different lines
  function lwam_force_individual_cart_items( $cart_item_data, $product_id ) {
    $unique_cart_item_key = md5( microtime() . rand() );
    $cart_item_data['unique_key'] = $unique_cart_item_key;
    return $cart_item_data;
  }
  add_filter( 'woocommerce_add_cart_item_data', 'lwam_force_individual_cart_items', 10, 2 );
  //------------------------------------------------
  // Add extra to cart - Does not run on order again
  //------------------------------------------------
  function lwam_add_extra_to_cart_action($cart_item_key) {
    global $woocommerce;
    global $product;
    global $sitepress;
    //-------------------------------
    // Array to hold the linked items
    //-------------------------------
    $lwamitems = array();
    //------------------------------------------------------------
    // This array holds the prices for each item, we set the price
    // options that follow the mail item quantity to zero and add
    // all those to the price of the main item instead
    //------------------------------------------------------------
    $lwamprices = WC()->session->get('lwamprices',array());
    $lwamquantities = WC()->session->get('lwamquantities',array());
    //-------------------------------
    // Check if we are adding to cart
    //-------------------------------
    if (isset($_REQUEST['add-to-cart'])) {
      $item = WC()->cart->get_cart_item($cart_item_key);
      $product_id = $item['data']->get_id();
      $quantity = $item['quantity'];
      $original_id = $product_id;
      $price = lwam_shoppingcart_getprice($original_id,$quantity);
      //------------------------------------------
      // Add a record (an array) for the main item
      //------------------------------------------
      $option = array(
        "key" => $cart_item_key,
        "product_id" => $product_id,
        "price" => $price,
        "quantity" => $quantity,
        "lwampo_opt" => 0,
        "processed" => 0
      );
      array_push($lwamitems,$option);
      //---------------------------------------------------------
      // First we add singular products that follow main quantity
      //---------------------------------------------------------
      if (isset($_REQUEST["lw_opt"])) {
        $options = $_REQUEST["lw_opt"];
        foreach ($options as $option_product_id=>$option_quantity) {
          $option = lwam_shoppingcart_addoption ($option_product_id, $option_quantity);
          if (is_array($option) && count($option) > 0) {
            $lwamitems[] = $option;
          }
        }
      }
      //-----------------------------------------------------------
      // Second, we add multiple products that follow main quantity
      //-----------------------------------------------------------
      if (isset($_REQUEST['lw_opt_multi'])) {
        $options = $_REQUEST["lw_opt_multi"];
        foreach ($options as $option_sequence_no=>$option_product_id) {
          $option = lwam_shoppingcart_addmultioption ($original_id,$option_product_id, $quantity);
          if (is_array($option) && count($option) > 0) {
            $lwamitems[] = $option;
          }
        }
      }
      //-------------------------------------------------
      // Third, we add singular products with quantity 1
      // This does NOT get added to any array of any sort
      //-------------------------------------------------
      if (isset($_REQUEST["lw_opt"])) {
        $options = $_REQUEST["lw_opt"];
        foreach ($options as $key=>$value) {
          if ($value != 0) {
            $lwampo_opt = 0;
            if ($value == 1) {
              $lwampo_opt = 1;
            }
            if ($lwampo_opt == 1) {
              $custom_price = lwam_shoppingcart_getprice($key,1);
              $original_id = $key;
              $optionkey = WC()->cart->add_to_cart($key,$value);
              // Here must we set price to customprice for the just added
              // product. Grah.
              $lwamprices[$optionkey] = $custom_price;
            }
          }
        }
      }
      //-------------------------------------------------
      // Fourth, we add multiple products with quantity 1
      // This does NOT get added to any array of any sort
      //-------------------------------------------------
      if (isset($_REQUEST['lw_opt_multi'])) {
        $prod_id = $_REQUEST['add-to-cart'];
        //---------------------------------------------------------------
        // OK, we now need to figure out how many we want of this product
        // which is set in the lwampo option for this one. Maybe we have
        // to read those? The product_id is in the value
        //---------------------------------------------------------------
        $optionaddons = get_post_meta( $prod_id, '_lwampo', true );
        $optionjson = html_entity_decode($optionaddons);
        $optionjson = json_decode($optionjson);
        $options = $_REQUEST['lw_opt_multi'];
        foreach ($options as $key=>$value) {
          if ($value != 0) {
            for($i = 0, $size = count($optionjson); $i < $size; $i++) {
              if (in_array($value,$optionjson[$i]->lwampo_prod)) {
                $qty_type = $optionjson[$i]->lwampo_opt;
                //-------------------------
                // 0 = follow main quantity
                // 1 = Quantity is 1
                //-------------------------
                $qty = $_REQUEST["quantity"];
                if ($qty_type == 1) {
                  $qty = 1;
                }
                if ($qty_type == 1) {
                  $custom_price = lwam_shoppingcart_getprice($value,1);
                  //----------------------------------
                  // This price is in CURRENT currency
                  //----------------------------------
                  $original_id = $value;
                  $optionkey = WC()->cart->add_to_cart($value,$qty);
                }
              }
            }
          }
        }
      }
      //----------------------------------------------------------------------------
      // We will use WC()->session->set($key, $ ); to save an array of cart IDs that
      // are linked to this here ID that we have created oledi
      // $lwamitems has the items that are linked to this item
      //----------------------------------------------------------------------------
      if (is_array($lwamitems) && count($lwamitems) > 0) {
        WC()->session->set('lwam'.$cart_item_key,$lwamitems);
        $thisprice = 0;
        foreach ($lwamitems as $item) {
          $thisprice += $item["price"];
          $lwamprices[$item["key"]] = 0;
          $lwamquantities[$item["key"]] = $quantity;
        }
        $lwamprices[$cart_item_key] = $thisprice;
        $lwamquantities[$cart_item_key] = $quantity;
        WC()->session->set('lwamprices',$lwamprices);
        WC()->session->set('lwamquantities',$lwamquantities);
        //--------------------------------------------------
        // It is here we want to set the cart item data!!!!!
        //--------------------------------------------------
        $thiscart = WC()->cart->cart_contents;
        foreach($thiscart as $thiscart_item_key=>$thiscart_item) {
          if ($thiscart_item_key == $cart_item_key) {
            $thiscart_item['lwam_linked'] = $lwamitems;
            //$thiscart_item['lwam_cart_item_key'] = $cart_item_key;
            WC()->cart->cart_contents[$thiscart_item_key] = $thiscart_item;
          }
        }
        WC()->cart->set_session();
      }
      $thiscart = WC()->cart->cart_contents;
      foreach($thiscart as $thiscart_item_key=>$thiscart_item) {
        $thiscart_item['lwam_cart_item_key'] = $thiscart_item_key;
        WC()->cart->cart_contents[$thiscart_item_key] = $thiscart_item;
      }
      WC()->cart->set_session();
    }
  }
  //=======================
  // DELETE ITEMS FROM CART
  //=======================
  //-------------------------------------------------------
  // Handle delete of products that are inextricably linked
  //-------------------------------------------------------
  function lwam_remove_linked_items() {
    global $woocommerce;
    $lwamprices = WC()->session->get('lwamprices',array());
    $lwamquantities = WC()->session->get('lwamquantities',array());
    if (isset($_REQUEST['remove_item'])) {
      $thiskey = 'lwam'.$_REQUEST['remove_item'];
      $thisdata = WC()->session->get($thiskey);
      if (is_array($thisdata) && count($thisdata) > 0) {
        foreach ($thisdata as $thisitem) {
          $cart_item_key = $thisitem["key"];
          // The below line may not work, so we try the one below that
          // unset (WC()->cart->cart_contents[$cart_item_key]);
          WC()->cart->remove_cart_item($cart_item_key);
          unset ($lwamquantities[$cart_item_key]);
          unset ($lwamprices[$cart_item_key]);
        }
      }
      // We must remove the item also from my special session. This is really annoying.
      $blank = [];
      WC()->session->set('lwam'.$_REQUEST['remove_item'],$blank);
    }
  }
  add_action( 'woocommerce_cart_updated', 'lwam_remove_linked_items', 10, 2 );
  //===================
  // SET PRICES IN CART
  //===================
  //-------------------------------------------
  // Hooking in the main price setting function
  //-------------------------------------------
  add_action( 'woocommerce_before_calculate_totals', 'lwam_add_custom_price', 1000, 1);
  function lwam_add_custom_price($cart_obj) {
    //------------------------------
    // This is necessary for WC 3.0+
    //------------------------------
    if (is_admin() && ! defined('DOING_AJAX'))
      return;
    //-------------------------------------------
    // Get prices and quantities from the session
    //-------------------------------------------
    $lwamprices = WC()->session->get('lwamprices',array());
    $lwamquantities = WC()->session->get('lwamquantities',array());
    foreach ($cart_obj->get_cart() as $key => $value) {
      //------------------------------------------------------------------------------------
      // If we are processing a 'normal order' we get prices and quantities from the session
      //------------------------------------------------------------------------------------
      if (array_key_exists($key,$lwamprices)) {
        $value["data"]->set_price($lwamprices[$key]);
      }
      if (array_key_exists($key,$lwamquantities)) {
        $cart_obj->cart_contents[$key]['quantity'] = $lwamquantities[$key];
      }
    }
  }
  //================
  // CHANGE QUANTITY
  //================
  //---------------------------------------------------------------------
  // Handle quantity change JULY 2018
  // We must recalculate the prices after changing the quantity... grargh
  //---------------------------------------------------------------------
  add_action( 'woocommerce_after_cart_item_quantity_update', 'lwam_change_cart_quantity', 1, 4 );
  function lwam_change_cart_quantity( $cart_item_key,$quantity,$old_quantity,$cart ){
    global $woocommerce;
    global $woocommerce_wpml;
    global $sitepress;
    //-------------------------------------------------------------------------
    // To avoid recursive looping, this evil fiend must be temporarily disabled
    //-------------------------------------------------------------------------
    remove_action( 'woocommerce_after_cart_item_quantity_update', 'lwam_change_cart_quantity', 1 );
    $mycart = WC()->cart->get_cart();
    $lwamprices = WC()->session->get('lwamprices',array());
// WPML removed    $current_currency = apply_filters('wcml_price_currency', NULL );
    // if( ! is_cart() ) return; // Only on cart page - this is stupid, the hook only runs when updating cart quantity. Ha.
    $thiskey = 'lwam'.$cart_item_key;
    $thisdata = WC()->session->get($thiskey,array());
    //-------------------------------
    // Adjust price based on quantity
    //-------------------------------
    foreach( $mycart as $ncart_item_key => $cart_item ){
      if ($ncart_item_key == $cart_item_key) {
        $original_id = $cart_item['product_id'];
        $thisdata = $cart_item["lwam_linked"];
        if (!empty($thisdata)) {
          if (!is_array($thisdata)) {
            $thisdata = unserialize($thisdata);
          }
        }
      }
    }
    //---------------------------------------------------------
    // Get the price for the main item in the original language
    //---------------------------------------------------------
    $_product = wc_get_product( $original_id );
    //----------------------------------
    // This price is in CURRENT currency
    //----------------------------------
    $custom_price = $_product->get_price();
    //-----------------------------------------
    // Get the price based on the cart quantity
    //-----------------------------------------
    $custom_price = lwam_shoppingcart_getprice($original_id,$quantity,$custom_price,$current_currency);
    //---------------------------
    // Adjust to current currency
    //---------------------------
    $custom_price = apply_filters( 'wcml_raw_price_amount', $custom_price, $current_currency);
    //----------------------------------
    // Does this item have linked items?
    //----------------------------------
    if (is_array($thisdata) && count($thisdata) > 0) {
      $thisindex = 0;
      foreach ($thisdata as $thisitem) {
        foreach ($mycart as $ncart_item_key => $cart_item ) {
          if ($ncart_item_key == $thisitem["key"]) {
            // Do some tricksy tricksy with the pricing. I am so clever
            if ($thisitem["product_id"] != $original_id) {
              $moreid = $thisitem["product_id"];
              $moreproduct = wc_get_product($moreid);
              $moreprice = $moreproduct->get_price();
              $moreprice = lwam_shoppingcart_getprice($moreid,$quantity,$moreprice,$current_currency);
              $custom_price = $custom_price + $moreprice;
              $cart_item["data"]->set_price(0);
              $lwamprices[$ncart_item_key] = 0;
              $lwamquantities[$ncart_item_key] = $quantity;
              WC()->cart->set_quantity( $ncart_item_key, $quantity, false  );
              $cart->cart_contents[ $ncart_item_key ]['quantity'] = $quantity;
            } else {
              WC()->cart->set_quantity( $ncart_item_key, $quantity, false  );
              $cart->cart_contents[ $ncart_item_key ]['quantity'] = $quantity;
              $lwamquantities[$ncart_item_key] = $quantity;
            }
          }
        }
        $thisdata[$thisindex]["quantity"] = $quantity;
        $thisindex++;
      }
      WC()->session->set($thiskey,$thisdata);
    } 
    foreach($mycart as $ncart_item_key => $cart_item ){
      if ($ncart_item_key == $cart_item_key) {
        $cart_item["data"]->set_price($custom_price);
        $lwamprices[$ncart_item_key] = $custom_price;
        $lwamquantities[$ncart_item_key] = $quantity;
      }
    }
    //---------------------------------------------------------
    // DO NOT FORGET THE lwam_prices array, must be updated!!!!
    //---------------------------------------------------------
    WC()->session->set('lwamprices',$lwamprices);
    WC()->session->set('lwamquantities',$lwamquantities);
    //------------------------
    // Enable the sucker again
    //------------------------
    add_action( 'woocommerce_after_cart_item_quantity_update', 'lwam_change_cart_quantity', 1, 4 );
  }
  //-----------------------------------------------------------------------------------
  // Add linked items to cart_item_data (i.e. get from session, add to cart item object
  //-----------------------------------------------------------------------------------
  add_filter('woocommerce_get_cart_item_from_session', function ($cartItemData, $cartItemSessionData, $cartItemKey ) {
    if ( isset( $cartItemSessionData['lwam_linked'] ) ) {
      $cartItemData['lwam_linked'] = $cartItemSessionData['lwam_linked'];
    }
    if ( isset( $cartItemSessionData['lwam_cart_item_key'] ) ) {
      $cartItemData['lwam_cart_item_key'] = $cartItemSessionData['lwam_cart_item_key'];
    }
    return $cartItemData;
  }, 10, 3 );
  //==================
  // GENERAL FUNCTIONS
  //==================
  //-------------------------------------------------------------------------------------------
  // Hide stuff:
  // - Hide the delete buttons for add-on products (they are only deleted with the main product)
  // - Hide prices for add-on products
  // - Hide images for add-on products
  //-------------------------------------------------------------------------------------------
  function lwam_hide_options_stuff() {
    global $woocommerce;
    if (is_cart() || is_checkout()) {
      //--------------------------------------------------------------------
      // OK, we must loop the cart and then we must check our session thingy
      // and build an array or list w the values we want to hide buttons for
      //--------------------------------------------------------------------
      $idarraystring = "";
      $idarraysep = "";
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        //-----------------------------------------------------------------------------
        // In the session, we should have saved the cart keys for all products that are
        // linked to another cart key. We will then use this for hiding options for
        // those linked products
        //-----------------------------------------------------------------------------
        $thiskey = 'lwam'.$cart_item_key;
        $thisdata = WC()->session->get($thiskey,array());
        if (is_array($thisdata)) {
          foreach($thisdata as $thisitem) {
            if ($thisitem["key"] != $cart_item_key) {
              $idarraystring .= $idarraysep."'".$thisitem["key"]."'";
              $idarraysep = ",";
            }
          }
        }
      }
      //---------------
      // Start a script
      //---------------
      print "<script>\n";
      print "jQuery(document).ready(function(){\n";
      print "  lwam_hideStuff();\n";
      print "});\n";
      print "jQuery(document.body).on('updated_cart_totals',function(){\n";
      print "  lwam_hideStuff();\n";
      print "});\n";
      print "jQuery(document.body).on('updated_wc_div',function(){\n";
      print "  lwam_hideStuff();\n";
      print "});\n";
      print "jQuery(document.body).on('updated_checkout',function(){\n";
      print "  lwam_hideStuff();\n";
      print "});\n";
      print "function lwam_hideStuff() {\n";
      print "  var idarray = [".$idarraystring."];\n";
      print "  var idlength = idarray.length;\n";
      print "  if (idlength > 0) {\n";
      print "    for (var i=0;i<idlength;i++) {\n";
      print "      jQuery('input[name*=\"'+idarray[i]+'\"').each(function() {\n";
      print "        jQuery(this).hide();\n";
      print "        jQuery(this).closest('tr').find('.wp-post-image').hide();\n";
      print "        jQuery(this).closest('tr').find('.woocommerce-Price-amount').hide();\n";
      print "        jQuery(this).closest('tr').find('.sku').hide();\n";
      print "      });\n";
      print "    }\n";
      print "  }\n";
      print "}\n";
      print "</script>\n";
    }
  }
  add_action( 'wp_footer','lwam_hide_options_stuff',1000);
  //--------------------------------------------------
  // OK, let's have ONE function only to get the price
  // Should return it in the original currency I think
  //--------------------------------------------------
  function lwam_shoppingcart_getprice($post_id,$quantity) {
    global $woocommerce;
    global $sitepress;
    $original_id = $post_id;
    $_product = wc_get_product( $original_id );
    $custom_price = $_product->get_price();
    //----------------------------------
    // See if we have any custom pricing
    //----------------------------------
    if (get_post_meta( $post_id, '_lwamfq', true )) {
      $fixed_quantity = get_post_meta( $original_id, '_lwamfq', true );
      $fixed_json = html_entity_decode($fixed_quantity);
      $fixed_json = json_decode($fixed_json);
      if (is_array($fixed_json)) {
        for($i = 0, $size4 = count($fixed_json); $i < $size4; $i++) {
          $thisqty = (int)$fixed_json[$i]->lwamfq_qty;
          if ($thisqty <= $quantity) {
            //-------------------------------
            // This price is in BASE currency
            //-------------------------------
            $custom_price = $fixed_json[$i]->lwamfq_price;
          }
        }
      }
    }
    return $custom_price;
  }
  //--------------------------------------------------------------
  // Get the values for an add-on option single following quantity
  //--------------------------------------------------------------
  function lwam_shoppingcart_addoption ($option_product_id, $option_quantity) {
    global $woocommerce;
    global $sitepress;
    $thisoption = array();
    if ($option_quantity != 0) {
      $lwampo_opt = 0;
      if ($option_quantity == 1) {
        $lwampo_opt = 1;
      }
      if ($lwampo_opt == 0) {
        $custom_price = lwam_shoppingcart_getprice($option_product_id,$option_quantity);
// WPML removed        $key = icl_object_id($option_product_id, 'product', true, $sitepress->get_default_language());
// replaced by next line
        $key = $option_product_id;
        $optionkey = WC()->cart->add_to_cart($option_product_id,$option_quantity);
        $thisoption = array(
          "key" => $optionkey,
          "product_id" => $option_product_id,
          "price" => $custom_price,
          "quantity" => $option_quantity,
          "lwampo_opt" => $lwampo_opt,
          "processed" => 0
        );
      }
    }
    return $thisoption;
  }
  //-------------------------------------------------------------
  // Get the values for an add-on option multi following quantity
  //-------------------------------------------------------------
  function lwam_shoppingcart_addmultioption ($original_product_id,$option_product_id,$option_quantity) {
    global $woocommerce;
    global $sitepress;
    $thisoption = array();
    $optionaddons = get_post_meta($original_product_id, '_lwampo', true );
    $optionjson = html_entity_decode($optionaddons);
    $optionjson = json_decode($optionjson);
    if ($option_product_id != 0) {
      $quantity = $option_quantity;
      for($i = 0, $size = count($optionjson); $i < $size; $i++) {
        if (in_array($option_product_id,$optionjson[$i]->lwampo_prod)) {
          $qty_type = $optionjson[$i]->lwampo_opt;
          //-------------------------
          // 0 = follow main quantity
          // 1 = Quantity is 1
          //-------------------------
          if ($qty_type == 1) {
            $quantity = 1;
          }
          if ($qty_type == 0) {
            $custom_price = lwam_shoppingcart_getprice($option_product_id,$quantity);
            //----------------------------------
            // This price is in CURRENT currency
            //----------------------------------
            $optionkey = WC()->cart->add_to_cart($option_product_id,$quantity);
            $thisoption = array(
              "key" => $optionkey,
              "product_id" => $option_product_id,
              "price" => $custom_price,
              "quantity" => $quantity,
              "lwampo_opt" => $qty_type,
              "processed" => 0
            );
          }
        }
      }
    }
    return $thisoption;
  }
  //================
  // DEBUG FUNCTIONS
  //================
  function lwam_debug_print($debugtext) {
    $debugstring = print_r($debugtext, true);
    $debugstring = date('Y-m-d h:i')." ".$debugstring;
    $myfile = file_put_contents('/tmp/lwam_log.txt',$debugstring.PHP_EOL,FILE_APPEND);
  }
?>
