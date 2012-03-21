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

  function getFilelink() {
    unset($this->html);
    $this->file = file_get_contents($this->languages[$this->language]['site']);
    $this->debug('Getting html page from ' . $this->languages[$this->language]['site']);
    $this->html = new simple_html_dom();
    $this->html->load($this->file);
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
    $this->filelink = false;
    $this->debug('Language setted to ' . $this->language);
  }

  function download($url = false) {
    if (!$url) {$url = $this->getFilelink();}
    $this->debug('Downloading latest ' . $this->language . ' file: ' . $url);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    file_put_contents($this->getLocalpath(), $data);
    return $this->save('latest_downloaded', $this->getNewestVersion());
  }

  function extract() {
    $za = new ZipArchive();
    $zipfile = 'temp/wordpress-' . $this->getNewestVersion() . '-' . $this->language . '.zip';
    $za->open($zipfile);
    $this->debug('Extracting from latest ' . $this->language . ' file: ' . $zipfile);
    $files = array(
      'languagefiles/' . $this->getNewestVersion() . '/' . $this->language . '.mo' => 'wordpress/wp-content/themes/twentyeleven/languages/' . $this->language . '.mo',
      'languagefiles/' . $this->getNewestVersion() . '/' . $this->language . '.po' => 'wordpress/wp-content/themes/twentyeleven/languages/' . $this->language . '.po',
    );
    foreach ($files as $localpath => $zippath) {
      if (!is_dir('languagefiles/' . $this->getNewestVersion())) {mkdir('languagefiles/' . $this->getNewestVersion());}
      if (!$data = $za->getFromName($zippath)) {die('Languagefile ' . $zippath . ' not found in archive: ' . $zipfile);}

      file_put_contents($localpath, $data);
    }
    return !empty($data) ? $this->save('latest_extracted', $this->getNewestVersion()) : false;
  }

  function process() {
    if ($this->load('latest_downloaded')!=$this->getNewestVersion()) {
      $this->download();
    }
    if ($this->load('latest_extracted')!=$this->getNewestVersion()) {
      $this->extract();
    }
    return $this->save('latest_processed', $this->getNewestVersion());
  }

  function debug($text) {
    echo $text . "\n";
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

  function db_dump() {
    return file_put_contents($this->dbfile, '');
  }

  function db_export() {
    if (!$this->database) {$this->loaddb();}
    var_dump($this->database);
  }

}
