<?php
//ini_set('display_startup_errors',1);
//ini_set('display_errors',1);
//error_reporting(-1);
  //----------------------------------------------
  // lwam_productpage.php - product page functions
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //----------------------------------------------
  //----------------------------------------------------------------------------------
  // This function is a bit of a hack, the plugin must be activated before woocommerce
  // otherwise it will not work.
  //----------------------------------------------------------------------------------
  function woocommerce_quantity_input($args = array(),$product = null, $echo = true) {
    if ( is_null( $product ) ) {
      $product = $GLOBALS['product'];
    }
    global $woocommerce;
    global $woocommerce_wpml;
    global $sitepress;
    //--------------
    // Buffer output
    //--------------
    ob_start();
    //--------------------------------------------------------------------------------
    // OK here's another problem. The product id is another product (translated). FOK.
    //--------------------------------------------------------------------------------
    $original_id = $product->get_id();
    //-----------------
    // Current currency
    //-----------------
    $defaults = array(
      'input_id'     => uniqid( 'quantity_' ),
      'input_name'   => 'quantity',
      'input_value'  => '1',
      'classes'      => apply_filters( 'woocommerce_quantity_input_classes', array( 'input-text', 'qty', 'text' ), $product ),
      'max_value'    => apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
      'min_value'    => apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
      'step'         => apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
      'pattern'      => apply_filters( 'woocommerce_quantity_input_pattern', has_filter( 'woocommerce_stock_amount', 'intval' ) ? '[0-9]*' : '' ),
      'inputmode'    => apply_filters( 'woocommerce_quantity_input_inputmode', has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'numeric' : '' ),
      'product_name' => $product ? $product->get_title() : '',
    );
    $args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );
    if (is_product()) {
      //----------------------------
      // Check if this is a dropdown
      //----------------------------
      $options = "";
      if (get_post_meta( $original_id, '_lwamfq', true )) {
        $fixed_quantity = get_post_meta( $original_id, '_lwamfq', true );
        if ($fixed_quantity != null && $fixed_quantity != "null") {
          $fixed_json = html_entity_decode($fixed_quantity);
          $fixed_json = json_decode($fixed_json);
          for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
            $options .= "<option value=\"".$fixed_json[$i]->lwamfq_qty."\" ";
            if ($fixed_json[$i]->lwamfq_default == 1) {
              $options .= "selected";
            }
            $options .= ">".$fixed_json[$i]->lwamfq_desc."</option>\n";
          }
        }
      }
      //----------------------------
      // If we have fixed quantities
      //----------------------------
      if ($options != "") {
        echo '<h2 class="product_title lwam_product_title">'.__('Quantity','woocommerce').'</h2>'."\n";
        echo '      <div class="quantity_select" style="' . $args['style'] . "\">\n";
        echo '        <select id="'.esc_attr($args['input_name']).'" name="' . esc_attr( $args['input_name'] ) . '" title="' . _x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ) . '" ';
        echo ' class="quantity_select';
        echo '"';
        echo '>'."\n";
        echo $options;
        echo '        </select>'."\n";
        //--------------------------------------------
        // Add the id to our list of items on the page
        //--------------------------------------------
        echo "<script>\n";
        echo "  lwam_ids.push('".esc_attr($args['input_name'])."');\n";
        echo "</script>\n";
      } else {
        //------------------------------------------------------------
        // Here, we should run the standard input - we have no options
        //------------------------------------------------------------
        $args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );
        // Apply sanity to min/max args - min cannot be lower than 0
        if ( '' !== $args['min_value'] && is_numeric( $args['min_value'] ) && $args['min_value'] < 0 ) {
          $args['min_value'] = 0; // Cannot be lower than 0
        }
        // Max cannot be lower than 0 or min
        if ( '' !== $args['max_value'] && is_numeric( $args['max_value'] ) ) {
          $args['max_value'] = $args['max_value'] < 0 ? 0 : $args['max_value'];
          $args['max_value'] = $args['max_value'] < $args['min_value'] ? $args['min_value'] : $args['max_value'];
        }
        wc_get_template( 'global/quantity-input.php', $args );
      }
      //-----------------
      // Optional add-ons
      //-----------------
      if (get_post_meta( $original_id, '_lwampo', true )) {
        echo "<div class='lwam_addons_div'>\n";
        $options_opt = "";
        $optionaddons = get_post_meta( $original_id, '_lwampo', true );
        $optionjson = html_entity_decode($optionaddons);
        $optionjson = json_decode($optionjson);
        for($i = 0, $size = count($optionjson); $i < $size; ++$i) {
          $obj = (object) $optionjson[$i];
          $hasno = 1;
          if (property_exists($obj,'lwampo_opt_hasno')) {
            if ($obj->lwampo_opt_hasno == 1) {
              $hasno = 0;
            }
          }
          //----------------------------------------------------------------------
          // opttype = 'S' (single product) or 'M' (multiple products in dropdown)
          //----------------------------------------------------------------------
          $opttype = "S";
          if (count($optionjson[$i]->lwampo_prod) == 1) {
            $opt_id = $optionjson[$i]->lwampo_prod[0];
            $opttype = "S";
          } else {
            //---------------------------------------------------------------------------------------------------
            // For multiple, we will have a different ID numbering, just sequential. They have anothe name and ID
            //---------------------------------------------------------------------------------------------------
            $opt_id = $i;
            $opttype = "M";
          } 
          //-----------------------------
          // Find the option product name
          //-----------------------------
          // $option_title = get_the_title($opt_id);
          // OK, we need to change here so that if not default language, we pick up the product name
          // in the translated language
          $option_title = $optionjson[$i]->lwampo_caption;
          //---------------------
          // Title for the option
          //---------------------
          echo '<div class="component-title-wrapper">'."\n";
          echo '<h2 class="lwam_product_title">';
          echo $option_title;
          echo '</h2>'."\n";
          echo '</div>'."\n";
          //----------------------------------------
          // Figure out which type of option this is
          //----------------------------------------
          // We get the allowed quantitites from the child product. This is not necessary
          // OK, here we need to think, I think a good way is to change the "Yes" option to whatever the main one is using Javascript
          // lwampo_opt = 0 means it follows main product's quantity
          // lwampo_opt = 1 means is always has quantity 1
          echo '<div class="quantity_select lwam_product_div" style="'.$args['style'].'">'."\n";
          //--------------------------------------------------------------------------------------------------
          // This is a select with dropdowns Yes or No which either takes the number of products from the main
          // product or sets it to '1'
          //--------------------------------------------------------------------------------------------------
          if ($opttype == "S") {
            echo '<select id="lw_opt_'.$opt_id.'" name="lw_opt['.$opt_id.']" class="lwam_options">'."\n"; 
            if ($hasno == 1) {
              echo '<option value=0>'.__('No','woocommerce').'</option>'."\n";
            }
            echo '<option value=1>'.__('Yes','woocommerce').'</option>'."\n";
            echo '</select>'."\n";
            //--------------------------------------------
            // Add the id to our list of items on the page
            //--------------------------------------------
            echo "<script>\n";
            echo "  lwam_ids.push('lw_opt_".$opt_id."');\n";
            echo "</script>\n";
          } else {
            echo '<select id="lw_opt_multi_'.$opt_id.'" name="lw_opt_multi['.$opt_id.']" class="lwam_options">'."\n"; 
            if ($hasno == 1) {
              echo '<option value="0">'.__('No','woocommerce').'</option>'."\n";
            }
            foreach ($optionjson[$i]->lwampo_prod as $key=>$value) {
              echo '<option value="'.$value.'">';
              echo get_the_title($value);
              echo '</option>'."\n";
            }
            echo '</select>'."\n";
            //--------------------------------------------
            // Add the id to our list of items on the page
            //--------------------------------------------
            echo "<script>\n";
            echo "  lwam_ids.push('lw_opt_multi_".$opt_id."');\n";
            echo "</script>\n";
          }
          echo '</div>'."\n";
          //-------------------------------------------------------------------------
          // We also want another select type, which will show products from an array
          // We know this by how many dimensions $optionjson[$i]->lwampo_prod has
          //-------------------------------------------------------------------------
          $lwampo_opt = $optionjson[$i]->lwampo_opt;
          if ($lwampo_opt == 0) {
            echo "<script>\n";
            echo "jQuery(document).ready(function() {\n";
            echo "  jQuery('*[name=".$args['input_name']."]').change(function() {\n";
            echo "    var quant = jQuery('*[name=".$args['input_name']."]').val();\n";
            echo "    jQuery('#lw_opt_".$opt_id." option:eq(1)').val(quant);\n";
            echo "  });\n";
            //---------------------------------------------------------------
            // On inital load, can the below do the tricksy? Yes, it seems so
            //---------------------------------------------------------------
            echo "  var quant = jQuery('*[name=".$args['input_name']."]').val();\n";
            echo "  jQuery('#lw_opt_".$opt_id." option:eq(1)').val(quant);\n";
            echo "});\n";
            echo "</script>\n";
          }
        }
        echo "</div>\n";
      }
        //------------------------------------------
        // Here must we make a price DIV. Hallelujah
        //------------------------------------------
        echo '<div class="composite_wrap lwam_pricediv">';
        echo '<p class="styckpris" style="opacity: 1;"><span class="styckpris-text">'.__('Totalt styckpris:','lwattentionmedia').'</span> <span class="styckpris-kr-val"><span class="styckpris-summa"></span> <span class="styckpris-valuta">'.get_woocommerce_currency_symbol().'/st</span></span></p>'."\n";
        echo '<p class="styckpris" style="opacity: 1;"><span class="inklmoms-text">'.__('Totalt inkl moms:','lwattentionmedia').'</span> <span class="inklmoms-kr-val"><span class="inklmoms-summa"></span> <span class="inklmoms-valuta">'.get_woocommerce_currency_symbol().'</span></span></p>'."\n";
        echo '<div class="composite_price" style=""><p class="price"><span class="total">'.__('Summa:','lwattentionmedia').'</span> <span class="total-amount-kr-val"><span class="amount total-amount">0</span> <span class="total-valuta">'.get_woocommerce_currency_symbol().'</span></span></p></div>'."\n";
        echo '</div>'."\n";
    } // End is_product()
    if (is_checkout()) {
      $input_value = $args['input_value'];
      $args['min_value'] = $args['max_value'] = $input_value;
      wc_get_template( 'global/quantity-input.php', $args ); 
    }
    if (!is_product() && !is_checkout()) {
      wc_get_template( 'global/quantity-input.php', $args ); 
    }
    //-----------------
    // Output or return
    //-----------------
    if ( $echo ) {
      echo ob_get_clean();
    } else {
      return ob_get_clean();
    }
  }
  //----------------------------------
  // Product page price display stuffs
  //----------------------------------
  // add_action ('woocommerce_before_single_product_summary','lwam_build_price_arrays');
  add_action ('wp_head','lwam_build_price_arrays');
  function lwam_build_price_arrays() {
    global $woocommerce;
    global $woocommerce_wpml;
    global $sitepress;
    global $product;
    global $post;
//die($product);
    if (is_product()) {
      // Current currency
      $current_currency = apply_filters('wcml_price_currency', NULL );
      //$product_id = $product->get_id();
      //-----------------------------------------------
      // Get the original product id (in base language)
      //-----------------------------------------------
      // WPML removed    $product_id = icl_object_id( $product->get_id(), 'product', true, $sitepress->get_default_language());
      // replaced by next line
//      $product_id = $product->get_id();
$product_id = $post->ID;
      //----------------
      // A return string
      //----------------
      $return = "";
      //--------------------
      // Start off elegantly
      //--------------------
      $return .= "\n<script>\n";
      $return .= "  var lwam_prices = new Array();\n";
      $return .= "  var lwam_quantities = new Array();\n";
      $return .= "  var lwam_ids = new Array();\n";
      $return .= "</script>\n";
      print $return;
    }
  }
  add_action('wp_footer', 'lwam_patch_spinner');
  function lwam_patch_spinner() {
    global $woocommerce;
    global $woocommerce_wpml;
    global $sitepress;
    global $product;
    global $post;
    if (is_product()) {
      // Current currency
      $current_currency = apply_filters('wcml_price_currency', NULL );
      //-----------------------------------------------
      // Get the original product id (in base language)
      //-----------------------------------------------
      // WPML removed    $product_id = icl_object_id( $product->get_id(), 'product', true, $sitepress->get_default_language());
      // replaced by next line
      //    $product_id = $product->get_id();
      $product_id = $post->ID;
      //----------------
      // A return string
      //----------------
      //--------------------
      // Start off elegantly
      //--------------------
      // print "\n<script>\n";
      // print "  var lwam_prices = new Array();\n";
      // print "  var lwam_quantities = new Array();\n";
      // print "  var lwam_ids = new Array();\n";
      // print "</script>\n";
      $_product = wc_get_product($product_id);
      $_price = $_product->get_price();
      if ($_price === '') {
        $_price = 0;
      }
      $return = "";
      $return .=  "\n<script>\n";
      $return .= "  lwam_prices[".$product_id."] = new Array();\n";
      $return .= "  lwam_prices[".$product_id."][0] = ".$_price.";\n";
      if (get_post_meta( $product_id, '_lwamfq', true )) {
        $fixed_quantity = get_post_meta( $product_id, '_lwamfq', true );
        $fixed_json = html_entity_decode($fixed_quantity);
        $fixed_json = json_decode($fixed_json);
        for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
          $price = $fixed_json[$i]->lwamfq_price;
          $price = apply_filters( 'wcml_raw_price_amount', $price, $current_currency);
          $return .= "  lwam_prices[".$product_id."][".$fixed_json[$i]->lwamfq_qty."] = ".$price.";\n";
        }
      }
      $return .= "  lwam_quantities[".$product_id."] = 0;\n";
      //-------------------------------------------------------------------------------
      // Now shall we see here... we want to check
      // - price variations on the main product into an array
      // - price variations on products that take quantity from main product into array
      //-------------------------------------------------------------------------------
      $optionaddons = get_post_meta( $product_id, '_lwampo', true );
      $optionjson = html_entity_decode($optionaddons);
      $optionjson = json_decode($optionjson);
  
      $size = count($optionjson);
      for($i = 0; $i < $size; $i++) {
        if (count($optionjson[$i]->lwampo_prod) > 0) {
          //print "option";
          //print_r($optionjson[$i]);
          //----------------------------------------------------------------------
          // opttype = 'S' (single product) or 'M' (multiple products in dropdown)
          //----------------------------------------------------------------------
          $opttype = "S";
          if (count($optionjson[$i]->lwampo_prod) == 1) {
            if (is_array($optionjson[$i]->lwampo_prod)) {
              $opt_id = $optionjson[$i]->lwampo_prod[0];
            } else {
              $opt_id = $optionjson[$i]->lwampo_prod;
            }
            // WPML removed          $opt_id = icl_object_id( $opt_id, 'product', true, $sitepress->get_default_language());
            $opttype = "S";
          } else {
            $opttype = "M";
          } 
          //----------------------------------------
          // Figure out which type of option this is
          //----------------------------------------
          // lwampo_opt = 0 means it follows main product's quantity
          // lwampo_opt = 1 means is always has quantity 1
          // lwampo_prod = the add-ons product_id
          if ($opttype == "S") {
            //--------------------------------
            // Just one product to be added on
            //--------------------------------
            //---------------------------------------------------------------------
            // Get either the price (singular) or the quantity-driven prices. Urgh.
            //---------------------------------------------------------------------
            // Insert standard price
            $_subproduct = wc_get_product($opt_id);
            if ($_subproduct != null && $_subproduct != false) {
              $_subprice = $_subproduct->get_price();
              if ($_subprice === '') {
                $_subprice = 0;
              }
              $return .= "  lwam_prices[".$opt_id."] = new Array();\n";
              $return .= "  lwam_prices[".$opt_id."][0] = ".$_subprice.";\n";
              if (get_post_meta( $opt_id, '_lwamfq', true ) != false) {
                $fixed_quantity = get_post_meta( $opt_id, '_lwamfq', true );
                //print "fixed quantity: ";
                //var_dump($fixed_quantity);
                if ($fixed_quantity != null && $fixed_quantity != "null") {
                  $fixed_json = html_entity_decode($fixed_quantity);
                  $fixed_json = json_decode($fixed_json);
                  for($j = 0, $size2 = count($fixed_json); $j < $size2; ++$j) {
                    $price = $fixed_json[$j]->lwamfq_price;
                    $price = apply_filters( 'wcml_raw_price_amount', $price, $current_currency);
                    $return .= "  lwam_prices[".$opt_id."][".$fixed_json[$j]->lwamfq_qty."] = ".$price.";\n";
                  }
                }
              }
              $return .= "  lwam_quantities[".$opt_id."] = ".$optionjson[$i]->lwampo_opt.";\n";
            }
          } else {
            //-----------------------------------------
            // Select from a list of products to add on
            //-----------------------------------------
            //------------------------------------------------------------------
            // $value = the product id for the option, this one has one for each
            //------------------------------------------------------------------
            foreach ($optionjson[$i]->lwampo_prod as $key=>$value) {
              // So we have found one product, now we shall have to find out what kind of pricing it has
              $opt_id = $value;
              // WPML removed            $opt_id = icl_object_id( $opt_id, 'product', true, $sitepress->get_default_language());
              // Insert standard price
              $_product = wc_get_product($opt_id);
              if ($_product != null && $_product != false) {
                $_price = $_product->get_price();
                if ($_price === '') {
                  $_price = 0;
                }
                $return .= "  lwam_prices[".$opt_id."] = new Array();\n";
                $return .= "  lwam_prices[".$opt_id."][0] = ".$_price.";\n";
                if (get_post_meta( $opt_id, '_lwamfq', true )) {
                  $fixed_quantity = get_post_meta( $opt_id, '_lwamfq', true );
                  $fixed_json = html_entity_decode($fixed_quantity);
                  $fixed_json = json_decode($fixed_json);
                  for($j = 0, $size3 = count($fixed_json); $j < $size3; ++$j) {
                    $price = $fixed_json[$j]->lwamfq_price;
                    $price = apply_filters( 'wcml_raw_price_amount', $price, $current_currency);
                    $return .= "  lwam_prices[".$opt_id."][".$fixed_json[$j]->lwamfq_qty."] = ".$price.";\n";
                  }
                }
                $return .= "  lwam_quantities[".$opt_id."] = ".$optionjson[$i]->lwampo_opt.";\n";
              }
            }
          }
        }
      }
      //---------------------------------------------------------
      // And a snitzy litte function to calculate the total price
      // - the so-called kaboodle
      //---------------------------------------------------------
      $return .= "  function lwam_calculateprice() {\n";
      $return .= "    var price = 0;\n";
      $return .= "    var arrayLength = lwam_ids.length;\n";
      $return .= "    var product_id = 0;\n";
      $return .= "    var base_quantity = jQuery('#quantity').val();\n";
      $return .= "    for (var i = 0; i < arrayLength; i++) {\n";
      $return .= "      if (lwam_ids[i] == 'quantity') {\n";
      $return .= "        product_id = ".$product_id.";\n";
      $return .= "      } else {\n";
      $return .= "        if (lwam_ids[i].indexOf('multi') != -1) {\n";
      $return .= "          var product_id = jQuery('#'+lwam_ids[i]).val();\n";
      //$return .= "console.log('We have multi ('+lwam_ids[i]+') and product_id = '+product_id);\n";
      $return .= "        } else {\n";
      $return .= "          var product_id = lwam_ids[i].substring(7);\n";
      $return .= "        }\n";
      $return .= "      }\n";
      //------------------------------------------------------------------------------------
      // OK, so we have a product_id. We can now look up the price and type of quantity calc 
      // in our many, many arrays.... 0 = use base quant
      //------------------------------------------------------------------------------------
      $return .= "      var thisprice = 0;\n";
      $return .= "      var thisquantity = 0;\n";
      $return .= "      if (lwam_quantities[product_id] == 0) {\n";
      $return .= "        thisquantity = base_quantity;\n";
      $return .= "      } else {\n";
      $return .= "        thisquantity = 1;\n";
      $return .= "      }\n";
      $return .= "      if (jQuery('#'+lwam_ids[i]).val() != 0) {\n";
      $return .= "        if(lwam_prices[product_id][thisquantity]) {\n";
      $return .= "          thisprice = lwam_prices[product_id][thisquantity]*thisquantity;\n";
      $return .= "        } else {\n";
      $return .= "          thisprice = lwam_prices[product_id][0]*thisquantity;\n";
      $return .= "        }\n";
      $return .= "      }\n";
      $return .= "      price += thisprice;\n";
      $return .= "    }\n";
      //-----------------------------
      // Calculate the per-item-price
      //-----------------------------
      $return .= "    var unitprice = (price/base_quantity).formatMoney(2);\n";
      $return .= "    jQuery('.styckpris-summa').html(unitprice);\n";
      $return .= "    jQuery('.styckpris').stop();\n";
      $return .= "    jQuery('.styckpris').animate({\n";
      $return .= "      'opacity': '1'\n";
      $return .= "    }, 500);\n";
      
      // FIX: We will need to get the VAT% for this product right now!
      // ... and how do we do that if we don't know where the client
      // is from? Hmmmmmm
  
      $return .= "    jQuery('.inklmoms-summa').html((price*1.25).formatMoney(2));\n";
      $return .= "    jQuery('.styckpris').stop();\n";
      $return .= "    jQuery('.styckpris').animate({\n";
      $return .= "      'opacity': '1'\n";
      $return .= "    }, 500);\n";
      $return .= "    jQuery('.total-amount').html(price.formatMoney(2));\n";;
      //$return .= "    jQuery('.amount')[1].childNodes[0].nodeValue = price.formatMoney(2);\n";;
      $return .= "  }\n";
      $return .= "  jQuery(document).ready(function() {\n";
      //------------------------------------------------------------
      // Map all the change events and run initial price calculation
      //------------------------------------------------------------
      $return .= "    var arrayLength = lwam_ids.length;\n";
      $return .= "    for (var i = 0; i < arrayLength; i++) {\n";
      $return .= "      jQuery('#'+lwam_ids[i]).change(function() {\n";
      $return .= "        lwam_calculateprice();\n";
      $return .= "      });\n";
      $return .= "    }\n";
      $return .= "    lwam_calculateprice();\n";
      $return .= "  });\n";
      $return .= "  Number.prototype.formatMoney = function(c, d, t){\n";
      $return .= "    var n = this, \n";
      $return .= "    c = isNaN(c = Math.abs(c)) ? 2 : c, \n";
      $return .= "    d = d == undefined ? \",\" : d, \n";
      $return .= "    t = t == undefined ? \" \" : t, \n";
      $return .= "    s = n < 0 ? \"-\" : \"\", \n";
      $return .= "    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), \n";
      $return .= "    j = (j = i.length) > 3 ? j % 3 : 0;\n";
      $return .= "    return s + (j ? i.substr(0, j) + t : \"\") + i.substr(j).replace(/(\d{3})(?=\d)/g, \"$1\" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : \"\");\n";
      $return .= "  };\n";
      $return .= "</script>\n";
      echo $return;
    }
    print "<script>\n";
    print "jQuery(document).load(function() {\n";
    print "  jQuery('.pp_loaderIcon').hide();\n";
    print "});\n";
    print "</script>\n";
  }
?>
