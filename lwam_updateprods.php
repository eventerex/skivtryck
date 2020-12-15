<?php
  //---------------------------------------------------
  // lwam_updateprods.php - Update product descriptions
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //---------------------------------------------------
  // Global default language
  $lwam_default_language = "sv";
  //================
  // the import form
  //================
  function lwam_updateprods_form() {
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
    //------------
    // Form header
    //------------
    print "<h2>".__("Uppdatera produktbeskrivningar","lwattentionmedia")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //------------------
    // Check step number
    //------------------
    if (isset($_REQUEST["submit"])) {
      if (empty($_FILES['lwam_filename']['tmp_name'])) {
        $error .= __('Ingen fil vald','lwattentionmedia');
        $errsep = "<br>";
      } else {
        //------------------
        // Read the products
        //------------------
        $infile = $_FILES['lwam_filename']['tmp_name'];
        $prodarray = lwam_update_readproducts($infile);
        lwam_updateprods($prodarray,$lwam_language_code,$lwam_default_language);
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

    print "<p><label for=\"lwam_filename\">".__("Ladda upp fil","lwattentionmedia").":</label>\n";
    print "    <input name=\"lwam_filename\" id=\"lwam_filename\" type=\"file\" value=\"\" aria-required=\"true\" /></p>\n";
    print "<p class=\"submit\"><input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Importera","lwattentionmedia")."\" /></p>\n";
    print "</form>\n";
    print "</div>\n";
  }
  //====================
  // Update the products
  //====================
  function lwam_updateprods($prodarray,$lwam_language_code,$default_language) {
    global $wpdb;
    global $sitepress;
    global $lwam_default_language;
    $sitepress->switch_lang($lwam_default_language, true);
    $sitepress->switch_lang($lwam_default_language, true);
    global $woocommerce;
    foreach ($prodarray as $product) {
      //-----------------------------------------
      // Take away line feed, sloppy but it works
      //-----------------------------------------
      $product_number = str_replace(array("\r", "\n"), '', $product[0]);
      $product_name = $product[2];
      $product_description = $product[3];
      $post_type = "product";
      $newslug = $product_number."-".sanitize_title($product_name);
      //--------------------------------
      // post_id = 0 if it doesn't exist
      //--------------------------------
      $post_id = wc_get_product_id_by_sku($product_number);
      //------------------------------------------------
      // If we have a name, and a numeric product number
      //------------------------------------------------
      if ($product[2] != "" && strlen($product[0])>2) {
        //--------------------------------------------------------------------
        // If the product does not exist in the original language, me no want!
        //--------------------------------------------------------------------
        $post_id = wc_get_product_id_by_sku($product_number);
        if ($post_id != 0) {
          //------------------------
          // Get the translated post
          //------------------------
          $post_translated_id = icl_object_id($post_id,'product',false,$lwam_language_code);
          if ($lwam_language_code == $lwam_default_language) {
            $post_translated_id = $post_id;
          }
          //-----------------------------------------------
          // Update the product description to the variable
          //-----------------------------------------------
          $q = "SELECT * FROM ".$wpdb->prefix."posts WHERE ID=".$post_translated_id;
          $t = $wpdb->get_results($q,ARRAY_A);
          $old_desc = "";
          foreach ($t as $r) {
            $old_desc = $r["post_content"];
          }
          print $product_number."<br>\n";
          $q = "UPDATE ".$wpdb->prefix."posts SET post_content='".esc_sql($product_description)."' WHERE ID=".$post_translated_id;
          $t = $wpdb->query($q);
        }
      }
    }
  }
  //======================================
  // Read the products and return an array
  //======================================
  function lwam_update_readproducts($infile) {
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
//    print "<div style=\"height: 300px;overflow-y: scroll;\">\n";
//    print '<table class="wp-list-table striped">' . "\n";
//    print "<tr>\n";
//    print "<th>Artikelnummer</th>\n";
//    print "<th>Dölj</th>\n";
//    print "<th>Artikelnamn</th>\n";
//    print "<th>Artikelbeskrivning</th>\n";
//    print "<th>Pris</th>\n";
//    print "<th>Vikt</th>\n";
//    print "<th>Artikelgrupp</th>\n";
//    print "</tr>\n";
    foreach ($prodarray as $key => $product) {
      if ($firstline == true) {
        if ($product[2] != "" && is_numeric($product[0])) {
//          print "<tr>\n";
//          // SKU
//          print "<td>".$product[0]."</td>\n";
//          // Hide
//          print "<td>".$product[1]."</td>\n";
//          // Product name
//          print "<td>".$product[2]."</td>\n";
//          // Product description
//          $productname = $product[3];
//          if (strlen($productname) > 30) {
            $productname = substr($productname,0,30)."...";
//          }
//          print "<td>".strip_tags($productname)."</td>\n";
//          // Price
//          print "<td>".$product[5]."</td>\n";
//          // Weight
//          print "<td>".$product[12]."</td>\n";
//          // Product category
//          print "<td>".$product[18]."</td>\n";
//          print "</tr>\n";
        } else {
          unset($prodarray[$key]);
        }
      } else {
        unset($prodarray[$key]);
      }
      $firstline = true;
    }
//    print "</table>\n";
//    print "</div>\n";
    return $prodarray;
  }
?>
