<?php
set_time_limit(0);
require('inc/simple_html_dom.php');
require('inc/wplang.php');



$wpdl = new WPlang();

foreach ($wpdl->getLanguages() as $languagecode => $language) {
  $wpdl->setLanguage($languagecode);
  $wpdl->process();
}



//Show database / status
$wpdl->db_export();
