<?php
//ini_set('display_startup_errors',1);
//ini_set('display_errors',1);
//error_reporting(-1);
  //-----------------------------------------------------------------------------
  // lwam_productadmin.php - Functions for attentionmedia products admin handling
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------------------------------------
  //-------------------------------------------------
  // Add a new setting for products - Add-On-Products
  //-------------------------------------------------
  add_filter( 'woocommerce_product_data_tabs', 'lwam_add_product_data_tab' , 99 , 1 );
  function lwam_add_product_data_tab( $product_data_tabs ) {
    $product_data_tabs['lwam-custom-tab'] = array(
        'label' => __( 'LWAM-optioner', 'lwattentionmedia' ),
        'target' => 'lwam_product_data',
    );
    return $product_data_tabs;
  }
  add_action( 'woocommerce_product_data_panels', 'lwam_add_product_data_fields' );
  //-------------------------------
  // Add the fields for the setting
  //-------------------------------
  function lwam_add_product_data_fields() {
    global $sitepress;
    global $woocommerce, $post;
    global $wpdb;
    $custom_fields = get_post_custom($post->ID);
    $lwam_front_image_url = get_post_meta($post->ID,'_lwam_front_image_url',true);
    $lwam_template_url = get_post_meta($post->ID,'_lwam_template_url',true);
    // Whole tab DIV
    print "<div id=\"lwam_product_data\" class=\"panel woocommerce_options_panel wc-metaboxes-wrapper\">\n";
    // Template download link
    print "<div class=\"options-group\">\n";
    print "    <div class=\"postbox lwam_templates_container\">\n";
    print "      <button type=\"button\" class=\"handlediv button-link\" aria-expanded=\"true\">\n";
    print "        <span class=\"screen-reader-text\">".__("Toggle panel", 'lwam') . "</span>\n";
    print "        <span class=\"toggle-indicator\" aria-hidden=\"true\"></span>\n";
    print "      </button>\n";
    print "      <h2><span>".__("Tryckmall",'lwattentionmedia')."</span></h2>\n";
    print "      <div id=\"lwam_templates\">\n";
    print "        <div class=\"inside lwam_templates_container\">\n";
    print "          <p class=\"form-field \">\n";
    print "            <label for=\"_lwam_template_url\">".__("Tryckmall länk","lwattentionmedia")."</label><span class=\"woocommerce-help-tip\" data-tip=\"Nerladdningslänk.\"></span><input type=\"text\" style=\"\" name=\"_lwam_template_url\" id=\"_lwam_template_url\" value=\"".$lwam_template_url."\" placeholder=\"\" /> </p>\n";
    print "        </div>\n";
    print "      </div>\n";
    print "    </div>\n";
    print "</div>\n";
    //----------------------
    // Fixed quantity prices
    //----------------------
    // Options group DIV
    print "  <div class=\"options-group\">\n";
    print "    <input type=\"hidden\" id=\"_lwamfq\" name=\"_lwamfq\" value=\"".(!empty($custom_fields["_lwamfq"][0])? $custom_fields["_lwamfq"][0] : '')."\" />\n";
    // Postbox DIV
    print "    <div class=\"postbox lwamfq_price_table_container\">\n";
    print "      <button type=\"button\" class=\"handlediv button-link\" aria-expanded=\"true\">\n";
    print "        <span class=\"screen-reader-text\">".__("Toggle panel", 'lwam') . "</span>\n";
    print "        <span class=\"toggle-indicator\" aria-hidden=\"true\"></span>\n";
    print "      </button>\n";
    print "      <h2><span>".__("Pris-stafflingar",'lwattentionmedia')."</span></h2>\n";
    // Data table DIV
    print "      <div id=\"lwamfq_price_data_table\">\n";
    // Inside DIV
    print "        <div class=\"inside lwamfq_price_table_container\" data-role-key=\"all\">\n";
    print "          <table class=\"table lwamfq_price_table\">\n";
    print "            <thead>\n";
    print "              <tr>\n";
    print "                <th class=\"lwamfq_qty\">".__('Antal', 'lwattentionmedia')."</th>\n";
    print "                <th class=\"lwamfq_desc\">".__('Beskrivning', 'lwattentionmedia')."</th>\n";
    print "                <th class=\"lwamfq_price\">".__('Pris per st', 'lwattentionmedia')."</th>\n";
    print "                <th class=\"lwamfq_default\">".__('Förvald', 'lwattentionmedia')."</th>\n";
    print "                <th class=\"lwamfq_actions\">".__('Åtgärd', 'lwattentionmedia')."</th>\n";
    print "              </tr>\n";
    print "            </thead>\n";
    print "            <tbody></tbody>\n";
    print "          </table>\n";
    print "          <p><a class=\"button button-primary lwamfq_add_price\">\n";
    print __('Add...', 'lwam');
    print "</a></p>\n";
    print "          <br>\n";
    // End inside DIV
    print "        </div>\n";
    // End data table DIV
    print "      </div>\n";
    // Hidden DIV
    print "      <div style=\"display: none;\">\n";
    print "        <table id=\"lwamfq_template\" class=\"lwamfq hidden\">\n";
    print "          <tr>\n";
    print "            <td><input placeholder=\"\" type=\"text\" class=\"lwamfq_input_qty\" data-name=\"lwamfq_qty\" /></td>\n";
    print "            <td><input placeholder=\"\" type=\"text\" class=\"lwamfq_input_desc\" data-name=\"lwamfq_desc\" value=\"\" /></td>\n";
    print "            <td><input placeholder=\"\" type=\"text\" class=\"lwamfq_input_price\" data-name=\"lwamfq_price\" /></td>\n";
    print "            <td><input placeholder=\"\" type=\"checkbox\" value=\"1\" class=\"lwamfq_input_default\" data-name=\"lwamfq_default\" /></td>\n";
    print "            <td>";
    print "<a class=\"lwamfq_delete button\"><span class=\"dashicons dashicons-no\"></span></a>\n";
    print "</td>\n";
    print "          </tr>\n";
    print "        </table>\n";
    // End hidden DIV
    print "      </div>\n";
    // End postbox DIV
    print "    </div>\n";
    // End options-group DIV
    print "  </div>\n";
    //-------------------------
    // Optional add-on products
    //-------------------------
    // Options group DIV
    print "  <div class=\"options-group\">\n";
    print "    <input type=\"hidden\" id=\"_lwampo\" name=\"_lwampo\" value=\"".(!empty($custom_fields["_lwampo"][0])? $custom_fields["_lwampo"][0] : '')."\" />\n";
    // Postbox DIV
    print "      <div class=\"postbox lwamfq_option_table_container\">\n";
    print "        <button type=\"button\" class=\"handlediv button-link\" aria-expanded=\"true\">\n";
    print "          <span class=\"screen-reader-text\">".__("Toggle panel", 'lwam') . "</span>\n";
    print "          <span class=\"toggle-indicator\" aria-hidden=\"true\"></span>\n";
    print "        </button>\n";
    print "        <h2><span>".__("Valfria tillägg",'lwattentionmedia')."</span></h2>\n";
    // Data table DIV
    print "        <div id=\"lwampo_options_data_table\">\n";
    // Inside DIV
    print "          <div class=\"inside lwampo_options_table_container\" data-role-key=\"all\">\n";
    print "            <table class=\"table lwampo_options_table\">\n";
    print "              <thead>\n";
    print "                <tr>\n";
    print "                  <th class=\"lwampo_seq\">".__('#', 'lwattentionmedia')."</th>\n";
    print "                  <th class=\"lwampo_caption\">".__('Beskrivning', 'lwattentionmedia')."</th>\n";
    print "                  <th class=\"lwampo_prod\">".__('Produkt(er)', 'lwattentionmedia')."</th>\n";
//    print "                  <th class=\"lwampo_selprod\">".__('Valda', 'lwattentionmedia')."</th>\n";
    print "                  <th class=\"lwampo_opt\">".__('Antal', 'lwattentionmedia')."</th>\n";
    print "                  <th class=\"lwampo_opt_hasno\">".__('Obl.', 'lwattentionmedia')."</th>\n";
    print "                  <th class=\"lwampo_actions\">".__('Åtgärd', 'lwattentionmedia')."</th>\n";
    print "                </tr>\n";
    print "              </thead>\n";
    print "              <tbody></tbody>\n";
    print "            </table>\n";
    print "            <p><a class=\"button button-primary lwampo_add_product\">\n";
    print __('Add...', 'lwam');
    print "</a></p>\n";
    print "            <br>\n";
    // End Inside DIV
    print "          </div>\n";
    // End Data DIV
    print "        </div>\n";
    // Hidden DIV
    print "        <div style=\"display: none;\">\n";
    print "          <table id=\"lwampo_template\" class=\"lwampo hidden\">\n";
    print "            <tr style=\"padding-bottom: 30px !important;\">\n";
    print "              <td><input style=\"width:30px !important;\" placeholder=\"\" size=3 type=\"text\" class=\"lwampo_input_seq\" data-name=\"lwampo_seq\" /></td>\n";
    print "              <td>";
    print "              <input placeholder=\"\" style=\"width: 130px ! important;\" type=\"text\" class=\"lwampo_input_caption\" data-name=\"lwampo_caption\" />";
    print "              </td>\n";
    print "              <td>";
    print "                <select style=\"width: 100% !important;float: none;;\" multiple class=\"lwampo_input_prod\" data-name=\"lwampo_prod\">\n";
    //--------------------------------
    // Fill up option products options
    //--------------------------------
    //---------------------------------------------------------------------------------------------
    // FIX Get all products in default language - We really need to say which category is 'add-ons'
    //---------------------------------------------------------------------------------------------
    $category_id = get_option('lwam_addons_category');
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
    $t = new WP_Query( $args );
    $options = "";
    while ($t->have_posts()) {
      $t->the_post();
      $sku = get_post_meta( $t->post->ID, '_sku', true );
      // Here we want to get all categories that the product belongs to and
      // if there is a category that is not addons, we show that
      $product = wc_get_product($t->post->ID);
      $catids = $product->get_category_ids();
      $catname = '';
      if (count($catids) == 1) {
        $catterm = get_term_by('id',$catids[0],'product_cat');
        $catname = $catterm->name;
      }
      if (count($catids) > 1) {
        for ($i = 0; $i < count($catids); $i++) {
          if ($catids[$i] != $category_id) {
            $catterm = get_term_by('id',$catids[0],'product_cat');
            $catname = $catterm->name;
          }
        }
      }
      $options .= "<option value=\"".$t->post->ID."\">".$catname." : ".$t->post->post_title."</option>\n";
      wp_reset_postdata();
    }
    print $options;
    print "                </select>\n";
    //print "              </td>\n";
//    print "              <td>";
print "<br><b><strong>".__("Valda","lwattentionmedia")."</strong></b><br>\n";
    print "                <select style=\"width: 100%;float: none !important;\" multiple class=\"lwampo_input_selprod\" data-name=\"lwampo_selprod\">\n";
    print "                </select>\n";
    print "<br>&nbsp;<br>\n";
    print "              </td>";
    print "              <td>";
    print "                <select placeholder=\"\" class=\"lwampo_input_opt\" data-name=\"lwampo_opt\" />";
    print "                  <option value=0>".__("Följ",'lwattentionmedia')."</option>";
    print "                  <option value=1>".__("1",'lwattentionmedia')."</option>";
    print "                </select>\n";
    print "              </td>\n";
    print "              <td><input type=\"checkbox\" class=\"lwampo_input_hasno\" data-name=\"lwampo_opt_hasno\"></td>\n";
    print "              <td>";
    print "                <a class=\"lwampo_delete button\"><span class=\"dashicons dashicons-no\"></span></a>\n";
    print "              </td>\n";
    print "            </tr>\n";
    print "          </table>\n";
    // End Hidden DIV
    print "        </div>\n";
    // End Postbox DIV
    print "    </div>\n";
    // End Options group DIV
    print "  </div>\n";
    //----------------------------
    // Quantity product substitute
    //----------------------------
    // Options group DIV
    print "  <div class=\"options-group\">\n";
    print "    <input type=\"hidden\" id=\"_lwamps\" name=\"_lwamps\" value=\"".(!empty($custom_fields["_lwamps"][0])? $custom_fields["_lwamps"][0] : '')."\" />\n";
    // Postbox DIV
    print "    <div class=\"postbox lwamps_price_table_container\">\n";
    print "      <button type=\"button\" class=\"handlediv button-link\" aria-expanded=\"true\">\n";
    print "        <span class=\"screen-reader-text\">".__("Toggle panel", 'lwam') . "</span>\n";
    print "        <span class=\"toggle-indicator\" aria-hidden=\"true\"></span>\n";
    print "      </button>\n";
    print "      <h2><span>".__("Antalsberoende ersättningsprodukter",'lwattentionmedia')."</span></h2>\n";
    // Data table DIV
    print "      <div id=\"lwamps_substitute_data_table\">\n";
    // Inside DIV
    print "        <div class=\"inside lwamps_substitute_table_container\" data-role-key=\"all\">\n";
    print "          <table class=\"table lwamps_substitute_table\">\n";
    print "            <thead>\n";
    print "              <tr>\n";
    print "                <th class=\"lwamps_qty\">".__('Antal större än', 'lwattentionmedia')."</th>\n";
    print "                <th class=\"lwamps_prod\">".__('Ersätt med produkt', 'lwattentionmedia')."</th>\n";
    print "              </tr>\n";
    print "            </thead>\n";
    print "            <tbody></tbody>\n";
    print "          </table>\n";
    print "          <p><a class=\"button button-primary lwamps_add_substitute\">\n";
    print __('Add...', 'lwam');
    print "</a></p>\n";
    print "          <br>\n";
    // End inside DIV
    print "        </div>\n";
    // End data table DIV
    print "      </div>\n";
    // Hidden DIV
    print "      <div style=\"display: none;\">\n";
    print "        <table id=\"lwamps_template\" class=\"lwamps hidden\">\n";
    print "          <tr>\n";
    print "            <td><input placeholder=\"\" type=\"text\" class=\"lwamps_input_qty\" data-name=\"lwamps_qty\" /></td>\n";
    print "              <td>";
    print "                <select class=\"lwamps_input_prod\" data-name=\"lwamps_prod\">\n";
    //--------------------------------
    // Fill up option products options
    //--------------------------------
//    $default_lang = $sitepress->get_default_language();
    $args = array(
//      'lang' => $default_lang,
      'posts_per_page' => -1,
      'post_type' => 'product',
      'orderby' => 'title',
      'order' => 'ASC',
      'post_status' => 'publish',
    );
    $t = new WP_Query( $args );
    $options = "";
    while ($t->have_posts()) {
      $t->the_post();
      $sku = get_post_meta( $t->post->ID, '_sku', true );
      // Here we want to get all categories that the product belongs to and
      // if there is a category that is not addons, we show that
      $product = wc_get_product($t->post->ID);
      $catids = $product->get_category_ids();
      $catname = '';
      if (count($catids) == 1) {
        $catterm = get_term_by('id',$catids[0],'product_cat');
        $catname = $catterm->name;
      }
      if (count($catids) > 1) {
        for ($i = 0; $i < count($catids); $i++) {
          if ($catids[$i] != $category_id) {
            $catterm = get_term_by('id',$catids[0],'product_cat');
            $catname = $catterm->name;
          }
        }
      }
      $options .= "<option value=\"".$t->post->ID."\">".$catname." : ".$t->post->post_title."</option>\n";
      wp_reset_postdata();
    }
//    $q = "SELECT ";
//    $q .= "  ID ";
//    $q .= ",post_title ";
//    $q .= " FROM ";
//    $q .= $wpdb->prefix."posts ";
//    $q .= " INNER JOIN ";
//    $q .= $wpdb->prefix."icl_translations ";
//    $q .= " ON ";
//    $q .= $wpdb->prefix."posts.ID = ";
//    $q .= $wpdb->prefix."icl_translations.element_id ";
//    $q .= " AND ";
//    $q .= $wpdb->prefix."icl_translations.language_code = '".$sitepress->get_default_language()."'";
//    $q .= " WHERE ";
//    $q .= "  post_type='product' ";
//    $q .= " AND ID <> ".$post->ID;
//    $q .= " AND post_status = 'publish' ";
//    $q .= " ORDER BY post_title ";
//    $t = $wpdb->get_results($q, ARRAY_A);
//    $options = "";
//    foreach ($t as $r) {
//      $options .= "<option value=\"".$r["ID"]."\">".$r["post_title"]."</option>\n";
//    }
    print $options;
    print "                </select>\n";
    print "              </td>\n";
    print "            <td>";
    print "<a class=\"lwamps_delete button\"><span class=\"dashicons dashicons-no\"></span></a>\n";
    print "</td>\n";
    print "          </tr>\n";
    print "        </table>\n";
    // End hidden DIV
    print "      </div>\n";
    // End postbox DIV
    print "    </div>\n";
    // End options-group DIV
    print "  </div>\n";
    //-----------------------------
    // End of the whole tab content
    //-----------------------------
    print "</div>\n";
    //========================
    // Java scripts - Move out
    //========================
    print "<script>\n";
    //------------------------
    // Document ready stuffers
    //------------------------
    print "jQuery(document).ready(function() {\n";
    //----------------
    // Add a new price
    //----------------
    print "  jQuery('.lwamfq_add_price').on('click', function() {\n";
    print "    var tableContainer = jQuery(this).closest('.lwamfq_price_table_container');\n";
    print "    jQuery('#lwamfq_template').find('tr').clone().appendTo(tableContainer.find('.lwamfq_price_table tbody'));\n";
    print "  });\n";
    //-------------
    // Delete price
    //-------------
    print "  jQuery('#lwamfq_price_data_table').on('click', '.lwamfq_delete', function() {\n";
    print "    jQuery(this).closest('tr').remove();\n";
    print "    savePriceToJSON();\n";
    print "  });\n";
    //----------------
    // Change handlers
    //----------------
    print "  jQuery('#lwamfq_price_data_table').on('change', 'input[data-name=\"lwamfq_qty\"]', function() {\n";
    print "    var newVal = jQuery(this).val();\n";
    print "    if (newVal == '' || isNaN(newVal) || parseInt(newVal) <= 0) {\n";
    print "      newVal = 1;\n";
    print "    }\n";
    print "    jQuery(this).val(parseInt(newVal));\n";
    print "    savePriceToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwamfq_price_data_table').on('change', 'input[data-name=\"lwamfq_desc\"]', function() {\n";
    print "    savePriceToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwamfq_price_data_table').on('change', 'input[data-name=\"lwamfq_price\"]', function() {\n";
    print "    var newVal = jQuery(this).val();\n";
    print "    if (newVal == '' || isNaN(newVal) || parseFloat(newVal) <= 0) {\n";
    print "      newVal = 0;\n";
    print "    }\n";
    print "    jQuery(this).val(parseFloat(newVal));\n";
    print "    savePriceToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwamfq_price_data_table').on('change', 'input[data-name=\"lwamfq_default\"]', function() {\n";
    print "    savePriceToJSON();\n";
    print "  });\n";
    //-------------------------
    // Generate the Price DATAs
    //-------------------------
    print "  var savePriceToJSON = function() {\n";
    print "    var jsonvar = [];\n"; // An array
    print "    jQuery('.lwamfq_price_table').find('tr').each(function() {\n";
    print "      var row = {};\n";
    print "      jQuery(this).find('input:text,input[type=checkbox],select').each(function() {\n";
    print "        var key = jQuery(this).attr('data-name');\n";
    print "        if (key == 'lwamfq_default') {\n";
    print "          var value = '0';\n";
    print "          if (jQuery(this).is(':checked')) {\n";
    print "            value = '1';\n";
    print "          }\n";
    print "        } else {\n";
    print "          var value = jQuery(this).val();\n";
    print "        }\n";
    print "        if (value != '') {\n";
    print "          row[key] = value;\n";
    print "        }\n";
    print "      });\n";
    print "      if ((row['lwamfq_desc'] != null) && (row['lwamfq_qty'] != null) && (row['lwamfq_price'] != null)) {\n";
    print "        jsonvar.push(row);\n";
    print "      }\n";
    print "    });\n";
    //---------------------------------
    // Sort the price array by quantity
    //---------------------------------
    print "    jsonvar.sort(function(a,b) {return a.lwamfq_qty - b.lwamfq_qty;} );\n";
    print "    jsonstring = JSON.stringify(jsonvar);\n";
    print "    jQuery('#_lwamfq').val(jsonstring);\n";
    print "  };\n";
    //-----------------------------
    // Display price values from DB
    //-----------------------------
    print "  if (jQuery(\"#_lwamfq\").length > 0) {\n";
    print "    var table = jQuery('.lwamfq_price_table tbody');\n";
    print "    var data = jQuery(\"#_lwamfq\").val();\n";
    print "    if (data != '') {\n";
    print "      data = JSON.parse(data);\n";
    print "      jQuery.each(data, function (index, value) {\n";
    print "        var row = jQuery('#lwamfq_template').find('tr').clone();\n";
    print "        row.find('input[data-name=\"lwamfq_desc\"]').val(value['lwamfq_desc']);\n";
    print "        row.find('input[data-name=\"lwamfq_qty\"]').val(value['lwamfq_qty']);\n";
    print "        row.find('input[data-name=\"lwamfq_price\"]').val(value['lwamfq_price']);\n";
    print "        checked = value['lwamfq_default'];\n";
    print "        if (checked == 1) {\n";
    print "          row.find('input[data-name=\"lwamfq_default\"]').prop(\"checked\", true );\n";
    print "        }\n";
    print "        row.appendTo(table);\n";
    print "      });\n";
    print "    }\n";
    print "  }\n";
    //-------------------------
    // Add a new option product
    //-------------------------
    print "  jQuery('.lwampo_add_product').on('click', function() {\n";
    print "    var tableContainer = jQuery(this).closest('.lwampo_options_table_container');\n";
    print "    jQuery('#lwampo_template').find('tr').clone().appendTo(tableContainer.find('.lwampo_options_table tbody'));\n";
    print "  });\n";
    //----------------------
    // Delete option product
    //----------------------
    print "  jQuery('.lwampo_options_table').on('click', '.lwampo_delete', function() {\n";
    print "    jQuery(this).closest('tr').remove();\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    //----------------
    // Change handlers
    //----------------
    print "  jQuery('#lwampo_options_data_table').on('change', 'input[data-name=\"lwampo_seq\"]', function() {\n";
    print "    var newVal = jQuery(this).val();\n";
    print "    if (newVal == '' || isNaN(newVal) || parseInt(newVal) <= 0) {\n";
    print "      newVal = 1;\n";
    print "    }\n";
    print "    jQuery(this).val(parseInt(newVal));\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwampo_options_data_table').on('change', 'input[data-name=\"lwampo_caption\"]', function() {\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    //print "  jQuery('#lwampo_options_data_table').on('change', 'select[data-name=\"lwampo_prod\"]', function() {\n";
    //print "    saveOptionsToJSON();\n";
    //print "  });\n";
    print "  jQuery('#lwampo_options_data_table').on('dblclick', 'select[data-name=\"lwampo_prod\"]', function() {\n";
    print "    thisvalue = jQuery(this).val();\n";
    print "    thistext = jQuery(this).find(\"option:selected\").text();\n";
    //print "      thistext = jQuery(this).text();\n";
    //print "alert(thisvalue+':'+thistext);\n";
    //print "    saveOptionsToJSON();\n";
    print "    jQuery(this).parent().parent().find('select[data-name=\"lwampo_selprod\"]').append(jQuery('<option>',{\n";
    print "      value: thisvalue,\n";
    print "      text: thistext,\n";
    print "    }));\n";
    print "    jQuery('option:selected',this).remove();\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwampo_options_data_table').on('dblclick', 'select[data-name=\"lwampo_selprod\"]',function() {\n";
    print "    thisvalue = jQuery(this).val();\n";
    print "    thistext = jQuery(this).find(\"option:selected\").text();\n";
    print "    jQuery(this).parent().parent().find('select[data-name=\"lwampo_prod\"]').append(jQuery('<option>',{\n";
    print "      value: thisvalue,\n";
    print "      text: thistext,\n";
    print "    }));\n";
    print "    jQuery('option:selected',this).remove();\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwampo_options_data_table').on('change', 'select[data-name=\"lwampo_opt\"]', function() {\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwampo_options_data_table').on('click', 'input[data-name=\"lwampo_opt_hasno\"]', function() {\n";
    print "    saveOptionsToJSON();\n";
    print "  });\n";
    //----------------------------------
    // Generate the Option Product DATAs
    //----------------------------------
    print "  var saveOptionsToJSON = function() {\n";
    print "    var jsonvar = [];\n"; // An array
    print "    jQuery('.lwampo_options_table').find('tr').each(function() {\n";
    // We must select all in the selected dropdown, otherwise no save lah
    print "      jQuery(this).find('select.lwampo_input_selprod option').prop('selected',true);\n";
    print "      var row = {};\n";
    print "      jQuery(this).find('input:text,input:checkbox,select').each(function() {\n";
    print "        var key = jQuery(this).attr('data-name');\n";
    print "        var value = jQuery(this).val();\n";
    print "        if (key == 'lwampo_opt_hasno') {\n";
    print "          if (jQuery(this).is(':checked')) {\n";
    print "            value = 1;\n";
    print "          } else {\n";
    print "            value = 0;\n";
    print "          }\n";
    print "        }\n";
    print "        if (value != '') {\n";
    print "          savekey = key;\n";
    print "          if (savekey != 'lwampo_prod') {\n";
    print "            if (savekey == 'lwampo_selprod') {\n";
    print "              savekey = 'lwampo_prod';\n";
    print "            }\n";
    print "            row[savekey] = value;\n";
    print "          }\n";
    print "        }\n";
    print "      });\n";
    print "      if ((row['lwampo_seq'] != null) && (row['lwampo_prod'] != null) && (row['lwampo_opt'] != null)) {\n";
    print "        jsonvar.push(row);\n";
    print "      }\n";
    print "    });\n";
    //-----------------------------------
    // Sort the product array by sequence
    //-----------------------------------
//print "console.log(jsonvar);\n";
    print "    jsonvar.sort(function(a,b) {return a.lwampo_seq - b.lwampo_seq;} );\n";
//print "console.log(jsonvar);\n";

    print "    jsonstring = JSON.stringify(jsonvar);\n";
    print "    jQuery('#_lwampo').val(jsonstring);\n";
    print "  };\n";
    //--------------------------------------
    // Display option product values from DB
    //--------------------------------------
    print "  if (jQuery(\"#_lwampo\").length > 0) {\n";
    print "    var table = jQuery('.lwampo_options_table tbody');\n";
    print "    var data = jQuery(\"#_lwampo\").val();\n";
    print "    if (data != '') {\n";
    print "      data = JSON.parse(data);\n";
    print "      jQuery.each(data, function (index, value) {\n";
    print "        var row = jQuery('#lwampo_template').find('tr').clone();\n";
    print "        row.find('input[data-name=\"lwampo_seq\"]').val(value['lwampo_seq']);\n";
    print "        row.find('input[data-name=\"lwampo_caption\"]').val(value['lwampo_caption']);\n";
    // OK, lwampo_prod is now an array, we need to loop it in some elegant way.... ah but no need lah. 
    // The clever jQuery takes the array happily :)
    // HERE we want to do something different: we want to move to the 'Selected' list if it's in lwampo_prod
    print "        prodselect = row.find('select.lwampo_input_prod');\n";
    print "        for (var i=0;i<value['lwampo_prod'].length;i++) {\n";
    print "           var selval = value['lwampo_prod'][i];\n";
    print "           var selopt = prodselect.find('option[value='+selval+']');\n";
    print "           var seltxt = selopt.text();\n";
    print "           row.find('select.lwampo_input_selprod').append(jQuery('<option>',{\n";
    print "             value: selval,\n";
    print "             text: seltxt,\n";
    print "           }));\n";
    print "        }\n";
    print "        row.find('select[data-name=\"lwampo_selprod\"]').val(value['lwampo_prod']);\n";
    print "        row.find('select[data-name=\"lwampo_opt\"]').val(value['lwampo_opt']);\n";
    //-------------------------------------------
    // New option if the options has a "no" value
    //-------------------------------------------
    print "        if (value.hasOwnProperty('lwampo_opt_hasno')) {\n";
    print "          if (value['lwampo_opt_hasno'] == 1) {\n";
    print "            row.find('input[data-name=\"lwampo_opt_hasno\"]').attr('checked',true);\n";
    print "          } else {\n";
    print "            row.find('input[data-name=\"lwampo_opt_hasno\"]').attr('checked',false);\n";
    print "          }\n";
    print "        } else {\n";
    print "          row.find('input[data-name=\"lwampo_opt_hasno\"]').attr('checked',false);\n";
    print "        }\n";
    print "        row.appendTo(table);\n";
    print "      });\n";
    print "    }\n";
    print "  }\n";
    //-----------------------------
    // Add a new substitute product
    //-----------------------------
    print "  jQuery('.lwamps_add_substitute').on('click', function() {\n";
    print "    var tableContainer = jQuery(this).closest('.lwamps_substitute_table_container');\n";
    print "    jQuery('#lwamps_template').find('tr').clone().appendTo(tableContainer.find('.lwamps_substitute_table tbody'));\n";
    print "  });\n";
    //-------------
    // Delete substitute
    //-------------
    print "  jQuery('#lwamps_substitute_data_table').on('click', '.lwamps_delete', function() {\n";
    print "    jQuery(this).closest('tr').remove();\n";
    print "    saveSubstituteToJSON();\n";
    print "  });\n";
    //----------------
    // Change handlers
    //----------------
    print "  jQuery('#lwamps_substitute_data_table').on('change', 'input[data-name=\"lwamps_qty\"]', function() {\n";
    print "    var newVal = jQuery(this).val();\n";
    print "    if (newVal == '' || isNaN(newVal) || parseInt(newVal) <= 0) {\n";
    print "      newVal = 1;\n";
    print "    }\n";
    print "    jQuery(this).val(parseInt(newVal));\n";
    print "    saveSubstituteToJSON();\n";
    print "  });\n";
    print "  jQuery('#lwamps_substitute_data_table').on('change', 'select[data-name=\"lwamps_prod\"]', function() {\n";
    print "    saveSubstituteToJSON();\n";
    print "  });\n";
    //-------------------------
    // Generate the Substitute DATAs
    //-------------------------
    print "  var saveSubstituteToJSON = function() {\n";
    print "    var jsonvar = [];\n"; // An array
    print "    jQuery('.lwamps_substitute_table').find('tr').each(function() {\n";
    print "      var row = {};\n";
    print "      jQuery(this).find('input:text,select').each(function() {\n";
    print "        var key = jQuery(this).attr('data-name');\n";
    print "        var value = jQuery(this).val();\n";
    print "        if (value != '') {\n";
    print "          row[key] = value;\n";
    print "        }\n";
    print "      });\n";
    print "      if ((row['lwamps_qty'] != null) && (row['lwamps_prod'] != null)) {\n";
    print "        jsonvar.push(row);\n";
    print "      }\n";
    print "    });\n";
    //---------------------------------
    // Sort the substitute array by quantity
    //---------------------------------
    print "    jsonvar.sort(function(a,b) {return a.lwamps_qty - b.lwamps_qty;} );\n";
    print "    jsonstring = JSON.stringify(jsonvar);\n";
    print "    jQuery('#_lwamps').val(jsonstring);\n";
    print "  };\n";
    //----------------------------------
    // Display substitute values from DB
    //----------------------------------
    print "  if (jQuery(\"#_lwamps\").length > 0) {\n";
    print "    var table = jQuery('.lwamps_substitute_table tbody');\n";
    print "    var data = jQuery(\"#_lwamps\").val();\n";
    print "    if (data != '') {\n";
    print "      data = JSON.parse(data);\n";
    print "      jQuery.each(data, function (index, value) {\n";
    print "        var row = jQuery('#lwamps_template').find('tr').clone();\n";
    print "        row.find('input[data-name=\"lwamps_qty\"]').val(value['lwamps_qty']);\n";
    print "        row.find('select[data-name=\"lwamps_prod\"]').val(value['lwamps_prod']);\n";
    print "        row.appendTo(table);\n";
    print "      });\n";
    print "    }\n";
    print "  }\n";
    print "});\n";
    print "</script>\n";
  }
  //--------------------------------
  // Save the fields for the setting
  //--------------------------------
  add_action( 'woocommerce_process_product_meta', 'lwam_woocommerce_process_product_meta_fields_save' );
  function lwam_woocommerce_process_product_meta_fields_save( $post_id ){
    if (!empty($_POST['_lwamfq']) && $_POST['_lwamfq'] != "[]") {
      update_post_meta($post_id, '_lwamfq', htmlentities($_POST['_lwamfq']));
    } else {
      delete_post_meta($post_id, '_lwamfq');
    }
    if (!empty($_POST['_lwampo']) && $_POST['_lwampo'] != "[]") {
      update_post_meta($post_id, '_lwampo', htmlentities($_POST['_lwampo']));
    } else {
      delete_post_meta($post_id, '_lwampo');
    }
    if (!empty($_POST['_lwamps']) && $_POST['_lwamps'] != "[]") {
      update_post_meta($post_id, '_lwamps', htmlentities($_POST['_lwamps']));
    } else {
      delete_post_meta($post_id, '_lwamps');
    }
    if (!empty($_POST['_lwam_front_image_url']) && $_POST['_lwam_front_image_url'] != "") {
      update_post_meta($post_id, '_lwam_front_image_url', $_POST['_lwam_front_image_url']);
    } else {
      delete_post_meta($post_id, '_lwam_front_image_url');
    }
    if (!empty($_POST['_lwam_template_url']) && $_POST['_lwam_template_url'] != "") {
      update_post_meta($post_id, '_lwam_template_url', $_POST['_lwam_template_url']);
    } else {
      delete_post_meta($post_id, '_lwam_template_url');
    }
  }
?>
