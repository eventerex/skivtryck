<?php
  //-----------------------------------------------
  // lwam_frontpage.php - Attentionmedia front page
  // Copyright(c) Lightweb AB
  // Author: Bjorn Pehrson
  //-----------------------------------------------
  //---------------------------
  // Script for swapping images
  //---------------------------
  function lwam_front_js() {
    $lwam_front_get_image = plugins_url('lwam_front_getimage.php',__FILE__);
    print "<script type=\"text/javascript\">\n";
    print "var lwam_front_get_image = '".$lwam_front_get_image."';\n";
    print "</script>\n";
  }
  // Add hook for front-end <head></head>
  add_action('wp_head', 'lwam_front_js');
?>
