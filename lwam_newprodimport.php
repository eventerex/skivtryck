<?php
  //-----------------------------------------------------------------------
  // lwam_newprodimport.php - Import products in multiple languages - sucks
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------------------------------
  // Global default language
  $lwam_default_language = "sv";
  //================
  // the import form
  //================
  function lwam_new_import_products_form() {
    global $lwam_default_language;
    global $wpdb;
    global $woocommerce;
    global $sitepress;
    //-------------------------------------------
    // Find the default language and switch to it
    //-------------------------------------------
    $sitepress->switch_lang($lwam_default_language, true);
    //---------
    // Incoming
    //---------
    $lwam_language_code = $lwam_default_language;
    if (isset($_REQUEST["lwam_language_code"])) { $lwam_language_code = $_REQUEST["lwam_language_code"]; }
    $lwam_addonly = 0;
    if (isset($_REQUEST["lwam_addonly"])) {
      $lwam_addonly = 1;
    }
    //------------
    // Form header
    //------------
    print "<h2>".__("NEW Import Products","lwam")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //------------------
    // Check step number
    //------------------
    if (isset($_REQUEST["submit"])) {
      if (empty($_FILES['lwam_filename']['tmp_name'])) {
        $error .= __('No file selected','lwam');
        $errsep = "<br>";
      } else {
        //------------------
        // Read the products
        //------------------
        $infile = $_FILES['lwam_filename']['tmp_name'];
        $prodarray = lwam_new_readproducts($infile);
        lwam_new_importproducts($prodarray,$lwam_language_code,$lwam_default_language,$lwam_addonly);
      }
    }
    //-----------
    // Wrap it in
    //-----------
    print "<div class=\"wrap\">\n";
    //------------
    // Any errors?
    //------------
    if ($error != "") {
      print "<div class=\"error\">\n";
      print $error;
      print "</div>\n";
    } 
    //-------------
    // Any message?
    //-------------
    if ($message != "") {
      print "<div class=\"message\">\n";
      print $message;
      print "</div>\n";
    } 
    print "<form class=\"form\" method=\"post\" enctype=\"multipart/form-data\">\n";
    print "<p><label for=\"lwam_language_id\">".__("Select language for import").":</label>\n";
    $q = "SELECT * FROM ".$wpdb->prefix."icl_languages WHERE active = 1 ORDER BY english_name";
    $t = $wpdb->get_results($q,ARRAY_A);
    print "<select id=\"lwam_language_code\" name=\"lwam_language_code\">\n";
    foreach ($t as $r) {
      print "<option value=\"".$r["code"]."\" ";
      if (isset($_REQUEST["lwam_language_code"])) {
        if ($_REQUEST["lwam_language_code"] == $r["code"]) {
          print " selected ";
        }
      } else {
        if ($r["code"] == $lwam_default_language) {
          print " selected ";
        }
      }
      print ">".$r["english_name"]."</option>\n";
    }
    print "</select>\n";

    print "<p><label for=\"lwam_addonly\">".__("Only add new products","lwam").":</label>\n";
    print "<input name=\"lwam_addonly\" id=\"lwam_addonly\" type=\"checkbox\" value=\"1\">\n";
    print "<p><label for=\"lwam_filename\">".__("Upload file","lwam").":</label>\n";
    print "    <input name=\"lwam_filename\" id=\"lwam_filename\" type=\"file\" value=\"\" aria-required=\"true\" /></p>\n";
    print "<p class=\"submit\"><input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Import","lwam")."\" /></p>\n";
    print "</form>\n";
    print "</div>\n";
  }
  //====================
  // Import the products
  //====================
  function lwam_new_importproducts($prodarray,$lwam_language_code,$default_language,$lwam_addonly) {
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ERROR);
    global $sitepress;
    global $lwam_default_language;
    $sitepress->switch_lang($lwam_default_language, true);
    $sitepress->switch_lang($lwam_default_language, true);
    global $woocommerce;
    //---------------------------------------------------------
    // If we are importing a language other than default (sv)
    // we immediately create/update the categories? I think YES
    //---------------------------------------------------------
    if ($lwam_language_code != $default_language) {
      $translated_categories = lwam_translate_all_categories($lwam_language_code,$default_language);
    }
