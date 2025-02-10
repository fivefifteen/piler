<?php
namespace Piler\Handler;

use \Piler\Handler\CssHandler;
use \Piler\Handler\JsHandler;
use \Piler\Handler\ScssHandler;

abstract class Handler {
  public $imported_files = array();

  abstract protected function process_input(string $input, string $input_file);
  abstract protected function process_output(string $output, string $output_file);

  static function get_handler_by_ext($ext, $input_paths = array(), $args = array()) {
    switch(strtolower($ext)) {
      case 'css':
        return new CssHandler($input_paths, $args);
      case 'js':
        return new JsHandler($input_paths, $args);
      case 'scss':
        return new ScssHandler($input_paths, $args);
      default:
        throw new \Error("$ext is not a valid handler");
    }
  }
}
?>