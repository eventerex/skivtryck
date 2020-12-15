<?php
/**
 * Plugin Name: Lightweb Attention Media Plugin
 * Plugin URI: http://lightweb.se
 * Description: Extends WooCommerce for Attention Media
 * Version: 1.0.0
 * Author: Bjorn Pehrson, Lightweb
 * Author URI: http://lightweb.se
 * License: Copyright and All Rights Reserved
 * Text Domain: lwattentionmedia
*/
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
  //=====================
  // INSTALLATION SECTION
  //=====================
  /*-------------------------------*/
  /* Create/Update database tables */
  /*-------------------------------*/
  function lwam_install() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $sql = array();
  }
  /*---------------------------*/
  /* Run installation function */
  /*---------------------------*/
  register_activation_hook( __FILE__, 'lwam_install' );
  //===============
  // PUBLIC SECTION
  //===============
  // Includes go here
  include_once('lwam_account.php');
  //==============
  // ADMIN SECTION
  //==============
  include_once('lwam_newprodimport.php');
  include_once('lwam_updateprods.php');
  include_once('lwam_newprodexport.php');
  //include_once('lwam_prodimport.php');
  include_once('lwam_priceimport.php');
  include_once('lwam_addonimport.php');
  include_once('lwam_pricechange.php');
  include_once('lwam_productadmin.php');
  include_once('lwam_orderadmin.php');
  include_once('lwam_productpage.php');
  include_once('lwam_shoppingcart.php');
  include_once('lwam_checkout.php');
  include_once('lwam_frontpage.php');
  include_once('lwam_specter.php');
  //------------------------------------------
  // Make sure we have the WP_List_Table class
  //------------------------------------------
  if (!class_exists('WP_List_Table')) {
      require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
  }
  //------------------------
  // Classes for admin pages
  //------------------------
  //==============
  // Main function
  //==============
  function lwam_main() {
    global $wpdb;
    print "<div class=\"wrap\">\n";
    print __("Välkommen till Lightweb Attentionmedia!","lwattentionmedia")."\n";
    print "</div>\n";
  }
  //=====================
  // Plugin Settings page
  //=====================
  function lwam_options_page() {
    global $wpdb;
    print "<div class=\"wrap\">\n";
    print "   <h2>".__("LWAM-inställningar","lwattentionmedia")."</h2>\n";
    print "      <form method=\"post\" action=\"options.php\">\n";
    settings_fields( 'lwam-options' );
    do_settings_sections( 'lwam-options' );
    print "<table class=\"form-table\">\n";
    print "    <tr valign=\"top\">\n";
    print "    <th scope=\"row\">".__("Produktkategori för tillägg","lwattentionmedia")."</th>\n";
    print "    <td>";
    //----------------------------
    // Find all product categories
    //----------------------------
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
    print "        <select id=\"lwam_addons_category\" name=\"lwam_addons_category\">\n";
    foreach ($all_categories as $cat) {
      if($cat->category_parent == 0) {
        $category_id = $cat->term_id;       
        print "<option value=\"". $category_id ."\" ";
        if ($category_id == get_option('lwam_addons_category')) {
          print " selected ";
        }
        print ">". $cat->name ."</option>\n";
      }
    }
    print "        </select>\n";
    print "      </td>\n";
    print "    </tr>\n";
    print "    <tr valign=\"top\">\n";
    print "      <th scope=\"row\">".__("Text under töm varukorgen","lwattentionmedia")."</th>\n";
    print "      <td>";
    print "        <input id=\"lwam_text_under_empty_cart\" name=\"lwam_text_under_empty_cart\" value=\"".get_option("lwam_text_under_empty_cart")."\">\n";
    print "      </td>\n";
    print "    </tr>\n";
    print "</table>\n";
    submit_button();
    print "      </form>\n";
    print "</div>\n";
  }
  //===============================
  // admin_menu hook implementation
  //===============================
  function lwam_admin_menu() {
    require_once ABSPATH . '/wp-admin/admin.php';
    add_management_page(__('Ändra Priser','lwattentionmedia'),__('Ändra Priser','lwattentionmedia'),'manage_options','lwam_pricechange','lwam_price_change_form');
    add_management_page(__('Exportera Priser','lwattentionmedia'),__('Exportera Priser','lwattentionmedia'),'manage_options','lwam_newprodexport','lwam_newprodexport_form');
//    add_management_page(__('Importera Produkter','lwattentionmedia'),__('Importera Produkter','lwattentionmedia'),'manage_options','lwam_newimportproducts','lwam_new_import_products_form');
//    add_management_page(__('Fixa Produkter','lwattentionmedia'),__('Fixa Produkter','lwattentionmedia'),'manage_options','lwam_updateprods','lwam_updateprods_form');
    add_management_page(__('Importera Priser','lwattentionmedia'),__('Importera Priser','lwattentionmedia'),'manage_options','lwam_importprices','lwam_import_prices_form');
//    add_management_page(__('Tilldela Tillägg','lwattentionmedia'),__('Tilldela Tillägg','lwattentionmedia'),'manage_options','lwam_importaddons','lwam_import_addons_form');
    //------------------
    // Add settings page
    //------------------
    add_options_page('Lightweb LWAM System', __('LWAM Systeminställningar',"lwattentionmedia"), 'manage_options', 'lwam-plugin', 'lwam_options_page');
    register_setting( 'lwam-options', 'lwam_addons_category' );
    register_setting( 'lwam-options', 'lwam_text_under_empty_cart' );
  }
  add_action('admin_menu', 'lwam_admin_menu');
  //============
  // Add scripts
  //============
  //----------------------------------
  // Enqueue the scripts for front-end
  //----------------------------------
  function lwam_add_scripts() {
    //-----------
    // LWAM stuff
    //-----------
    wp_register_script( 'lwam-script', plugins_url("/js",__FILE__) . '/lwattentionmedia.js' ,array( 'jquery'));
    wp_enqueue_script('lwam-script');
    //---------------------
    // Style for the plugin
    //---------------------
    wp_enqueue_style('lwam_frontend_style', plugins_url('css/lwattentionmedia.css',__FILE__));
  }
  add_action('wp_enqueue_scripts', 'lwam_add_scripts');
  // dequeue cart fragments - DOES NOT WORK
  function dequeue_woocommerce_cart_fragments() { if (is_cart()) wp_dequeue_script('wc-cart-fragments'); }
  add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments', 11); 
  //------------------------------
  // Enqueue the scripts for admin
  //------------------------------
  function lwam_add_admin_scripts() {
    wp_enqueue_style('lwam_backend_style', plugins_url('css/lwattentionmedia-admin.css',__FILE__));
  }
  add_action('admin_enqueue_scripts', 'lwam_add_admin_scripts');
  //==================
  // GENERAL FUNCTIONS
  //==================
  //=================
  // FRONT END FOOTER
  //=================
  function lwam_frontend_footer() {
    global $woocommerce;
    $url = wc_get_checkout_url();
    print "<script>\n";
    print "jQuery(document).ready(function() {\n";
    print "  jQuery('.added-url').attr('href','".$url."');\n";
    print "  jQuery('.cart-link-span').attr('href','".$url."');\n";
    print "  jQuery('.cart-link-span').attr('data-url','".$url."');\n";
    print "});\n";
    print "</script>\n";
    print "<style>\n";
//    print "mark {\n";
//    print "  background-color: white;\n";
//    print "  color: black;\n";
//    print "}\n";
    print "</style>\n";
  }
  add_action( 'wp_footer', 'lwam_frontend_footer', 100 );
  // Force NO decimals on prices
  add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
  //===================
  // DEBUG - Empty cart
  //===================