//var_dump($prodarray);
//die('Before import');
    foreach ($prodarray as $product) {
      //-----------------------------------------
      // Take away line feed, sloppy but it works
      //-----------------------------------------
      $product_number = str_replace(array("\r", "\n"), '', $product[0]);
      $product_name = $product[2];
      $product_description = $product[3];
      $product_weight = $product[12];
      $product_category = $product[18];
      $product_price = $product[5];
      if ($product_price == "") {
        $product_price = 0;
      }
      $post_type = "product";
      $newslug = $product_number."-".sanitize_title($product_name);
      //--------------------------------
      // post_id = 0 if it doesn't exist
      //--------------------------------
      $post_id = wc_get_product_id_by_sku($product_number);
      //------------------------------------------------
      // If we have a name, and a numeric product number
      //------------------------------------------------
      if ($product[2] != "" && is_numeric($product[0]) && strlen($product[0])>2) {
        //---------------------------------------------------------------------------------
        // If we have default language, we will insert a new post or update an existing one
        //---------------------------------------------------------------------------------
        if ($lwam_language_code == $default_language) {
//wp_die('Why am i here? '.$lwam_language_code." ".$default_language);
          $post = array(
            'ID' => $post_id,
            'post_author' => 1,
            'post_status' => "publish",
            'post_content' => $product_description,
            'post_title' => $product_name,
            'post_parent' => '',
            'post_type' => $post_type,
            'post_name' => $newslug,
          );
          $post_id = wp_insert_post( $post, $wp_error = false);
          if (is_wp_error($post_id)) {
            wp_die($post_id->get_error_message());
          }
          //-----------------
          // Set the metadata
          //-----------------
          lwam_new_setmetadata($post_id,$product_number,$product_price);
          //-------------------------------------------
          // This one creates a term (product category)
          //-------------------------------------------
          $term_taxonomy_id = wp_set_object_terms( $post_id, $product_category, 'product_cat' );
          if ( is_wp_error( $term_taxonomy_id ) ) {
             $error_string = $term_taxonomy_id->get_error_message();
             echo '<div id="message" class="error"><p>' . $term_taxonomy_id . '</p></div>';
          }
          wp_set_object_terms($post_id, 'simple', 'product_type');
        } else {
          //--------------------------------------------------------------------
          // If the product does not exist in the original language, me no want!
          //--------------------------------------------------------------------
          $post_id = wc_get_product_id_by_sku($product_number);
          if ($post_id != 0) {
            //------------------------------------------------------------------------------------------
            // We are not in default language, we want to create a translation or update an existing one
            // After this, we have an id that we will use to attach product categories
            //------------------------------------------------------------------------------------------
            $post_translated_id = lwam_new_translate_post( $post_id, $post_type, $lwam_language_code,$product_name,$product_description,$product_number,$product_category,$product_price);
            $terms_original = wp_get_post_terms( $post_id, 'product_cat');
            foreach ($terms_original as $term) {
              $translated_term_id =  icl_object_id($term->term_id, 'product_cat', true, $lwam_language_code);
              //----------------------------------------------------------------
              // NOW, we want to assign the translated category to this here one
              // we want the translated slug or id. Hmmmm
              //----------------------------------------------------------------
              wp_set_object_terms($post_translated_id,$translated_term_id,'product_cat');
            }
            $imgprod = new WC_Product($post_id);
            $featured_id = get_post_thumbnail_id($post_id);
            set_post_thumbnail( $post_translated_id, $featured_id );
            $images = $imgprod->get_gallery_attachment_ids();
            $newprod = new WC_Product($post_translated_id);
            $newprod->set_gallery_image_ids($images);
//var_dump($newprod);
//print "<br>\n";
            $newprod->save();
          }
        }
      }
    }
  }
  //========================================
  // Insert terms for a non-default language
  //========================================
  function lwam_insert_terms($terms, $taxonomy,$lwam_language_code){
    $feedback = array();
    //-----------------
    // insert new terms
    //-----------------
    foreach($terms as $d_term_id => $t_term_name){
      //------------------------------------------------------------------------
      // the default term_taxonomy_id (this is what we save in icl_translations)
      //------------------------------------------------------------------------
      $default_term = term_exists( $d_term_id, $taxonomy );           
      if ( is_wp_error( $default_term ) ) {
         $error_string = $default_term->get_error_message();
         echo '<div id="message" class="error"><p>' . $default_term . '</p></div>';
      }
      $slug = sanitize_title_with_dashes($t_term_name).'-'.$lwam_language_code;
      $translated_term = term_exists( $slug, $taxonomy );           
      if ( is_wp_error( $translated_term ) ) {
        $error_string = $translated_term->get_error_message();
        echo '<div id="message" class="error"><p>' . $translated_term . '</p></div>';
      }
      if ($translated_term == null) {
        $translated_term = wp_insert_term($t_term_name, $taxonomy, array('slug' => $slug));
        if ( is_wp_error( $translated_term ) ) {
           $error_string = $translated_term->get_error_message();
           echo '<div id="message" class="error"><p>' . var_export($translated_term,true) . '</p></div>';
           wp_die();
        }
      } else {
        $translated_term = wp_update_term($translated_term["term_id"], $taxonomy, array('name' => $t_term_name,'slug' => $slug));
//print "We have tried UPDATE the term and the result was ".var_export($translated_term,true)."<br>\n";
        if ( is_wp_error( $translated_term ) ) {
           $error_string = $translated_term->get_error_message();
           echo '<div id="message" class="error"><p>' . var_export($translated_term,true) . '</p></div>';
           wp_die();
        }
      }
      //--------------------------------------------------
      // pass the result in the $feedback array for output 
      //--------------------------------------------------
      $feedback[$default_term['term_id']] = $translated_term['term_id'];
      //$feedback[$default_term['term_taxonomy_id']] = $translated_term['term_taxonomy_id'];
    }   
    return $feedback;
  }
  //=================================
  // Translate ALL product categories
  //=================================
  function lwam_translate_all_categories($lang,$default_language) {
    global $sitepress;
    $sitepress->switch_lang('sv', true);
    $sitepress->switch_lang('sv', true);
    $terms_original = get_terms( 'product_cat', 'hide_empty=0');
    $terms_to_insert = array();
    foreach ($terms_original as $term_original) {
      $term_temp_id = $term_original->term_id;
      $term_temp_name = $term_original->name;
      $terms_to_insert[$term_temp_id] = $term_temp_name;
    }
    //-------------------------------------
    // set the element type tax_product_cat
    //-------------------------------------
    $element_type ='tax_product_cat';
    $language_code = $lang;
    //----------------------------------------------------------------------------------
    // insert the terms
    // see the wpml_insert_terms function comments for the parameters 
    //----------------------------------------------------------------------------------
//print "We are about to insert terms ".var_export($terms_to_insert,true)." for language ".$lang."<br>\n";
    $inserted_terms = lwam_insert_terms($terms_to_insert,  'product_cat',$lang);
//print "We have insert terms ".var_export($inserted_terms,true)."<br>\n";
    //----------------------------------------------------------
    // We have all from-to term_taxonomy_id's in $inserted_items
    //----------------------------------------------------------
    if (!empty($inserted_terms)) {
      global $sitepress;
      // loop
      foreach($inserted_terms as $d_term => $t_term){
        //-------------------------------
        // get the trid from the original
        //-------------------------------
        $trid = $sitepress->get_element_trid($d_term, $element_type);
        //----------------------------------------------
        // associate the translated term to the original            
        //----------------------------------------------
        $process = true;
        if ($process == true) {
          $result_lang_details = $sitepress->set_element_language_details($t_term, $element_type, $trid, $lang, $default_language);  
          if ( is_wp_error( $result_lang_details ) ) {
             $error_string = $result_lang_details->get_error_message();
             echo '<div id="message" class="error"><p>' . $result_lang_details . '</p></div>';
          }
        }
      }
    }
  }
  //=============================
  // Translate product categories
  //=============================
  function lwam_translate_categories($post_translated_id,$lang,$default_language,$product_category) {
    global $sitepress;
    $return = array();
    $post_id = icl_object_id( $post_translated_id, 'product',  false,  false );
    //-------------------------------------
    // Get the terms from the original post
    //-------------------------------------
    $terms_original = wp_get_post_terms( $post_id, 'product_cat');
    if ( is_wp_error( $terms_original ) ) {
       $error_string = $terms_original->get_error_message();
       echo '<div id="message" class="error"><p>' . $terms_original . '</p></div>';
    }
    $terms_to_insert = array();
    foreach ($terms_original as $term_original) {
      $term_temp_id = $term_original->term_id;
      $term_temp_name = $term_original->name;
      $terms_to_insert[$term_temp_id] = $term_temp_name;
    }
    //-------------------------------------
    // set the element type tax_product_cat
    //-------------------------------------
    $element_type ='tax_product_cat';
    $language_code = $lang;
    //----------------------------------------------------------------------------------
    // insert the terms
    // see the wpml_insert_terms function comments for the parameters 
    //----------------------------------------------------------------------------------
//print "We are about to insert terms ".var_export($terms_to_insert,true)." for language ".$lang."<br>\n";
    $inserted_terms = lwam_insert_terms($terms_to_insert,  'product_cat',$lang);
//print "We have insert terms ".var_export($inserted_terms,true)."<br>\n";
    //----------------------------------------------------------
    // We have all from-to term_taxonomy_id's in $inserted_items
    //----------------------------------------------------------
    if (!empty($inserted_terms)) {
      global $sitepress;
      // loop
      foreach($inserted_terms as $d_term => $t_term){
        //-------------------------------
        // get the trid from the original
        //-------------------------------
        $trid = $sitepress->get_element_trid($d_term, $element_type);
        //----------------------------------------------
        // associate the translated term to the original            
        //----------------------------------------------
        $process = true;
        if ($process == true) {
          $result_lang_details = $sitepress->set_element_language_details($t_term, $element_type, $trid, $lang, $default_language);  
          if ( is_wp_error( $result_lang_details ) ) {
             $error_string = $result_lang_details->get_error_message();
             echo '<div id="message" class="error"><p>' . $result_lang_details . '</p></div>';
          }
        }
        //----------------------------------
        // Put stuff in the $return variable
        //----------------------------------
        $return[] = $trid;
        //-----------------------------------------------------------------------------------
        // Assign the fornicating translated term to the translated post, how hard can it be?
        // when I do this shit, it deletes the terms, whaddafok is this????
        // CHECK TO ASSIGN CATEGORY MANUALLY AND SEE WHAT HAPPENS IN THE DB
        //-----------------------------------------------------------------------------------
//        $assigned_terms = wp_set_object_terms($post_translated_id,$t_term,'product_cat');
//        if ( is_wp_error( $sssigned_terms ) ) {
//           $error_string = $assigned_terms->get_error_message();
//           echo '<div id="message" class="error"><p>' . $assigned_terms . '</p></div>';
//           var_dump($assigned_terms);
//        }
     
      }
    }
    return $return;
  }
  //===============
  // Translate post
  //===============
  function lwam_new_translate_post($post_id, $post_type, $lang,$product_name,$product_description,$product_number,$product_category,$product_price) {
    global $wpdb;
    $post_translated_id = icl_object_id($post_id,'product',false,$lang);
    //-----------------
    // Include WPML API
    //-----------------
    include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );
    //-----------------------
    // Insert translated post
    //-----------------------
    $newslug = $product_number."-".sanitize_title($product_name);
    $post_translated_id = wp_insert_post( 
      array(
        'ID' => $post_translated_id,
        'post_status' => "publish",
        'post_content' => $product_description,
        'post_title' => $product_name,
        'post_name' => $newslug,
        'post_type' => $post_type
      )
    );
    //--------------------------
    // Get trid of original post
    //--------------------------
    $trid = wpml_get_content_trid( 'post_' . $post_type, $post_id );
    //---------------------
    // Get default language
    //---------------------
    $default_lang = wpml_get_default_language();
    //--------------------------------------------
    // Associate original post and translated post
    //--------------------------------------------
    $wpdb->update( $wpdb->prefix.'icl_translations', array( 'trid' => $trid, 'language_code' => $lang, 'source_language_code' => $default_lang ), array( 'element_id' => $post_translated_id ) );
