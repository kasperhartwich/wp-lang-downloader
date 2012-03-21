<?php
set_time_limit(0);
require('inc/simple_html_dom.php');
require('inc/wplang.php');



$wpdl = new WPlang();
$wpdl->db_dump();

foreach ($wpdl->getLanguages() as $languagecode => $language) {
  $wpdl->setLanguage($languagecode);
  $wpdl->process();
}










$wpdl->db_export();