//  add_action( 'woocommerce_proceed_to_checkout', 'insert_empty_cart_button' );
//  function insert_empty_cart_button() {
//    // Echo our Empty Cart button
//    echo '<input type="submit" class="button" name="lw_empty_cart" value="Empty Cart" />';
//  }
  add_action( 'template_redirect', 'empty_cart_button_handler' );
  function empty_cart_button_handler() {
    global $product;
    global $woocommerce;
    global $woocommerce_wpml;
    global $sitepress;
    $items = WC()->cart->get_cart();
    if( isset( $_POST['lw_empty_cart'] ) && $_SERVER['REQUEST_METHOD'] == "POST" ) {
      $blank = [];
      foreach($items as $key => $valuearray) {
        WC()->session->set('lwam'.$key,$blank);
      }
      WC()->cart->empty_cart( true );
    }
  }
//  function debug_footer() {
//    global $woocommerce;
//    $var = WC()->cart->get_cart();
//    $var = lw_grab_dump($var);
//    echo $var;
//  }
// add_action( 'wp_footer', 'debug_footer', 100 );
  //-------------------
  // Lightweb debug/log
  //-------------------
  if ( ! function_exists('lw_log')) {
    function lw_log ( $log )  {
      $filename = plugin_dir_path(__FILE__)."/lw_log.txt";
      $log = is_array($log) || is_object($log) ? print_r($log,false) : $log;
      $log = date('Y-m-d h:i:s')." ".$log.PHP_EOL;
      $log = str_replace("<br>",PHP_EOL,$log);
      $logresult = file_put_contents($filename, $log, FILE_APPEND);
    }
  }
  function lw_grab_dump($var) {
    ob_start();
    var_dump($var);
    $dump = ob_get_clean();
    $dump = str_replace(array("\n", "\r"), '<br>', $dump);
    return $dump;
  }
