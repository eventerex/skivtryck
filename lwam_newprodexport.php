<?php
// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);
  //----------------------------------------------------
  // lwam_newprodexport.php - Export products and prices
  // Copyright (c) Eventerex
  //----------------------------------------------------
  //----------
  // PHPOffice
  //----------
  require_once("vendor/autoload.php");
  if (!defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN',0);
  }
  use PhpOffice\PhpSpreadsheet\Spreadsheet;
  use PhpOffice\PhpSpreadsheet\Spreadsheet\Writer;
  use PhpOffice\PhpSpreadsheet\Spreadsheet\Writer\Xlsx;
  //-------
  // A form
  //-------
  function lwam_newprodexport_form() {
    global $wpdb;
    $lwam_product_category_ids = [];
    //-------------------
    // Get all categories
    //-------------------
    $all_categories = lwam_getpricecats();
    print "<h2>".__("Exportera produkpriser per produkt","lwattentionmedia")."</h2>\n";
    print "<div class=\"wrap\">\n";
    print __("Detta program exporterar produktnummer, produktnamn och prisstafflingar till Excel. ");
    print __("Den exporterade Excel-filen kan sedan användar för import till en annan sajt.");
    print "<p>\n";
    //-----------
    // Input form
    //-----------
    print "<form id=\"exportform\" class=\"form\" method=\"post\" enctype=\"multipart/form-data\">\n";
    print "<p><label for=\"lwam_product_tocategories\">".__("Välj produktkategorier att exportera","lwattentionmedia").":</label><br />\n";
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
    print "<p class=\"submit\">\n";
    print "  <input onclick=\"lwam_newprodexport_create();return false;\" type=\"button\" class=\"button button-primary btn btn-primary\" name=\"submit\" value=\"".__("Skapa Excel-fil","lwattentionmedia")."\" />\n";
    print "</p>\n";
    print "</form>\n";
    print "</div>\n";
    print "<script>\n";
print "function lwam_newprodexport_create() {\n";
print "  let data = new FormData(exportform);\n";
print "  data.append('action','lwam_newprodexport_create');\n";
print "  fetch('".admin_url('admin-ajax.php')."',{\n";
print "    method: 'POST',\n";
print "    body: data\n";
print "  })\n";
print "  .then(response => response.blob())\n";
print "  .then(function(theblob) {\n";
print "    var fileURL = window.URL.createObjectURL(theblob);\n";
print "    var fileLink = document.createElement('a');\n";
print "    fileLink.href = fileURL;\n";
print "    fileLink.setAttribute('download', 'Produkter.xlsx');\n";
print "    document.body.appendChild(fileLink);\n";
print "    fileLink.click();\n";
print "  });\n";
print "}\n";
//    print "exportform.onsubmit = async(e) => {\n";
//    print "  e.preventDefault();\n";
//    print "  let data = new FormData(exportform);\n";
//    print "  data.append('action','lwam_newprodexport_create');\n";
//    print "  let response = await fetch('".admin_url('admin-ajax.php')."',{\n";
//    print "    method: 'POST',\n";
//    print "    body: data\n";
//    print "  });\n";
//    print "  if (response.ok) {\n";
//    print "    let result = await response.blob();\n";
//    print "    var fileURL = window.URL.createObjectURL(new Blob([result]));\n";
//    print "    var fileLink = document.createElement('a');\n";
//    print "    fileLink.href = fileURL;\n";
//    print "    fileLink.setAttribute('download', 'Produkter.xlsx');\n";
//    print "    document.body.appendChild(fileLink);\n";
//    print "    fileLink.click();\n";
//    print "  }\n";
//    //print "  console.log(response);\n";
//    print "}\n";
    print "</script>\n";
  }
  //---------------
  // AJAX functions
  //---------------
  function lwam_newprodexport_create() {
    global $wpdb;
    global $woocommerce;
    $args = array(
      'post_type'             => 'product',
      'post_status'           => 'publish',
      'posts_per_page'        => -1,
      'tax_query'             => array(
        array(
          'taxonomy'      => 'product_cat',
          'field' => 'term_id', //This is optional, as it defaults to 'term_id'
          'terms'         => $_REQUEST["lwam_product_category_ids"],
          'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
        ),
        array(
          'taxonomy'      => 'product_visibility',
          'field'         => 'slug',
          'terms'         => 'exclude-from-catalog', // Possibly 'exclude-from-search' too
          'operator'      => 'NOT IN'
        )
      )
    );
    $result = new WP_Query($args);
    $posts = $result->posts;
    $rows = [];
    $rows[0] = new stdClass();
    $key = "sku";
    $rows[0]->$key = 'Artikelnr';
    $key = "xname";
    $rows[0]->$key = 'Namn';
    foreach ($posts as $post) {
      $row = new stdClass();
      $product_id = $post->ID;
      $_product = new WC_Product($product_id);
      $sku = $_product->get_sku();
      $row->sku = $sku;
      $name = $_product->get_name();
      $row->xname = $name;
      $quantity = '0';
      $price = $_product->get_price();
      if (get_post_meta($product_id, '_lwamfq', true)) {
        $fixed_quantity = get_post_meta( $product_id, '_lwamfq', true );
        $fixed_json = html_entity_decode($fixed_quantity);
        $fixed_json = json_decode($fixed_json);
        for($i = 0, $size = count($fixed_json); $i < $size; ++$i) {
          $price = $fixed_json[$i]->lwamfq_price;
          $quantity = $fixed_json[$i]->lwamfq_qty;
          if (!isset($rows[0]->$quantity)) {
            $rows[0]->$quantity = (string)$quantity;
          }
          $row->$quantity = $price;
        }
      } else {
        if (!isset($rows[0]->$quantity)) {
          $rows[0]->$quantity = (string)$quantity;
        }
        $row->$quantity = $price;
      }
      $rows[] = $row;
    }
    //--------------------------------------------------------------------------------------
    // We have all the stuffs in rows, now we need to sort rows[0] and then loop its columns
    // and look up values in each row for the corresponding column key. Phew.
    //--------------------------------------------------------------------------------------
    $columns = (array) $rows[0];
    ksort($columns);
    //----------------------------------
    // Instantiate a new PHPExcel object
    //----------------------------------
    $objPHPExcel = new Spreadsheet();
    //------------------------------------------
    // Set the active Excel worksheet to sheet 0
    //------------------------------------------
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
    //--------------------------------
    // Initialise the Excel row number
    //--------------------------------
    $rowCount = 1;
    //-------------------------------
    // start of printing column names
    //-------------------------------
    $excelcolumn = 'A';
    foreach ($columns as $key=>$column) {
      $objPHPExcel->getActiveSheet()->setCellValue($excelcolumn.$rowCount, $column);
      $excelcolumn++;
    }
    $rowCount++;
    $first = true;
    foreach ($rows as $row) {
      if (!$first) {
        $excelcolumn = 'A';
        foreach ($columns as $key=>$column) {
          if (isset($row->$key)) {
            // print_r($row->$key);
            $value = $row->$key;
          } else {
            $value = "";
          }
          $objPHPExcel->getActiveSheet()->setCellValue($excelcolumn.$rowCount, $value);
          $excelcolumn++;
        }
        $rowCount++;
      }
      $first = false;
    }
    foreach($objPHPExcel->getActiveSheet()->getRowDimensions() as $rd) { $rd->setRowHeight(-1); }
    //-------------------------------------
    // Here figure out how to return a blob
    //-------------------------------------
    $tmpfile = tempnam(plugin_dir_path(__FILE__)."/temp/",'phpxltmp');
    //$tmpfile = tempnam(sys_get_temp_dir(), 'phpxltmp');
    $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($objPHPExcel);
    $objWriter->save($tmpfile);
    $excel_out = file_get_contents($tmpfile);
    //unlink($tmpfile);
    header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
    print $excel_out;
    exit;
  }
  add_action( 'wp_ajax_lwam_newprodexport_create', 'lwam_newprodexport_create' );
  add_action( 'wp_ajax_nopriv_lwam_newprodexport_create', 'lwam_newprodexport_create' );
?>
