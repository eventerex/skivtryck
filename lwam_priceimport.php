<?php
  //-----------------------------------------------
  // lwam_priceimport - Attentionedia import prices
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------
  //--------------
  // Some includes
  //--------------
  require_once 'PHPExcel/Classes/PHPExcel.php';
  //------------------------
  // Global default language
  //------------------------
  $lwam_default_language = "sv";
  //---------
  // The form
  //---------
  function lwam_import_prices_form() {
    global $wpdb;
    global $woocommerce;
    print "<h2>".__("Importera Priser","lwattentionmedia")."</h2>\n";
    $message = "";
    $error = "";
    $errsep = "";
    //---------------------------
    // Check if we are submitting
    //---------------------------
    if (isset($_REQUEST["submit"])) {
      if (empty($_FILES['lwam_filename']['tmp_name'])) {
        $error .= __('Ingen fil vald','lwattentionmedia');
        $errsep = "<br>";
      } else {
        $infile = $_FILES['lwam_filename']['tmp_name'];
        if (lwam_priceimport_preview($infile)) {
          $message .= lwam_priceimport_import($infile);
        } else {
          $error .= __('Ett fel uppstod när filen skulle läsas','lwattentionmedia');
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
    print "<p><label for=\"lwam_filename\">".__("Ladda upp fil","lwattentionmedia").":</label>\n";
    print "    <input name=\"lwam_filename\" id=\"lwam_filename\" type=\"file\" value=\"\" aria-required=\"true\" /></p>\n";
    print "<p class=\"submit\"><input type=\"submit\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Importera","lwattentionmedia")."\" /></p>\n";
    print "</form>\n";
    print "</div>\n";
  }
  function lwam_priceimport_preview($infile) {
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load($infile);
    $objWorksheet = $objPHPExcel->getActiveSheet();
    $highestRow = $objWorksheet->getHighestRow(); 
    print __("Antal rader: ","lwattentionmedia")." ".($highestRow-2);
    $highestColumn = $objWorksheet->getHighestColumn(); 
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); 
    print '<table class="wp-list-table striped">' . "\n";
    for ($row = 1; $row <= $highestRow; ++$row) {
      print '<tr>' . "\n";
      for ($col = 0; $col <= $highestColumnIndex; ++$col) {
        print '<td>' . $objWorksheet->getCellByColumnAndRow($col, $row)->getValue() . '</td>' . "\n";
      }
      print '</tr>' . "\n";
    }
    print '</table>' . "\n";
    return true;
  }
  function lwam_priceimport_import($infile) {
    $return = "";
    $retsep = "";
    $number_imported = 0;
    $number_error = 0;
    $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load($infile);
    $objWorksheet = $objPHPExcel->getActiveSheet();
    $highestRow = $objWorksheet->getHighestRow(); 
    $highestColumn = $objWorksheet->getHighestColumn(); 
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); 
    //--------------------------------------------------------
    // First we get the intervals and which column they are in
    //--------------------------------------------------------
    $numberarray = array();
    for ($col = 1; $col<=$highestColumnIndex; ++$col) {
      $number = $objWorksheet->getCellByColumnAndRow($col,1)->getValue();
      if (is_numeric($number)) {
        $numberarray[$col] = $number;
      }
    }
    for ($row = 2; $row <= $highestRow; ++$row) {
      $product_number = $objWorksheet->getCellByColumnAndRow(0, $row)->getValue();
      $get_product  = lwam_priceimport_getproduct($product_number);
      if (is_numeric($get_product)) {
        $pricearray = array();
        //---------------
        // Get the prices
        //---------------
        for ($col = 1; $col<=$highestColumnIndex; ++$col) {
          $number = $objWorksheet->getCellByColumnAndRow($col,$row)->getValue();
          if (is_numeric($number)) {
            $pricearray[$col] = $number;
          } else {
            $pricearray[$col] = 0;
          }
        }
        //------------------------------
        // Actual price import goes here
        //------------------------------
        $prodimp = lwam_importproductprices($get_product,$numberarray,$pricearray);
        $number_imported++;
      } else {
        $return .= $retsep.$get_product;
        $retsep = "<br>\n";
        $number_error++;
      }
    }
    $return = __("Antal importerade","lwattentionmedia").": ".$number_imported."<br>\n".__("Antal EJ importerade","lwattentionmedia").": ".$number_error."<br>\n".$return;
    return $return;
  }
  function lwam_importproductprices($post_id,$numberarray,$pricearray) {
    $return = "";
    // We need to figure out which one is default
    $default = 0;
    if (get_post_meta( $post_id, '_lwamfq', true )) {
      $fixed_quantity = get_post_meta( $post_id, '_lwamfq', true );
      $fixed_json = html_entity_decode($fixed_quantity);
      $fixed_json = json_decode($fixed_json);
      for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
        if ($fixed_json[$i]->lwamfq_default == 1) {
          $default = $fixed_json[$i]->lwamfq_qty;
        }
      }
    }
    delete_post_meta($post_id, '_lwamfq');
    delete_post_meta($post_id, '_lwamone');
    //----------------------------------------------------------------------
    // First we want to check if the product has a price for 1 product,
    // in which case we should set the 'normal' price instead of an interval
    //----------------------------------------------------------------------
    $hasone = 0;
    $oneprice = 0;
    for ($i=0;$i<count($pricearray);$i++) {
      if (isset($numberarray[$i]) && is_numeric($numberarray[$i])) {
        if (is_numeric($pricearray[$i])) {
          if ($pricearray[$i] != 0) {
            if ($numberarray[$i] == 1) {
              $hasone = 1;
              $oneprice = $pricearray[$i];
            }
          }
        }
      }
    }
//    if ($hasone == 0) {
      //------------------
      // Build a new json?
      //------------------
      $jsonarray = array();
      for ($i=0;$i<count($pricearray);$i++) {
        if (isset($numberarray[$i]) && is_numeric($numberarray[$i])) {
          if (is_numeric($pricearray[$i])) {
            if ($pricearray[$i] != 0) {
              $thisdefault = 0;
              if ($numberarray[$i] == $default) {
                $thisdefault = 1;
              }
              $thisprice = array(
                "lwamfq_qty" => $numberarray[$i],
                "lwamfq_desc" => $numberarray[$i],
                "lwamfq_price" => $pricearray[$i],
                "lwamfq_default" => $thisdefault
              );
              array_push($jsonarray,$thisprice);
              if ($thisdefault == 1) {
                $oneprice = $pricearray[$i];
              }
            }
          }
        }
      }
//    }
    $jsonjson = json_encode($jsonarray);
    $jsonhtml = htmlentities($jsonjson);
    update_post_meta($post_id,"_lwamfq",$jsonhtml);
    //-----------------------------------------
    // Always set the regular WooCommerce Price
    //-----------------------------------------
    update_post_meta($post_id, '_price', $oneprice);
    update_post_meta($post_id, '_lwamone',$hasone);
    return $return;
  }
  function lwam_priceimport_getproduct($product_number) {
    global $wpdb;
    global $woocommerce;
    $return = "";
    $post_id = wc_get_product_id_by_sku($product_number);
    if ($post_id == 0 || $post_id == null) {
      $return .= __("Artikelnummer","lwattentionmedia")." ".$product_number." ".__("hittades inte","lwattentionmedia");
    } else {
      $return = $post_id;
    }
    return $return;
  }
?>
