<?php
  //------------------------------------------------------------
  // lwam_addonimport - Attentionedia assign add-ons to products
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //------------------------------------------------------------
  //--------
  // Da form
  //--------
  function lwam_import_addons_form() {
    global $sitepress;
    $default_lang = $sitepress->get_default_language();
    $sitepress->switch_lang($default_lang);
    print "<h2>".__("Lägg till tillägg","lwattentionmedia")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //---------------------------
    // Check if we are submitting
    //---------------------------
    $lwam_product_tocategories = array();
    $lwam_product_category_id = 0;
    if (isset($_REQUEST["submit"])) {
      $lwam_product_tocategories = $_REQUEST["lwam_product_tocategories"];
      $lwam_product_category_id = $_REQUEST["lwam_product_category_id"];
      $result = lwam_addaddons($lwam_product_tocategories,$lwam_product_category_id);
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
    $all_categories = lwam_getcats();
    //-----------
    // Input form
    //-----------
    print "<form class=\"form\" method=\"post\" enctype=\"multipart/form-data\">\n";
    print "<p><label for=\"lwam_product_tocategories\">".__("Välj kategorier att importera till").":</label><br />\n";
    //-----------------------------
    // Show categories to import to
    //-----------------------------
    foreach ($all_categories as $cat) {
      print "<input type=\"checkbox\" name=\"lwam_product_tocategories[]\" value=\"".$cat->term_id."\" ";
      foreach ($lwam_product_tocategories as $tocat) {
        if ($tocat == $cat->term_id) {
          print "checked";
        }
      }
      print ">".$cat->name."<br />\n";
    }
    $lwam_product_category_id = get_option('lwam_addons_category',0);
    if ($lwam_product_category_id != 0) {
      print "<p><label>".__("Category for add-ons").":</label><br>\n";
      foreach ($all_categories as $cat) {
        if($cat->category_parent == 0) {
          if ($cat->cat_ID == $lwam_product_category_id) {
            print "<input type=\"hidden\" name=\"lwam_product_category_id\" value=\"".$lwam_product_category_id."\">\n";;
            print "<b>". $cat->name ."</b>\n";
          }
        }
      }
      print "<p class=\"submit\"><input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Importera","lwattentionmedia")."\" /></p>\n";
    } else {
      print "<b>".__("Du måste välja vilken produktkategori som innehåller tillägg inställningsbilden.","lwattentionmedia")."</b>\n";
    }
    print "</form>\n";
    print "</div>\n";
  }
  function lwam_addaddons($lwam_product_tocategories,$lwam_product_category_id) {
    global $wpdb;
    global $sitepress;
    $prodstoadd = lwam_getprodsincat($lwam_product_category_id);
    foreach ($lwam_product_tocategories as $cat) {
      $prods = lwam_getprodsincat($cat);
      //-----------------------------------
      // Loop all products we are adding TO
      //-----------------------------------
      foreach ($prods as $prod) {
        //------------------------------------------------
        // First find any add-ons we have for this product
        //------------------------------------------------
        delete_post_meta($prod, '_lwampo');
        //--------------------------------
        // Loop the products we are adding
        //--------------------------------
        $prodstoaddarray = array();
        $seq = 1;
        foreach ($prodstoadd as $prodtoadd) {
          //-----------------------------------
          // Get the name of the product to add
          //-----------------------------------
          $prodname = get_the_title($prodtoadd);
          // Hmmm, lets see now. It the added product has the stafflings, we should have lwampo_opt = 0
          // else we have the lwampo_opt = 1
          $lwampo_opt = 1;
          if (get_post_meta($prodtoadd,'_lwamone',true) != 1) {
            $lwampo_opt = 0;
          }
          //if (get_post_meta( $prodtoadd, '_lwamfq', true )) {
          //  $lwampo_opt = 0;
          //}
          //--------------------------------
          // Here must we do the lwam stuffs
          //--------------------------------
          // Build array? Graaaah
          // - sequence 
          // - description 
          // - prod id 
          // - opt (0 = follow main id quantity, 1 = quantity 1)
          $prodstoaddarray[] = array(
            "lwampo_seq" => $seq,
            "lwampo_caption" => $prodname,
            "lwampo_prod" => array($prodtoadd),
            "lwampo_opt" => $lwampo_opt,
          );
          $seq++;
        }
        $lwampo = json_encode($prodstoaddarray);
        $lwampo = htmlentities($lwampo);
        //--------------------
        // Update the metadata
        //--------------------
        update_post_meta($prod, '_lwampo', $lwampo);
        //wp_die($prod);
      }
    }
  }
  //-----------------------------
  // Get all products in category
  //-----------------------------
  function lwam_getprodsincat($category_id) {
    global $sitepress;
    $default_lang = $sitepress->get_default_language();
    $product_ids = array();
    $args = array(
      'lang' => $default_lang,
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
  function lwam_getcats() {
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
