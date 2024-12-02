<?php
namespace Piler\Helper;

class File {
  static function create_directory($dir) {
    if (!is_dir($dir)) {
      return mkdir($dir, 0777, true);
    }
  }

  static function delete_directory($dir) {
    if (!is_dir($dir)) return false;

    $cwd = getcwd();
    $realpath = realpath($dir);
    if (!$dir || $realpath === $cwd || !str_starts_with($realpath, $cwd)) {
      throw new \Error("Safety net triggered while trying to delete {$dir}");
      exit(1);
    }

    $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $dir) . '/{,.}*', GLOB_BRACE);

    foreach ($files as $file) {
      if ($file == $dir . '/.' || $file == $dir . '/..') continue;
      is_dir($file) ? self::delete_directory($file) : self::delete_file($file);
    }

    return @rmdir($dir);
  }

  static function delete_file($file) {
    if (is_file($file)) {
      return @unlink($file);
    }
  }

  static function get_json($path) {
    $contents = @file_get_contents($path);

    if ($contents === false) {
      throw new \Error("Could not load $path");
    }

    $json = @json_decode($contents, true);

    if ($json === null) {
      throw new \Error("$path did not return valid JSON");
    }

    return $json;
  }

  function get_relative_path(string $from, string $to) {
    $separator = DIRECTORY_SEPARATOR;
    $from = str_replace(array('/', '\\'), $separator, $from);
    $to = str_replace(array('/', '\\'), $separator, $to);
    $from_parts = explode($separator, rtrim($from, $separator));
    $to_parts = explode($separator, rtrim($to, $separator));

    while(count($from_parts) && count($to_parts) && ($from_parts[0] == $to_parts[0])) {
      array_shift($from_parts);
      array_shift($to_parts);
    }

    return str_pad('', count($from_parts) * 3, '..' . $separator).implode($separator, $to_parts);
  }
}