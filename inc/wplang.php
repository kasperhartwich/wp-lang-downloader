<?php
class WPlang {
  public $file;
  public $html;
  public $filelink = false;
  public $language = false;
  public $dbfile = 'inc/database';
  private $database = false;
  public $languages = array(
    'es_ES' => array(
      'site' => 'http://es.wordpress.org',
    ),
    'de_DE' => array(
      'site' => 'http://de.wordpress.org',
    ),
    'nl_NL' => array(
      'site' => 'http://nl.wordpress.org',
    ),
    'da_DK' => array(
      'site' => 'http://da.wordpress.org',
    ),
  );

  function __construct() {
  }

  function getData() {
    $this->file = file_get_contents($this->languages[$this->language]['site']);
    $this->html = new simple_html_dom();
    $this->html->load($this->file);
  }

  function getFilelink() {
    if (!$this->filelink) {
      foreach ($this->html->find("a[class=download-button]") as $a) {
        $this->filelink = $a->href;
      }
    }
    return $this->filelink;
  }

  function getNewestVersion() {
    $temp = explode('-', $this->getFilelink());
    return $temp[1];
  }

  function getLanguages() {
    return $this->languages;
  }

  function setLanguage($language) {
    $this->language = $language;
    $this->getData();
  }

  function getLocalpath() {
    return 'temp/wordpress-' . $this->getNewestVersion() . '-' . $this->language . '.zip';
  }

  function download($url = false) {
    if (!$url) {$url = $this->getFilelink();}
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    file_put_contents($this->getLocalpath(), $data);
    return $this->save('latest_downloaded', $this->getNewestVersion());
  }

  function extract() {
    $za = new ZipArchive();

    $za->open($this->getLocalpath());
    $files = array(
      'languagefiles/' . $this->getNewestVersion() . '/' . $this->language . '.mo' => 'wordpress/wp-content/themes/twentyeleven/languages/' . $this->language . '.mo',
      'languagefiles/' . $this->getNewestVersion() . '/' . $this->language . '.po' => 'wordpress/wp-content/themes/twentyeleven/languages/' . $this->language . '.po',
    );
    foreach ($files as $localpath => $zippath) {
      if (!is_dir('languagefiles/' . $this->getNewestVersion())) {mkdir('languagefiles/' . $this->getNewestVersion());}
      $data = $za->getFromName($zippath);
      file_put_contents($localpath, $data);
    }
    return $this->save('latest_extracted', $this->getNewestVersion());
  }

  function process() {
    if ($this->load('latest_processed')!=$this->getNewestVersion()) {
      $this->download();
      $this->extract();
      return $this->save('latest_processed', $this->getNewestVersion());
    } else {
      return false;
    }
  }


  /*
   * Database
   */
  function loaddb() {
    $this->database = json_decode(file_get_contents($this->dbfile), true);
  }

  function load($key, $language = false) {
    if (!$language) {$language = $this->language;}
    if (!$this->database) {$this->loaddb();}
    return @$this->database[$language][$key];
  }

  function save($key, $value, $language = false) {
    if (!$language) {$language = $this->language;}
    if (!$this->database) {$this->loaddb();}

    $this->database[$language][$key] = $value;
    return file_put_contents($this->dbfile, json_encode($this->database));
  }
  function dump() {
    if (!$this->database) {$this->loaddb();}
    var_dump($this->database);
  }

}
