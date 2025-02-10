<?php
namespace Piler;

use \Piler\Helper\File;
use \Piler\Helper\Format;

class Reloader {
  public $timeout = 500;

  function __construct(string|array $config = 'pile.json', int $timeout = 500) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');

    $config_json = '';
    $path_prefix = '';

    if (is_string($config)) {
      $config_json = File::get_json($config);
      $path_prefix = dirname($config);
    }

    if ($timeout) {
      $this->timeout = $timeout;
    }

    if ($config_json) {
      if (isset($config_json['piler'])) {
        $config = $config_json['piler'];
      } elseif (isset($config_json['extra']) && isset($config_json['extra']['piler'])) {
        $config = $config_json['extra']['piler'];
      }
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
      if ($path_prefix) {
        $output_file = $path_prefix . DIRECTORY_SEPARATOR . $output_file;
      }

      if (file_exists($output_file)) {
        $last_modified = filemtime($output_file);

        if ($last_modified >= time()) {
          $this->send_message(array(
            'action'  => 'reload',
            'output'  => $output_file,
          ));
        }
      } else {
        $this->send_message(array(
          'error' => "Could not find {$output_file}"
        ));
      }
    }
  }

  function send_message(array $data) {
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