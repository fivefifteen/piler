<?php
namespace Piler\Handler;

use \Ahc\Cli\Output\Writer;
use \Piler\Helper\Format;
use \ScssPhp\ScssPhp\Compiler;

class ScssHandler extends Handler {
  public $ignore_errors = false;
  public $import_paths = array();
  public $quiet = false;

  public function __construct($import_paths = array(), $args = array()) {
    $this->import_paths = $import_paths;

    if (isset($args['ignore_errors'])) $this->ignore_errors = $args['ignore_errors'];
    if (isset($args['quiet'])) $this->quiet = $args['quiet'];
  }

  public function process_input($input, $input_file) {
    $compiler = new Compiler();
    $writer = new Writer();

    $ignore_errors = $this->ignore_errors;
    $import_paths = $this->import_paths;
    $quiet = $this->quiet;
    $input_files = array();

    $compiler->addImportPath(function($path) use($ignore_errors, $import_paths, &$input_files, $quiet, $writer) {
      $new_path = null;

      if (Compiler::isCssImport($path)) {
        return null;
      }

      foreach($import_paths as $import_path) {
        $check_path = Format::build_path($import_path, $path);
        $import_dir_name = dirname($check_path);
        $import_file_name = basename($check_path);
        $check_files = array();

        foreach(array('', '.scss', '.css') as $ext) {
          $check_files = array_merge($check_files, array(
            $import_file_name . $ext,
            '_' . $import_file_name . $ext,
            $import_file_name . '/' . $import_file_name . $ext,
            $import_file_name . '/_' . $import_file_name . $ext,
            $import_file_name . '/index' . $ext,
            $import_file_name . '/_index' . $ext
          ));
        }

        foreach($check_files as $file) {
          $file = $import_dir_name . '/' . $file;

          if (file_exists($file) && !is_dir($file)) {
            $new_path = $file;
            break;
          }
        }
      }

      if ($new_path) {
        if (!$quiet) {
          Format::write_action($writer, 'importing', $new_path);
        }

        if (!in_array($new_path, $input_files)) {
          $input_files[] = $new_path;
        }

        return $new_path;
      } else {
        if (!$quiet) {
          $writer->colors("<error>Error</end>: <message>Could not find any CSS files matching</end> <subject>{$path}</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    });

    return $compiler->compileString($input)->getCss();
  }

  public function process_output($output, $output_file) {
    throw new \Error('You should not be outputting SCSS');
  }
}
?>