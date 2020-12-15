<?php
//var_dump($default_language);
  //-----------------------------------------------
  // lwam_prodimport - Attentionedia import prods
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------
  //--------------
  // Some includes
  //--------------
  require_once 'PHPExcel/Classes/PHPExcel.php';
  function lwam_import_products_form() {
    global $wpdb;
    global $sitepress;
    $default_language = $sitepress->get_default_language();
    print "<h2>".__("Importera produkter","lwattentionmedia")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //---------------------------
    // Check if we are submitting
    //---------------------------
    if (isset($_REQUEST["submit"])) {
      if (empty($_FILES['lwam_filename']['tmp_name'])) {
        $error .= __('No file selected','lwam');
        $errsep = "<br>";
      } else {
        $lwam_language_code = $_REQUEST["lwam_language_code"];
        $infile = $_FILES['lwam_filename']['tmp_name'];
        $prodinfo = lwam_prodimport_preview($infile,$lwam_language_code);
        $lwam_addonly = 0;
        if (isset($_REQUEST["lwam_addonly"])) {
          $lwam_addonly = 1;
        }
        if (count($prodinfo) > 0) {
        //if (lwam_prodimport_preview($infile)) {
          $message .= lwam_prodimport_import($prodinfo,$lwam_language_code,$lwam_addonly);
        } else {
          $error .= __('Fel vid läsning av filen','lwattentionmedia');
          $errsep = "<br>";
        }
      }
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
    print "<form class=\"form\" method=\"post\" enctype=\"multipart/form-data\">\n";
    print "<p><label for=\"lwam_language_id\">".__("Välj språk för import","lwattentionmedia").":</label>\n";
    $q = "SELECT * FROM ".$wpdb->prefix."icl_languages WHERE active = 1 ";
    $t = $wpdb->get_results($q,ARRAY_A);
    print "<select id=\"lwam_language_code\" name=\"lwam_language_code\">\n";
    foreach ($t as $r) {
      print "<option value=\"".$r["code"]."\" ";
      if (isset($_REQUEST["lwam_language_code"])) {
        if ($_REQUEST["lwam_language_code"] == $r["code"]) {
          print " selected ";
        }
      } else {
        if ($r["code"] == $default_language) {
          print " selected ";
        }
      }
      print ">".$r["english_name"]."</option>\n";
    }
    print "</select>\n";
    print "<p><label for=\"lwam_addonly\">".__("Lägg bara til nya produkter","lwattentionmedia").":</label>\n";
    print "<input name=\"lwam_addonly\" id=\"lwam_addonly\" type=\"checkbox\" value=\"1\">\n";
    print "<p><label for=\"lwam_filename\">".__("Ladda upp fil","lwattentionmedia").":</label>\n";
    print "    <input name=\"lwam_filename\" id=\"lwam_filename\" type=\"file\" value=\"\" aria-required=\"true\" /></p>\n";
    print "<p class=\"submit\"><input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Importera","lwattentionmedia")."\" /></p>\n";
    print "</form>\n";
    print "</div>\n";
  }
  function lwam_prodimport_preview($infile,$lwam_language_code) {
    //$prods = utf8_encode(file_get_contents($infile));
    $prods = file_get_contents($infile);
    $prodarray = array();
    $fieldno = 0;
    $fieldvalue = "";
    print "<div style=\"height: 300px;overflow-y: scroll;\">\n";
    print '<table class="wp-list-table striped">' . "\n";
    print "<tr>\n";
    print "<th>".__("Artikelnummer","lwattentionmedia")."</th>\n";
    print "<th>".__("Dölj","lwattentionmedia")."</th>\n";
    print "<th>".__("Artikelnamn","lwattentionmedia")."</th>\n";
    print "<th>".__("Artikelbeskrivning","lwattentionmedia")."</th>\n";
    print "<th>".__("Pris","lwattentionmedia")."</th>\n";
    print "<th>".__("Vikt","lwattentionmedia")."</th>\n";
    print "<th>".__("Artikelgrupp","lwattentionmedia")."</th>\n";
    print "</tr>\n";
    $thisprod = array();
    for ($i=0;$i < strlen($prods);$i++) {
      if ($fieldno == 44) {
        if ($prods[$i] != "\r") {
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
    $firstline = false;
    foreach ($prodarray as $product) {
      if ($firstline == true) {
        if ($product[2] != "") {
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
        }
      }
      $firstline = true;
    }
    print "</table>\n";
    print "</div>\n";
    return $prodarray;
  }
  function lwam_prodimport_import($prodinfo, $lwam_language_code,$lwam_addonly) {
    global $wpdb;
    global $sitepress;
    $default_language = $sitepress->get_default_language();
    $return = "";
    $retsep = "";
    $number_imported = 0;
    $number_error = 0;
    foreach ($prodinfo as $product) {
      if ($product[2] != "" && is_numeric($product[0]) && strlen($product[0])>2) {
        $product_number = $product[0];
        //-----------------------------------------
        // Take away line feed, sloppy but it works
        //-----------------------------------------
        $product_number = str_replace(array("\r", "\n"), '', $product_number);
        $product_name = $product[2];
        $product_description = $product[3];
        $product_weight = $product[12];
        $product_category = $product[18];
        $product_price = $product[5];
        //---------------------------
        // Find the post if it exists
        //---------------------------
        $post_id = 0;
        $q = "SELECT ";
        $q .= $wpdb->prefix."postmeta.* ";
        $q .= "  FROM ";
        $q .= $wpdb->prefix."postmeta ";
        $q .= " INNER JOIN ";
        $q .= $wpdb->prefix."posts ";
        $q .= " ON ";
        $q .= $wpdb->prefix."postmeta.post_id = ";
        $q .= $wpdb->prefix."posts.ID ";
        $q .= " WHERE ";
        $q .= $wpdb->prefix."postmeta.meta_key='_sku' and ".$wpdb->prefix."postmeta.meta_value='".$product_number."'";
        $q .= " AND ";
        $q .= $wpdb->prefix."posts.post_status = 'publish'";
        $t = $wpdb->get_results($q,ARRAY_A);
        foreach ($t as $r) {
          $post_id = $r["post_id"];
        }
        //--------------------------------------------------------------------
        // OK, if the post does not exist and we are not importing the default
        // language, we must skip, skip, skip it
        //--------------------------------------------------------------------
        //--------------------------------------------------------------------------------------
        // If we are only adding, we are on the default language and we have a post, we should
        // skip it. If we are on another language, we can't be only adding, it requires that the
        // post already exists
        //--------------------------------------------------------------------------------------
        if ($lwam_addonly == 1 && $lwam_language_code == $default_language && $post_id != 0) {
           wp_die('No can do');
        } else {
          if ($lwam_language_code == $default_language) {
            if ($post_id == 0) {
              $post = array(
                  'post_author' => 1,
                  'post_content' => '',
                  'post_status' => "publish",
                  'post_content' => $product_description,
                  'post_title' => $product_name,
                  'post_parent' => '',
                  'post_type' => "product",
              );
              // Create post
              $post_id = wp_insert_post( $post, $wp_error = false);
              if (is_wp_error($post_id)) {
                wp_die($post_id->get_error_message());
              }
            } else {
              $post = array(
                  'ID' => $post_id,
                  'post_author' => 1,
                  'post_status' => "publish",
                  'post_content' => $product_description,
                  'post_title' => $product_name,
                  'post_parent' => '',
                  'post_type' => "product",
              );
              // Update post
              $post_id = wp_insert_post( $post, $wp_error = false);
              if (is_wp_error($post_id)) {
                wp_die($post_id->get_error_message());
              }
            }
            //-----------------------------------------------
            // This one creates a term, and maybe a taxonomy?
            //-----------------------------------------------
            $term_taxonomy_id = wp_set_object_terms( $post_id, $product[18], 'product_cat' );
            wp_set_object_terms($post_id, 'simple', 'product_type');
// FIX??
//            update_post_meta( $post_id, '_visibility', 'visible' );
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
            update_post_meta($post_id, '_product_version', '3.0.5'); // Was 3.0.4
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
            //print "Importing :".$sku." ".$product_name."<br>\n";
            $number_imported++;
          } else {
            //-------------------------------
            // Importing non-default language
            //-------------------------------
            if ($post_id == 0) {
              $number_error++;
            } else {
              //-----------------------------------
              // Get post_id of original post
              // What we have in $post_id ain't it?
              // It should be
              //-----------------------------------
              //--------------------------
              // Get trid of original post
              //--------------------------
              $trid = wpml_get_content_trid( 'post_product', $post_id );
              //--------------------------------------
              // Find the language copy of the product
              //--------------------------------------
              $trid = $sitepress->get_element_trid($post_id, 'post_product');
              $translations = $sitepress->get_element_translations($trid, 'product');
              $post_translated_id = 0;
              foreach( $translations as $lang=>$translation){
                if ($lang == $lwam_language_code) {
                  $post_translated_id = $translation->element_id;
                }
              }
              //---------------------------------------------
              // Make a translated post if we didn't have one
              //---------------------------------------------
              if ($post_translated_id == 0) {
                //---------------------------------------------
                // Create a copy for translation of the product
                //---------------------------------------------
                $post_translated_id = wp_insert_post( 
                  array( 
                    'post_title' => $product_name,
                    'post_content' => $product_description, 
                    'post_type' => 'product' , 
                    'post_status' => 'publish',
                    'post_author' => 1 
                  ) 
                );
              }

// IN PROGRESS
// print $post_id."<br>\n";
$strunt = 1;
if ($strunt == 0) {
              //----------------------------------------------------
              // Here we need to make or update the product category
              //----------------------------------------------------
              //-----------------------------------------------------
              // First, get the category(ies) of the original product
              // $post_id = original product
              // $post_translated_id = id of the translated product
              //-----------------------------------------------------
              $q = "SELECT ".$wpdb->prefix."term_relationships.* ";
              $q .= ",".$wpdb->prefix."terms.* ";
              $q .= " FROM ";
              $q .= " ".$wpdb->prefix."term_relationships ";
	      $q .= " LEFT JOIN ";
              $q .= " ".$wpdb->prefix."posts ";
              $q .= " ON ";
              $q .= " ".$wpdb->prefix."term_relationships.object_id = ".$wpdb->prefix."posts.ID ";
	      $q .= " LEFT JOIN ";
              $q .= " ".$wpdb->prefix."term_taxonomy ";
              $q .= " ON ";
              $q .= " ".$wpdb->prefix."term_taxonomy.term_taxonomy_id = ".$wpdb->prefix."term_relationships.term_taxonomy_id ";
	      $q .= " LEFT JOIN ";
              $q .= " ".$wpdb->prefix."terms ";
              $q .= " ON ";
              $q .= " ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_relationships.term_taxonomy_id ";
	      $q .= " WHERE ";
              $q .= " post_type = 'product' ";
              $q .= " AND taxonomy = 'product_cat' ";
	      $q .= " AND  object_id = ".$post_id;
              $t = $wpdb->get_results($q,ARRAY_A);

// OK Let's just take it nice and easy. Just list the goddamn categories oledi
              foreach ($t as $r) {
                print $post_id." ".$r["slug"]." ".$r["name"]."<br>\n";
              }

              // $term_id = the id of the product_cat in wp_terms
              foreach ($t as $r) {
                $term_id = 0;
                $term_taxonomy_id = 0;
                $slug_new = $r["slug"]."-".$lwam_language_code;
                $prodcat_new = $r["name"];

                $qc = "SELECT ";
                $qc .= " * ";
                $qc .= " FROM ";
                $qc .= $wpdb->prefix."terms ";
                $qc .= " WHERE slug='".$slug_new."'";
//wp_die($qc);
                $tc = $wpdb->get_results($qc,ARRAY_A);
                foreach ($tc as $rc) {
                  $term_id = $rc["term_id"];
                  $term_taxonomy_id = $r["term_taxonomy_id"];
                }
                // Do we need to create the category
                if ($term_id == 0) {
                  $qi = "INSERT INTO ";
                  $qi .= $wpdb->prefix."terms (";
                  $qi .= "name";
                  $qi .= ",slug";
                  $qi .= ") VALUES (";
                  $qi .= "'".$prodcat_new."'";
                  $qi .= ",'".$slug_new."'";
                  $qi .= ")";
                  $ti = $wpdb->query($qi);
                  $term_id = $wpdb->insert_id;
                  //----------------
                  // Taxonomy stuffs
                  //----------------
                  $qi = "INSERT INTO ";
                  $qi .= $wpdb->prefix."term_taxonomy (";
                  $qi .= "term_id";
                  $qi .= ",taxonomy";
                  $qi .= ",description";
                  $qi .= ",parent";
                  $qi .= ",count";
                  $qi .= ") VALUES (";
                  $qi .= $term_id;
                  $qi .= ",'product_cat'";
                  $qi .= ",'".$prodcat_new."'";
                  $qi .= ",0";
                  $qi .= ",0";
                  $qi .= ")";
                  $ti = $wpdb->query($qi);
                  $term_taxonomy_id = $wpdb->insert_id;
                }
                // So, here we have the term_taxonomy_id in $r
                // We use it to get the trid
                $element_type = "tax_product_cat";
                // From https://wpml.org/forums/topic/add-a-taxonomy-to-three-languages-programmatically/
                $cat_trid = $sitepress->get_element_trid($r["term_taxonomy_id"], $element_type);
                // associate the translated term to the original           
                $sitepress->set_element_language_details($term_taxonomy_id, $element_type, $cat_trid, $lwam_language_code, $sitepress->get_default_language());
                //---------------------------------------------
                // And finally link the product to the category
                //---------------------------------------------
                wp_set_object_terms( $post_translated_id , $term_id, 'product_cat' );
              }
// IN PROGRESS END
}




              //----------------------------------------------
              // OK - we now have a translated post, wat meer?
              //----------------------------------------------
              $wpdb->update(
                $wpdb->prefix.'icl_translations',
                array(
                  'trid' => $trid,
                  'element_type' => 'post_product',
                  'language_code' => $lwam_language_code,
                  'source_language_code' => $default_language  // should this be 'en'? We do not know.
                ),
                array(
                  'element_id' => $post_translated_id
                )
              );
              $sitepress->set_element_language_details(
                $post_translated_id,
                'post_product',
                $trid,
                $lwam_language_code,
                $sitepress->get_default_language(),
                true
              );
              //-------------------------------------
              // This section, I don't know why we do
              //-------------------------------------
              $data=array();
              $data['title_'.$lwam_language_code] = $product_name;
              duplicate_product_post_meta($post_id, $post_translated_id, $data);
              //-----------------------------
              // Now we get custom attributes
              //-----------------------------
//              $orig_product_attrs = get_product_atributes($post_id);
//              $trnsl_labels = get_option('wcml_custom_attr_translations');
//              foreach ($orig_product_attrs as $key => $orig_product_attr) {
//                if (isset($data[$key . '_' . $lwam_language_code]) && !is_array($data[$key . '_' . $lwam_language_code])) {
//                  //----------------------------------
//                  // Get translation values from $data
//                  //----------------------------------
//                  $trnsl_labels[$lwam_language_code][$key] = $data[$key . '_name_' . $lwam_language_code];
//                  $orig_product_attrs[$key]['value'] = $data[$key . '_' . $lwam_language_code];
//                } else {
//                  $orig_product_attrs[$key]['value'] = '';
//                }
//              }
//              update_option('wcml_custom_attr_translations', $trnsl_labels);
              //-----------------------------
              // update "_product_attributes"
              //-----------------------------
              update_post_meta($post_translated_id, '_product_attributes', $orig_product_attrs);
              sync_default_product_attr($post_id, $post_translated_id, $lwam_language_code);
              //-----------
              // Sync media
              //-----------
              sync_thumbnail_id($post_id,  $post_translated_id, $lwam_language_code);
              sync_product_gallery($post_id);
              //----------------
              // Sync taxonomies
              //----------------
              sync_product_taxonomies($post_id, $post_translated_id, $lwam_language_code);
              //----------------------------
              // Synchronize post variations
              //----------------------------
              // $this->sync_product_variations($post_id, $post_translated_id, $lwam_language_code, $data);
              // $this->sync_grouped_products($product_id, $post_translated_id, $lwam_language_code);
              
              $number_imported++;
            }
          }
        }
      }
    }
    $return = __("Antal importerade","lwattentionmedia").": ".$number_imported."<br>\n".__("Antal EJ importerade","lwattentionmedia").": ".$number_error."<br>\n".$return;
    return $return;
  }
  //----------------------------
  // Duplicate product post meta
  //----------------------------
  function duplicate_product_post_meta($original_product_id, $trnsl_product_id, $data = false , $add = false ){
    global $sitepress;
    $settings = $sitepress->get_settings();
    $lang = $sitepress->get_language_for_element($trnsl_product_id,'post_product');
    $all_meta = get_post_custom($original_product_id);
    unset($all_meta['_thumbnail_id']);
    foreach(wp_get_post_terms($original_product_id, 'product_type', array("fields" => "names")) as $type){
      $product_type = $type;
    }
    foreach ($all_meta as $key => $meta) {
      if (isset($settings['translation-management']['custom_fields_translation'][$key]) && 
          $settings['translation-management']['custom_fields_translation'][$key] == 0) {
        continue;
      }
      foreach ($meta as $meta_value) {
        $meta_value = maybe_unserialize($meta_value);
        if ($data) {
          if (isset($data[$key.'_'.$lang]) && 
              isset($settings['translation-management']['custom_fields_translation'][$key]) && 
              $settings['translation-management']['custom_fields_translation'][$key] == 2) {
            if ($key == '_file_paths') {
              $file_paths = explode("\n",$data[$key.'_'.$lang]);
              $file_paths_array = array();
              foreach($file_paths as $file_path){
                $file_paths_array[md5($file_path)] = $file_path;
              }
              $meta_value = $file_paths_array;
            } elseif ($key == '_downloadable_files') {
              $file_paths_array = array();
              foreach($data[$key.'_'.$lang] as $file_path) {
                $key_file = md5($file_path['file'].$file_path['name']);
                $file_paths_array[$key_file]['name'] = $file_path['name'];
                $file_paths_array[$key_file]['file'] = $file_path['file'];
              }
              $meta_value = $file_paths_array;
            } else {
              $meta_value = $data[$key.'_'.$lang];
            }
          }
          if (isset($data['regular_price_'.$lang]) && isset($data['sale_price_'.$lang]) && $product_type == 'variable') {
            switch($key) {
              case '_min_variation_sale_price':
                $meta_value = count(array_filter($data['sale_price_'.$lang]))?min(array_filter($data['sale_price_'.$lang])):'';
                break;
              case '_max_variation_sale_price':
                $meta_value = count(array_filter($data['sale_price_'.$lang]))?max(array_filter($data['sale_price_'.$lang])):'';
                break;
              case '_min_variation_regular_price':
                $meta_value = count(array_filter($data['regular_price_'.$lang]))?min(array_filter($data['regular_price_'.$lang])):'';
                break;
              case '_max_variation_regular_price':
                $meta_value = count(array_filter($data['regular_price_'.$lang]))?max(array_filter($data['regular_price_'.$lang])):'';
                break;
              case '_min_variation_price':
                if (count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = min(array_filter($data['sale_price_'.$lang]));
                } elseif (count(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = min(array_filter($data['regular_price_'.$lang]));
                } else {
                  $meta_value = '';
                }
                break;
              case '_max_variation_price':
                if (count(array_filter($data['sale_price_'.$lang])) && max(array_filter($data['sale_price_'.$lang]))>max(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = max(array_filter($data['sale_price_'.$lang]));
                } elseif(count(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = max(array_filter($data['regular_price_'.$lang]));
                } else {
                  $meta_value = '';
                }
                break;
              case '_price':
                if (count(array_filter($data['sale_price_'.$lang])) && min(array_filter($data['sale_price_'.$lang]))<min(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = min(array_filter($data['sale_price_'.$lang]));
                } elseif(count(array_filter($data['regular_price_'.$lang]))) {
                  $meta_value = min(array_filter($data['regular_price_'.$lang]));
                } else {
                  $meta_value = '';
                }
                break;
            }
          } else {
            if ($key == '_price' && isset($data['sale_price_'.$lang]) && isset($data['regular_price_'.$lang])) {
              if ($data['sale_price_'.$lang]) {
                $meta_value = $data['sale_price_'.$lang];
              } else {
                $meta_value = $data['regular_price_'.$lang];
              }
            }
          }
          $meta_value = apply_filters('wcml_meta_value_before_add',$meta_value,$key);
          if ($add) {
            add_post_meta($trnsl_product_id, $key, $meta_value, true);
          } else {
            update_post_meta($trnsl_product_id,$key,$meta_value);
          }
        } else {
          if (isset($settings['translation-management']['custom_fields_translation'][$key]) && $settings['translation-management']['custom_fields_translation'][$key] == 1) {
            $meta_value = apply_filters('wcml_meta_value_before_add',$meta_value,$key);
            update_post_meta($trnsl_product_id, $key, $meta_value);
          }
        }
      }
    }
    do_action('wcml_after_duplicate_product_post_meta',$original_product_id, $trnsl_product_id, $data);
  }

  function get_product_atributes($product_id) {
    $attributes = get_post_meta($product_id,'_product_attributes',true);
    if (!is_array($attributes)) {
      $attributes = array();
    }
    return $attributes;
  }
  function sync_default_product_attr($orig_post_id,$transl_post_id,$lang) {
    global $wpdb;
    $original_default_attributes = get_post_meta($orig_post_id, '_default_attributes', TRUE);
    if (!empty($original_default_attributes)) {
      $unserialized_default_attributes = array();
      foreach (maybe_unserialize($original_default_attributes) as $attribute => $default_term_slug) {
        // get the correct language
        if (substr($attribute, 0, 3) == 'pa_') {
          //attr is taxonomy
          $default_term = get_term_by('slug', $default_term_slug, $attribute);
          $tr_id = icl_object_id($default_term->term_id, $attribute, false, $lang);
          if( $tr_id) {
            $translated_term = $wpdb->get_row($wpdb->prepare("
              SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $attribute)
            );
            $unserialized_default_attributes[$attribute] = $translated_term->slug;
          } else {
            //custom attr
            $orig_product_attributes = get_post_meta($orig_post_id, '_product_attributes', true);
            $unserialized_orig_product_attributes = maybe_unserialize($orig_product_attributes);
            if (isset($unserialized_orig_product_attributes[$attribute])) {
              $orig_attr_values = explode('|',$unserialized_orig_product_attributes[$attribute]['value']);
              foreach ($orig_attr_values as $key=>$orig_attr_value) {
                $orig_attr_value = str_replace(' ','-',trim($orig_attr_value));
                $orig_attr_value = lcfirst($orig_attr_value);
                if ($orig_attr_value == $default_term_slug) {
                  $tnsl_product_attributes = get_post_meta($transl_post_id, '_product_attributes', true);
                  $unserialized_tnsl_product_attributes = maybe_unserialize($tnsl_product_attributes);
                  if (isset($unserialized_tnsl_product_attributes[$attribute])) {
                    $trnsl_attr_values = explode('|',$unserialized_tnsl_product_attributes[$attribute]['value']);
                    $trnsl_attr_value = str_replace(' ','-',trim($trnsl_attr_values[$key]));
                    $trnsl_attr_value = lcfirst($trnsl_attr_value);
                    $unserialized_default_attributes[$attribute] = $trnsl_attr_value;
                  }
                }
              }
            }
          }
        }
        $data = array('meta_value' => maybe_serialize($unserialized_default_attributes));
        $where = array('post_id' => $transl_post_id, 'meta_key' => '_default_attributes');
        $wpdb->update($wpdb->postmeta, $data, $where);
      }
    }
  }

  function sync_thumbnail_id($orig_post_id,$trnsl_post_id,$lang){
    if (defined('WPML_MEDIA_VERSION')) {
      $thumbnail_id = get_post_meta($orig_post_id,'_thumbnail_id',true);
      $trnsl_thumbnail = icl_object_id($thumbnail_id,'attachment',false,$lang);
      if (!is_null($trnsl_thumbnail)) {
        update_post_meta($trnsl_post_id,'_thumbnail_id',$trnsl_thumbnail);
      } else {
        update_post_meta($trnsl_post_id,'_thumbnail_id','');
      }
      update_post_meta($orig_post_id,'_wpml_media_duplicate',1);
      update_post_meta($orig_post_id,'_wpml_media_featured',1);
    }
  }
  function sync_product_gallery($product_id){
    if (!defined('WPML_MEDIA_VERSION')) {
      return;
    }
    global $wpdb,$sitepress;
    $product_gallery = get_post_meta($product_id,'_product_image_gallery',true);
    $gallery_ids = explode(',',$product_gallery);
    $trid = $sitepress->get_element_trid($product_id,'post_product');
    $translations = $sitepress->get_element_translations($trid,'post_product',true);
    foreach ($translations as $translation) {
      $duplicated_ids = '';
      if ($translation->language_code != $sitepress->get_default_language()) {
        foreach ($gallery_ids as $image_id) {
          $duplicated_id = icl_object_id($image_id,'attachment',false,$translation->language_code);
          if (!is_null($duplicated_id)) {
            $duplicated_ids .= $duplicated_id.',';
          }
        }
        $duplicated_ids = substr($duplicated_ids,0,strlen($duplicated_ids)-1);
        update_post_meta($translation->element_id,'_product_image_gallery',$duplicated_ids);
      }
    }
  }
  function sync_product_taxonomies($original_product_id,$tr_product_id,$lang){
    global $sitepress,$wpdb;
    remove_filter('get_term', array($sitepress,'get_term_adjust_id')); // AVOID filtering to current language
    $taxonomies = get_object_taxonomies('product');
    foreach ($taxonomies as $taxonomy) {
      $terms = get_the_terms($original_product_id, $taxonomy);
      $terms_array = array();
      if ($terms) {
        foreach ($terms as $term) {
          if ($term->taxonomy == "product_type") {
            $terms_array[] = $term->name;
            continue;
          }
          $tr_id = icl_object_id($term->term_id, $taxonomy, false, $lang);
          if (!is_null($tr_id)) {
            // Not using get_term - unfiltered get_term
            $translated_term = $wpdb->get_row($wpdb->prepare("
              SELECT * FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id = t.term_id WHERE t.term_id = %d AND x.taxonomy = %s", $tr_id, $taxonomy)
            );
            if (is_taxonomy_hierarchical($taxonomy)) {
              $terms_array[] = $translated_term->term_id;
            } else {
              $terms_array[] = $translated_term->name;
            }
          }
        }
        wp_set_post_terms($tr_product_id, $terms_array, $taxonomy);
      }
    }
  }


// START HERE


// THIS FOR LANGUAGE IMPORT
// From https://wpml.org/forums/topic/import-woocommerce-translations/
//
//
//--------------------------------
// THIS FOR GENERAL PRODUCT IMPORT - TO REMEMBER WHAT THE HELL WE ARE DOING
//--------------------------------
// From http://wordpress.stackexchange.com/questions/137501/how-to-add-product-in-woocommerce-with-php-code
?>
