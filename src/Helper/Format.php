<?php
namespace Piler\Helper;

class Format {
  static function array_flatten(array $array) {
    $result = array();
    array_walk_recursive($array, function($v) use(&$result) { $result[] = $v; });
    return $result;
  }

  static function array_merge_at_index(array $array, array|string $subject, int $index, bool $replace = false) {
    return array_merge(array_slice($array, 0, $index), (array) $subject, array_slice($array, ($index + ($replace ? 1 : 0))));
  }

  static function build_path(...$path_parts) {
    $parts = array();

    array_walk_recursive($path_parts, function($part) use(&$parts) {
      $parts[] = trim($part, DIRECTORY_SEPARATOR);
    });

    return implode(DIRECTORY_SEPARATOR, $parts);
  }

  static function arr_values(array $arr, array $keys) {
    $new_arr = array();

    foreach($arr as $key => $value) {
      if (in_array($key, $keys)) {
        $new_arr[$key] = $value;
      }
    }

    return $new_arr;
  }

  static function is_glob(string $str) {
    return preg_match('/[\*\?\[\]]/', $str) !== 0;
  }

  static function write_action(\Ahc\Cli\Output\Writer $writer, string $action, string $subject, bool|null $dry_run = false) {
    $action_slug = strtolower($action);
    $action = ucfirst($action_slug);
    $label = "<action_label_{$action_slug}>{$action}</end>";
    $subject = "<action_subject_{$action_slug}>{$subject}</end>";
    $prefix = "<action_prefix_{$action_slug}>•</end>";
    $suffix = '<action_suffix>...</end>';

    if ($action_slug === 'saving' && $dry_run) {
      $label = '*' . $label;
    }

    $writer->colors("{$prefix} {$label} {$subject}{$suffix}", true);
  }
}
?>