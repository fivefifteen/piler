<?php
namespace Piler\Handler;

use \MatthiasMullie\Minify\CSS As Minifier;

class CssHandler extends Handler {
  public $minify = true;

  public function __construct($import_paths = array(), $args = array()) {
    if (isset($args['minify'])) $this->minify = $args['minify'];
  }

  public function process_input($input, $input_file) {
    return $input;
  }

  public function process_output($output, $output_file) {
    if ($this->minify) {
      $minifier = new Minifier($output);
      return $minifier->minify();
    }

    return $output;
  }
}
?>