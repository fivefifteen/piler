<?php
namespace Piler;

use \Piler\Helper\File;
use \Piler\Helper\Format;

class Reloader {
  static $timeout = 500;

  function __construct(string|array $config = 'pile.json', int $timout = 500) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');

    if (is_string($config)) {
      $config = File::get_json($config_path);
    }

    if ($timeout) {
      $this->timeout = $timeout;
    }

    if (!$config || !isset($config['compile'])) {
      $this->send_message(array(
        'error' => 'Invalid config'
      ));

      return;
    }

    $file_groups = $config['compile'];

    $this->send_message(array(
      'message' => 'Checking for file changes...'
    ));

    foreach($file_groups as $output_file => $input_files) {
      foreach($input_files as $input_file) {
        if (file_exists($input_file)) {
          $last_modified = filemtime($input_file);

          if ($last_modified >= time()) {
            $this->send_message(array(
              'action'  => 'reload',
              'input'   => $input_file,
              'output'  => $output_file,
            ));
          }
        } else {
          $this->send_message(array(
            'error' => "Could not find {$input_file}"
          ));
        }
      }
    }
  }

  static function send_message(array $data) {
    ob_start();
    echo 'id: ' . time() . PHP_EOL;
    echo 'data: ' . json_encode($data) . PHP_EOL;
    echo 'retry: ' . $this->timeout . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
  }
}
?>