//    update_post_meta($post_translated_id, '_sku', $product_number);
    lwam_new_setmetadata($post_translated_id,$product_number,$product_price);
    //------------------------------------
    // Remove the status, I don't know why
    //------------------------------------
    $q = "DELETE FROM ".$wpdb->prefix."icl_translation_status WHERE translation_id=".$trid;
    $t = $wpdb->query($q);
    //--------------------------
    // Return translated post ID
    //--------------------------
    return $post_translated_id;
  }
  //==========================
  // Set product post metadata
  //==========================
  function lwam_new_setmetadata($post_id,$product_number,$product_price) {
    // Get woocommerce version
    $lwam_woocommerce_version = lwam_get_woo_version_number();
    update_post_meta($post_id, '_sku', $product_number);
    update_post_meta($post_id, '_price', $product_price);
    update_post_meta($post_id, '_sale_price', $product_price);
    update_post_meta($post_id, '_regular_price', $product_price);
    //----------------------------------
    // I don't know whichadese I need...
    //----------------------------------
    update_post_meta($post_id, '_wcml_custom_prices_status',0);
    update_post_meta($post_id, '_wpml_media_featured',1);
    update_post_meta($post_id, '_wpml_media_duplicate',1);
    update_post_meta($post_id, 'slide_template','default');
    update_post_meta($post_id, '_product_version',$lwam_woocommerce_version);
    update_post_meta($post_id, '_stock_status','instock');
    update_post_meta($post_id, '_download_expiry',-1);
    update_post_meta($post_id, '_download_limit',-1);
    update_post_meta($post_id, '_product_image_gallery','');
    update_post_meta($post_id, '_downloadable','no');
    update_post_meta($post_id, '_virtual','no');
    update_post_meta($post_id, '_default_attributes',array());
    update_post_meta($post_id, '_purchase_note','');
    update_post_meta($post_id, '_crosssell_ids', array());
    update_post_meta($post_id, '_upsell_ids',array());
    update_post_meta($post_id, '_height','');
    update_post_meta($post_id, '_width','');
    update_post_meta($post_id, '_length','');
    update_post_meta($post_id, '_weight','');
    update_post_meta($post_id, '_sold_individually','no');
    update_post_meta($post_id, '_backorders','no');
    update_post_meta($post_id, '_manage_stock','no');
    update_post_meta($post_id, '_tax_class','');
    update_post_meta($post_id, '_tax_status','taxable');
    update_post_meta($post_id, '_sale_price_dates_to','');
    update_post_meta($post_id, '_sale_price_dates_from','');
    update_post_meta($post_id, '_edit_last',1);
    update_post_meta($post_id, 'total_sales',0); // Needed?
    update_post_meta($post_id, '_stock',null); // Needed?
    update_post_meta($post_id, 'gm_titeln_i_varukorgen_gm_titeln_i_varkorgen',null); // Needed?
    return $post_id;
  }
  //======================================
  // Read the products and return an array
  //======================================
  function lwam_new_readproducts($infile) {
    $prods = file_get_contents($infile);
    $prodarray = array();
    $fieldno = 0;
    $fieldvalue = "";
    $thisprod = array();
    for ($i=0;$i < strlen($prods);$i++) {
      if ($fieldno > 20) {
        if ($prods[$i] != "\r" && $prods[$i] != "\n" ) {
          $fieldvalue .= $prods[$i];
        } else {
          $thisprod[] = $fieldvalue;
          $prodarray[] = $thisprod;
          //print "<td valign=\"top\">".$fieldvalue."</td></tr>\n";
          $fieldno = 0;
          $fieldvalue = "";
          $thisprod = array();
        }
      } else {
        if ($prods[$i] != "\t") {
          $fieldvalue .= $prods[$i];
        } else {
          if (substr($fieldvalue,0,1) == '"') {
            $fieldvalue = substr($fieldvalue,1,-1);
          }
          $thisprod[] = $fieldvalue;
          $fieldno++;
          $fieldvalue = "";
        }
      }
    }
    //---------------------------------------------
    // Remove products without product number (SKU)
    //---------------------------------------------
    $firstline = false;
    print "<div style=\"height: 300px;overflow-y: scroll;\">\n";
    print '<table class="wp-list-table striped">' . "\n";
    print "<tr>\n";
    print "<th>Artikelnummer</th>\n";
    print "<th>Dölj</th>\n";
    print "<th>Artikelnamn</th>\n";
    print "<th>Artikelbeskrivning</th>\n";
    print "<th>Pris</th>\n";
    print "<th>Vikt</th>\n";
    print "<th>Artikelgrupp</th>\n";
    print "</tr>\n";
    foreach ($prodarray as $key => $product) {
      if ($firstline == true) {
        if ($product[2] != "" && is_numeric($product[0])) {
          print "<tr>\n";
          // SKU
          print "<td>".$product[0]."</td>\n";
          // Hide
          print "<td>".$product[1]."</td>\n";
          // Product name
          print "<td>".$product[2]."</td>\n";
          // Product description
          $productname = $product[3];
          if (strlen($productname) > 30) {
            $productname = substr($productname,0,30)."...";
          }
          print "<td>".strip_tags($productname)."</td>\n";
          // Price
          print "<td>".$product[5]."</td>\n";
          // Weight
          print "<td>".$product[12]."</td>\n";
          // Product category
          print "<td>".$product[18]."</td>\n";
          print "</tr>\n";
        } else {
          unset($prodarray[$key]);
        }
      } else {
        unset($prodarray[$key]);
      }
      $firstline = true;
    }
    print "</table>\n";
    print "</div>\n";
    return $prodarray;
  }
  function lwam_get_woo_version_number() {
    // If get_plugins() isn't available, require it
    if ( ! function_exists( 'get_plugins' ) )
      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    // Create the plugins folder and file variables
    $plugin_folder = get_plugins( '/' . 'woocommerce' );
    $plugin_file = 'woocommerce.php';
    // If the plugin version number is set, return it 
    if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
      return $plugin_folder[$plugin_file]['Version'];
    } else {
      // Otherwise return null
      return NULL;
    }
  }
?>
