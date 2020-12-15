<?php
//ini_set('display_startup_errors', 1);
//ini_set('display_errors', 1);
//error_reporting(-1);
  //-----------------------------------------------
  // lwam_pricechange - Attentionmedia upate prices
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------
  //--------
  // Da form
  //--------
  function lwam_price_change_form() {
    global $sitepress;
    print "<h2>".__("Ändra priser","lwattentionmedia")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //---------------------------
    // Check if we are submitting
    //---------------------------
    if (isset($_REQUEST["submit"]) || isset($_REQUEST["dryrun"])) {
      $lwam_product_category_ids = $_REQUEST["lwam_product_category_ids"];
      $lwam_percent = $_REQUEST["lwam_percent"];
      $lwam_decimals = $_REQUEST["lwam_decimals"];
    } else {
      $lwam_product_category_ids = array();
      $lwam_percent = 0;
      $lwam_decimals = 2;
    }
    print "<div class=\"wrap\">\n";
    if ($error != "") {
      print "<div class=\"error\">\n";
      print $error;
      print "</div>\n";
    } 
    if ($message != "") {
      print "<div class=\"message\">\n";
      print $message;
      print "</div>\n";
    } 
    //-------------------
    // Get all categories
    //-------------------
    $all_categories = lwam_getpricecats();
    //-----------
    // Input form
    //-----------
    print "<form class=\"form\" method=\"post\" enctype=\"multipart/form-data\">\n";
    print "<p><label for=\"lwam_product_tocategories\">".__("Välj kategorier att ändra priser för:","lwattentionmedia").":</label><br />\n";
    //-----------------------------
    // Show categories to import to
    //-----------------------------
    foreach ($all_categories as $cat) {
      print "<input type=\"checkbox\" name=\"lwam_product_category_ids[]\" value=\"".$cat->term_id."\" ";
      foreach ($lwam_product_category_ids as $tocat) {
        if ($tocat == $cat->term_id) {
          print "checked";
        }
      }
      print ">".$cat->name."<br />\n";
    }
    print "<p>\n";
    print "<label for=\"lwam_percent\">".__("Procent prisändring (procent av nuvarande pris, ange negativ procent för prissänkning)","lwattentionmedia")."</label>\n";
    print "<br />\n";
    print "<input size=8 class=\"form-field\" name=\"lwam_percent\" id=\"lwam_percent\" value=\"".$lwam_percent."\">%\n";
    print "<p>\n";
    print "<label for=\"lwam_decimals\">".__("Antal decimaler i nytt pris","lwattentionmedia")."</label>\n";
    print "<br />\n";
    print "<input size=2 class=\"form-field\" name=\"lwam_decimals\" id=\"lwam_decimals\" value=\"".$lwam_decimals."\">\n";
    print "<p class=\"submit\">\n";
    print "  <input type=\"submit\" class=\"button button-secondary btn btn-secondary\" name=\"dryrun\" value=\"".__("Torrsim","lwattentionmedia")."\" />\n";
    print "  <input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Uppdatera","lwattentionmedia")."\" />\n";
    print "</p>\n";
    print "</form>\n";
    //---------------------------
    // Check if we are submitting
    //---------------------------
    if (isset($_REQUEST["submit"]) || isset($_REQUEST["dryrun"])) {
      $dryrun = isset($_REQUEST["dryrun"]);
      $result = lwam_changeprice($lwam_percent,$lwam_decimals,$dryrun,$lwam_product_category_ids);
    } else {
      $lwam_product_category_ids = array();
      $lwam_percent = 0;
      $lwam_decimals = 2;
    }
    print "</div>\n";
  }
  function lwam_changeprice($lwam_percent,$lwam_decimals,$dryrun,$lwam_product_category_ids) {
    global $wpdb;
    global $woocommerce;
    print "<h3>\n";
    if ($dryrun) {
      print __("Resultat av torrsim - priserna EJ uppdaterade","lwattentionmedia");
    } else {
      print __("Resultat uppdatering - priserna uppdaterade","lwattentionmedia");
    }
    print "</h3>\n";
    print "<table width=\"70%\" class=\"wp-list-table wide fixed striped\">\n";
    print "<tr>\n";
    print "<th align=left>".__("Artikelnummer","lwattentionmedia")."</th>\n";
    print "<th align=left>".__("Artikelbenämning","lwattentionmedia")."</th>\n";
    print "<th align=right>".__("Antal","lwattentionmedia")."</th>\n";
    print "<th align=right>".__("Pris","lwattentionmedia")."</th>\n";
    print "<th align=right>".__("Nytt pris","lwattentionmedia")."</th>\n";;
    print "<th></th>\n";;
    print "</tr>\n";
    foreach ($lwam_product_category_ids as $cat) {
      $prods = lwam_getprodsinpricecat($cat);
      //---------------------------------------------
      // Loop all products we are changing prices for
      //---------------------------------------------
      foreach ($prods as $product_id) {
        $_product = wc_get_product($product_id);
        //-----------------------------------------
        // Get all the prices for this here product
        //-----------------------------------------
        $quantity = 0;
        $price = $_product->get_price();
        $product_name = $_product->get_name();
        $product_sku = $_product->get_sku();
        if ($price === '') {
          $price = 0;
        }
        if (get_post_meta( $product_id, '_lwamfq', true )) {
          $fixed_quantity = get_post_meta( $product_id, '_lwamfq', true );
          $fixed_json = html_entity_decode($fixed_quantity);
          $fixed_json = json_decode($fixed_json);
          for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
            $quantity = $fixed_json[$i]->lwamfq_qty;
            $price = $fixed_json[$i]->lwamfq_price;
            $newprice = ($price * ($lwam_percent+100))/100;
            $newpricerounded = round($newprice,$lwam_decimals);
            print "<tr>\n";
            print "<td>".$product_sku."</td>\n";
            print "<td>".$product_name."</td>\n";
            print "<td align=right>".$quantity."</td>\n";
            print "<td align=right>".$price."</td>\n";
            print "<td align=right>".number_format($newpricerounded,$lwam_decimals)."</td>\n";;
            if (!$dryrun) {
              $fixed_json[$i]->lwamfq_price = $newpricerounded;
              print "<td>".__("Uppdaterad","lwattentionmedia")."</td>\n";
            } else {
              print "<td>".__("Simulerad - EJ uppdaterad","lwattentionmedia")."</td>\n";
            }
            print "</tr>\n";
          }
          $fixed_quantity = htmlentities(json_encode($fixed_json));
          if (!$dryrun) {
            update_post_meta($product_id, '_lwamfq',$fixed_quantity);
          }
        } else {
          $newprice = ($price * ($lwam_percent+100))/100;
          $newpricerounded = round($newprice,$lwam_decimals);
          print "<tr>\n";
          print "<td>".$product_sku."</td>\n";
          print "<td>".$product_name."</td>\n";
          print "<td align=right>".$quantity."</td>\n";
          print "<td align=right>".$price."</td>\n";
          print "<td align=right>".number_format($newpricerounded,$lwam_decimals)."</td>\n";;
          if (!$dryrun) {
            print "<td>".__("Uppdaterad","lwattentionmedia")."</td>\n";
          } else {
            print "<td>".__("Simulerad - EJ uppdaterad","lwattentionmedia")."</td>\n";
          }
          print "</tr>\n";
          if (!$dryrun) {
            $_product->set_regular_price( $newpricerounded );
            $_product->set_price( $newpricerounded );
          }
        }
      }
    }
    print "</table>\n";
  }
  //-----------------------------
  // Get all products in category
  //-----------------------------
  function lwam_getprodsinpricecat($category_id) {
    global $sitepress;
    $product_ids = array();
    $args = array(
      'posts_per_page' => -1,
      'tax_query' => array(
        'relation' => 'AND',
        array(
          'taxonomy' => 'product_cat',
          'field' => 'term_id',
          'terms' => $category_id
        )
      ),
      'post_type' => 'product',
      'orderby' => 'title,'
    );
    $products = new WP_Query( $args );
    while ($products->have_posts()) {
      $products->the_post();
      //print $products->post->ID."<br />\n";;
      $product_ids[] = $products->post->ID;
      wp_reset_postdata();
    }
    return $product_ids;
  }
  //---------------------------
  // Get all product categories
  //---------------------------
  function lwam_getpricecats() {
    global $wpdb;
    global $sitepress;
    $all_categories = array();
    $taxonomy     = 'product_cat';
    $orderby      = 'name';  
    $show_count   = 0;      // 1 for yes, 0 for no
    $pad_counts   = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no  
    $title        = '';  
    $empty        = 0;
    $args = array(
      'taxonomy'     => $taxonomy,
      'orderby'      => $orderby,
      'show_count'   => $show_count,
      'pad_counts'   => $pad_counts,
      'hierarchical' => $hierarchical,
      'title_li'     => $title,
      'hide_empty'   => $empty
    );
    $all_categories = get_categories( $args );
    return $all_categories;
  }
?>
