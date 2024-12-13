<?php
namespace Piler;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Output\Color;
use \Ahc\Cli\Output\Writer;
use \Piler\Handler\Handler;
use \Piler\Helper\File;
use \Piler\Helper\Format;

class Compile extends \Ahc\Cli\Input\Command {
  static $supported_extensions = array('js', 'css', 'scss');
  static $preprocess_extensions = array('scss');
  static $defaults = array(
    'bail_on_overwrite' => false,
    'config_path'       => 'pile.json',
    'debug'             => false,
    'dry_run'           => false,
    'event_source'      => null,
    'input'             => null,
    'ignore_errors'     => false,
    'import_paths'      => array(),
    'minify'            => true,
    'output'            => null,
    'quiet'             => false,
    'save_changes'      => false,
    'verbosity'         => 0,
    'watch'             => false,
    'working_directory' => '',
    'yes_to_overwrite'  => false
  );

  public $debug = false;
  public $errors = false;
  public $file_groups = array();
  public $watching = false;

  public function __construct() {
    parent::__construct('piler', 'Compiles and minifies JavaScript and CSS/SCSS files');

    $this
      ->argument('[input...]', 'Files or directories to process')
      ->option('-n --bail-on-overwrite', 'Skips the confirmation prompt and bails if a file already exists in the output directory')
      ->option('-c --config [path]', 'Path to a config JSON file', 'strval', self::$defaults['config_path'])
      ->option('-d --dry-run', 'Don\'t actually write anything so that you can test the command')
      ->option('-e --event-source', 'Server accessible path to point EventSource to for hot-reloading')
      ->option('-i --ignore-errors', 'Ignore any errors that may occur and continue processing as much as possible')
      ->option('-p --import-path [paths...]', 'Additional directory path(s) to import from')
      ->option('-m --no-minify', 'Minfy files')
      ->option('-o --output [paths...]', 'The directory path(s) or filename(s) to write to')
      ->option('-q --quiet', 'Run but don\'t output anything in the terminal')
      ->option('-s --save', 'Save list of compiled files to pile.json to easily run again later')
      ->option('-W --watch', 'Monitor defined files for changes and re-process when they are changed')
      ->option('-w --working-directory', 'Sets the working directory that all paths will be relative to', 'strval', self::$defaults['working_directory'])
      ->option('-y --yes-to-overwrite', 'Skips the confirmation prompt and overwrites files if they already exist in the output directory');
  }

  public function execute() {
    $args = array(
      'bail_on_overwrite' => $this->bailOnOverwrite,
      'config_path'       => $this->config,
      'debug'             => $this->verbosity >= 3,
      'dry_run'           => $this->dryRun,
      'event_source'      => $this->eventSource,
      'input'             => $this->input,
      'ignore_errors'     => $this->ignoreErrors,
      'import_paths'      => $this->importPath,
      'minify'            => $this->minify,
      'output'            => $this->output,
      'quiet'             => $this->quiet,
      'save_changes'      => $this->save,
      'verbosity'         => $this->verbosity,
      'watch'             => $this->watch,
      'working_directory' => $this->workingDirectory,
      'yes_to_overwrite'  => $this->yesToOverwrite
    );

    if ($args['watch']) {
      $this->watch($args);
    } else {
      $this->run($args);
    }
  }

  public function run($args) {
    extract(array_merge(self::$defaults, $args));

    if ($quiet) $verbosity = 0;

    $config = array();
    $debug = $verbosity >= 3;
    $errors = false;
    $file_groups = array();
    $watching = $this->watching;
    $writer = new Writer();

    $import_paths = array_filter(array_unique(array_merge(
      $import_paths,
      array($working_directory, Format::build_path($working_directory, 'fetched'))
    )));

    $this->debug = $debug;

    $this->log('args', $args);

    if ($input) {
      $confirmed_files = array();

      foreach($input as $input_idx => $input_entry) {
        if ($working_directory && !str_starts_with($input_entry, $working_directory)) {
          $input_entry = Format::build_path($working_directory, $input_entry);
        }

        $potential_files = self::validate_input_entry($input_entry, compact('quiet', 'ignore_errors', 'import_paths', 'verbosity', 'debug'));

        if (!$potential_files) {
          continue;
        }

        $confirmed_files = array_merge($confirmed_files, $potential_files);
      }

      if ($confirmed_files) {
        $file_groups = self::assign_inputs_to_outputs($confirmed_files, $output, compact('quiet', 'ignore_errors', 'import_paths', 'minify', 'verbosity', 'debug'));
      }
    } else {
      if ($working_directory && !str_starts_with($config_path, $working_directory)) {
        $config_path = Format::build_path($working_directory, $config_path);
      }

      $config_json = false;
      $is_default_check = basename($config_path) === 'pile.json';
      $alt_config_path = Format::build_path(dirname($config_path), 'composer.json');

      $this->log(array_merge(compact('config_path', 'alt_config_path', 'is_default_check'), array(
        'config_path_exists' => $this->debug ? file_exists($config_path) : null,
        'alt_config_path_exists' => $this->debug ? file_exists($alt_config_path) : null
      )));

      if (file_exists($config_path)) {
        $config_json = File::get_json($config_path);
      } elseif ($is_default_check && file_exists($alt_config_path)) {
        $config_json = File::get_json($alt_config_path);
        $config_path = $alt_config_path;
      }

      if ($config_json) {
        if (($config_dir = dirname($config_path))) {
          if (!in_array($config_dir, $import_paths)) {
            $import_paths[] = $config_dir;
          }

          $fetched_dir = Format::build_path($config_dir, 'fetched');

          if (!in_array($fetched_dir, $import_paths)) {
            $import_paths[] = $fetched_dir;
          }
        }

        if (isset($config_json['piler'])) {
          $config = $config_json['piler'];
        } elseif (isset($config_json['extra']) && isset($config_json['extra']['piler'])) {
          $config = $config_json['extra']['piler'];
        }
      }

      if ($config) {
        if (!$quiet) {
          $writer->colors("<info>Loaded</end> <subject>{$config_path}</end>", true);
        }

        if (isset($config['compile'])) {
          $file_groups = $config['compile'];
        }

        if (isset($config['config'])) {
          $imported_config = $config['config'];

          foreach($imported_config as $config_key => $config_value) {
            ${$config_key} = $config_value;
          }
        }
      } else {
        throw new \Error("A config file was found but it didn't have any valid data for Piler");
      }
    }

    $this->log('file_groups pre-validation', $file_groups);

    $file_groups = self::validate_file_groups($file_groups, compact('quiet', 'ignore_errors', 'import_paths', 'verbosity', 'working_directory', 'debug'));

    $this->log('file_groups pre-parsing', $file_groups);

    if (!$file_groups || !array_merge(...array_values($file_groups))) {
      if (!$quiet) {
        $writer->colors('<info>Nothing to compile</end>', true);
      }

      exit(0);
    }

    $file_groups = self::parse_file_groups($file_groups, compact('minify', 'debug'));

    $this->log('file_groups pre-processing', $file_groups);

    foreach($file_groups as $output_file => $input_files) {
      if ($working_directory && !str_starts_with($output_file, $working_directory)) {
        $output_file = Format::build_path($working_directory, $output_file);
      }

      $output_ext = pathinfo($output_file, PATHINFO_EXTENSION);
      $output_handler = Handler::get_handler_by_ext($output_ext, null, compact('event_source', 'minify', 'watching', 'debug'));
      $output = '';

      foreach($input_files as $input_file) {
        if (!$quiet) {
          Format::write_action($writer, 'loading', $input_file, $dry_run);
        }

        $input_ext = pathinfo($input_file, PATHINFO_EXTENSION);
        $input_import_paths = array_unique(array_merge(array(dirname($input_file)), $import_paths ?: array()));
        $input_handler = Handler::get_handler_by_ext($input_ext, $input_import_paths, compact('quiet', 'ignore_errors', 'verbosity', 'debug'));
        $input = file_get_contents($input_file);
        $processed_input = $input_handler->process_input($input, $input_file);
        $output .= $processed_input;
      }

      if (!$quiet) {
        Format::write_action($writer, 'compiling', $output_file, $dry_run);
      }

      $processed_output = $output_handler->process_output($output, $output_file);

      $output_file_exists = file_exists($output_file);

      if ($output_file_exists) {
        if (!$quiet && (!$yes_to_overwrite || $verbosity)) {
          $writer->colors("<warn>Warning</end> <subject>{$output_file}</end> <message>already exists</end>", true);
        }

        if ($bail_on_overwrite) {
          if (!$quiet && $verbosity) {
            $writer->colors('<info>Bailing...</end>', true);
          }

          exit(1);
        }

        if (!$yes_to_overwrite) {
          $interactor = new Interactor;
          $confirmation = $interactor->confirm('Overwrite it?', 'n');

          if (!$confirmation) {
            if ($verbosity) {
              $writer->colors('<info>Bailing...</end>', true);
            }

            exit(1);
          }
        }
      }

      if ($verbosity) {
        Format::write_action($writer, 'saving', $output_file, $dry_run);
      }

      if ($dry_run) {
        $bytes_written = true;
      } else {
        File::create_directory(dirname($output_file));

        $bytes_written = file_put_contents($output_file, $processed_output);
      }

      if (!$bytes_written) {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <message>Zero bytes written to</end> <subject>{$output_file}</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    }

    if ($save_changes && !$this->errors) {
      if ($config && isset($config['compile'])) {
        $config['compile'] = array_merge_recursive($config['compile'], $file_groups);
        $config_json = File::get_json($config_path) ?: array();
      } else {
        $config['compile'] = $file_groups;
        $config_json = array();
      }

      $config_json['piler'] = $config;

      $writer->colors(($dry_run ? '*' : null) . "<info>Updating</end> <subject>{$config_path}</end>...", true);

      if ($dry_run) {
        $config_bytes_written = true;
      } else {
        $config_bytes_written = file_put_contents($config_path, json_encode($config_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
      }

      if (!$config_bytes_written) {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <message>Zero bytes written to</end> <subject>{$config_path}</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    }

    $this->file_groups = $file_groups;

    if (!$quiet) {
      $completion_msg = '<info>Done!</end>';

      if ($this->errors) {
        $completion_msg .= ' ...<warn>but with errors</end>';
      }

      $writer->colors($completion_msg, true);

      if ($dry_run) {
        $writer->colors('<info>*Nothing was actually written because dry-run is enabled</end>', true);
      }
    }
  }

  public function assign_inputs_to_outputs($input, $output, $args) {
    $file_groups = array();

    extract(array_merge(Format::arr_values(self::$defaults, array(
      'quiet',
      'ignore_errors',
      'import_paths',
      'minify',
      'verbosity',
      'debug'
    )), $args));

    $this->debug = $debug;

    $input = array_reduce($input, function ($input_entries, $input_entry) use($quiet, $ignore_errors, $import_paths, $verbosity) {
      if (Format::is_glob($input_entry)) {
        $validated_entries = self::validate_input_entry($input_entry, compact('quiet', 'ignore_errors', 'import_paths', 'verbosity'));
        $input_entries = array_merge($input_entries, $validated_entries);
      } else {
        $input_entries[] = $input_entry;
      }

      return $input_entries;
    }, array());

    if ($output) {
      $writer = new Writer();

      $extensions = array_reduce($input, function ($exts, $confirmed_file) {
        $ext = pathinfo($confirmed_file, PATHINFO_EXTENSION);

        // @TODO: Make this dynamic somehow
        if ($ext === 'scss') $ext = 'css';

        if (!in_array($ext, $exts)) $exts[] = $ext;
        return $exts;
      }, array());

      $base_directories = array_values(array_reduce($input, function ($dirs, $confirmed_file) {
        $dir = dirname($confirmed_file);
        if (!in_array($dir, $dirs)) $dirs[] = $dir;
        return $dirs;
      }, array()));

      $base_directories_by_ext = array_reduce($input, function ($dirs, $confirmed_file) {
        $ext = pathinfo($confirmed_file, PATHINFO_EXTENSION);
        $dir = dirname($confirmed_file);

        // @TODO: Make this dynamic somehow
        if ($ext === 'scss') $ext = 'css';

        if (!isset($dirs[$ext])) $dirs[$ext] = array();
        if (!in_array($dir, $dirs[$ext])) $dirs[$ext][] = $dir;
        return $dirs;
      }, array());

      $global_output_dirs = array_filter($output, function ($dir) {
        return !pathinfo($dir, PATHINFO_EXTENSION);
      });

      $this->log(compact('extensions', 'base_directories', 'base_directories_by_ext', 'global_output_dirs'));

      $output_dirs = array_reduce($extensions, function ($dirs, $ext) use($output, $global_output_dirs) {
        $output_filter_result = array();

        array_walk($output, function ($output_entry, $output_entry_idx) use(&$output_filter_result, $ext) {
          $output_entry_ext = pathinfo($output_entry, PATHINFO_EXTENSION);

          if ($output_entry_ext && $output_entry_ext !== $ext) {
            return false;
          }

          $passes = $output_entry_ext === $ext;

          if ($passes) {
            $output_filter_result[] = $output_entry;
          }
        });

        array_walk($output, function ($output_entry, $output_entry_idx) use(&$output_filter_result, $dirs, $ext) {
          $output_entry_ext = pathinfo($output_entry, PATHINFO_EXTENSION);
          $found_so_far = count($output_filter_result);

          if ($output_entry_ext === '{ext}' && !$found_so_far) {
            $output_entry_ext = $ext;
            $output_entry = str_replace('{ext}', $ext, $output_entry);
          }

          if ($output_entry_ext && $output_entry_ext !== $ext) {
            return false;
          }

          $passes = (
            $output_entry_ext === $ext ||
            preg_match('/\{(?:types?|ext)\}/', $output_entry) !== 0
          );

          if ($passes) {
            $output_filter_result[] = $output_entry;
          }
        });

        if (!$output_filter_result && $global_output_dirs) {
          $output_filter_result = array(Format::build_path($global_output_dirs[0], "{name}.{$ext}"));
        }

        $dirs[$ext] = array_unique($output_filter_result);

        return $dirs;
      }, array());

      $this->log(compact('output_dirs'));

      foreach ($output_dirs as $ext => $output_dir_list) {
        if (!$output_dir_list) {
          $this->errors = true;

          if (!$quiet) {
            $writer->colors("<error>Error</end> <message>No valid outputs found for the</end> <subject>{$ext}</end> <message>file type</end>", true);
          }

          if (!$ignore_errors) {
            exit(1);
          }
        }
      }

      $valid_output_entries = array_values(array_unique(array_merge(...array_values($output_dirs))));

      $this->log(compact('valid_output_entries'));

      foreach($output as $output_entry) {
        $output_used = in_array($output_entry, $valid_output_entries);

        if (!$output_used && ($ext = pathinfo($output_entry, PATHINFO_EXTENSION)) && $ext === '{ext}') {
          foreach($extensions as $possible_ext) {
            $possible_output = str_replace('{ext}', $possible_ext, $output_entry);

            if (in_array($possible_output, $valid_output_entries)) {
              $output_used = true;
              break;
            }
          }
        }

        if (!$output_used) {
          $this->errors = true;

          if (!$quiet && $verbosity) {
            $writer->colors("<warn>Warning</end> <message>Could not decide what file type belongs to output</end> <subject>{$output_entry}</end>", true);
          }
        }
      }

      $file_groups = array_reduce($input, function ($groups, $confirmed_file) use($quiet, $writer, $ignore_errors, $output_dirs, $base_directories_by_ext) {
        $ext = pathinfo($confirmed_file, PATHINFO_EXTENSION);

        // @TODO: Make this dynamic somehow
        if ($ext === 'scss') $ext = 'css';

        $potential_outputs = $output_dirs[$ext];
        $group_name = null;

        if ($potential_outputs) {
          $potential_outputs_count = count($potential_outputs);

          if ($potential_outputs_count === 1) {
            $group_name = $potential_outputs[0];
          } else {
            $dir = dirname($confirmed_file);
            $base_dir_list = $base_directories_by_ext[$ext];

            if ($potential_outputs_count === count($base_dir_list) && ($potential_group_name_idx = array_search($dir, $base_dir_list)) !== -1) {
              $group_name = $potential_outputs[$potential_group_name_idx];
            }
          }
        }

        if ($group_name) {
          if (!isset($groups[$group_name])) {
            $groups[$group_name] = array();
          }

          $groups[$group_name][] = $confirmed_file;
        } else {
          $this->errors = true;

          if (!$quiet) {
            $writer->colors("<error>Error</end> <message>Could not decide what to do with</end> <subject>{$confirmed_file}</end>", true);
          }

          if (!$ignore_errors) {
            exit(1);
          }
        }

        return $groups;
      }, array());

      $this->log(compact('file_groups'));
    } else {
      $file_groups = array_reduce($input, function ($groups, $confirmed_file) use($minify) {
        $ext = pathinfo($confirmed_file, PATHINFO_EXTENSION);

        // @TODO: Make this dynamic somehow
        $output_ext = $ext;
        if ($ext === 'scss') $output_ext = 'css';

        // @TODO: Something about this being hardcoded seems weird?
        $extra_ext = $minify ? 'min' : 'compiled';
        $output_file = substr($confirmed_file, 0, -strlen($ext)) . "{$extra_ext}.{$output_ext}";

        if (!isset($groups[$output_file])) {
          $groups[$output_file] = array();
        }

        $groups[$output_file][] = $confirmed_file;
        return $groups;
      }, array());
    }

    $this->log(compact('file_groups'));

    return $file_groups;
  }

  public function parse_file_groups($file_groups, $args) {
    extract(array_merge(Format::arr_values(self::$defaults, array(
      'minify',
      'debug'
    )), $args));

    $this->debug = $debug;

    $parsed_file_groups = array();

    array_walk($file_groups, function ($input_files, $output_path) use(&$parsed_file_groups, $minify) {
      $output_ext = pathinfo($output_path, PATHINFO_EXTENSION);

      if (!$output_ext) {
        // @TODO: Again, seems weird to hardcode this but idk
        $output_path = Format::build_path($output_path, '{name}.' . ($minify ? 'min' : 'compiled') . '.{ext}');
      }

      $exts = array_reduce($input_files, function($exts, $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        // @TODO: Make this dynamic somehow
        if ($ext === 'scss') $ext = 'css';

        if (!isset($exts[$ext])) $exts[$ext] = array();
        $exts[$ext][] = $file;
        return $exts;
      }, array());

      foreach($exts as $ext => $files) {
        $parsed_output_path = $output_path;
        $type = null;

        // @TODO: Make this dynamic somehow
        switch($ext) {
          case 'css':
          case 'scss':
            $type = 'style';
            break;
          case 'js':
            $type = 'script';
            break;
          default:
            throw new \Error("Invalid extension {$ext}");
        }

        $parsed_output_path = str_replace('{type}', $type, $parsed_output_path);
        $parsed_output_path = str_replace('{types}', "{$type}s", $parsed_output_path);
        $parsed_output_path = str_replace('{ext}', $ext, $parsed_output_path);

        if (preg_match('/\{(?:name)\}/', $parsed_output_path) !== 0) {
          foreach($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $individiual_output_path = str_replace('{name}', $name, $parsed_output_path);

            if (!isset($parsed_file_groups[$individiual_output_path])) {
              $parsed_file_groups[$individiual_output_path] = array();
            }

            $parsed_file_groups[$individiual_output_path][] = $file;
          }
        } else {
          $parsed_file_groups[$parsed_output_path] = $files;
        }
      }
    });

    return $parsed_file_groups;
  }

  public function validate_file_groups($file_groups, $args) {
    extract(array_merge(Format::arr_values(self::$defaults, array(
      'quiet',
      'ignore_errors',
      'import_paths',
      'verbosity',
      'working_directory',
      'debug'
    )), $args));

    $this->debug = $debug;

    $writer = new Writer();

    foreach($file_groups as $group_output => $group_input) {
      $group_ext = pathinfo($group_output, PATHINFO_EXTENSION);
      $confirmed_files = array();

      if ($group_ext && !in_array($group_ext, array_diff(self::$supported_extensions, self::$preprocess_extensions))) {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <message>Output</end> <subject>{$group_output}</end> <message>is an invalid file type</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }

        continue;
      }

      foreach((array) $group_input as $group_input_file) {
        if ($working_directory && !str_starts_with($group_input_file, $working_directory)) {
          $group_input_file = Format::build_path($working_directory, $group_input_file);
        }

        $potential_files = self::validate_input_entry($group_input_file, compact('quiet', 'ignore_errors', 'import_paths', 'verbosity'));

        $this->log(compact('group_input', 'group_input_file', 'potential_files'));

        foreach($potential_files as $potential_file) {
          if ($group_ext) {
            $ext = pathinfo($potential_file, PATHINFO_EXTENSION);

            // @TODO: Make this dynamic somehow
            if ((in_array($ext, array('css', 'scss')) && $group_ext !== 'css') || ($ext === 'js' && $group_ext !== 'js')) {
              if (!$quiet && $verbosity) {
                $writer->colors("<warn>Warning</end> <subject>{$potential_file}</end> <message>cannot be written to</end> <subject>{$group_output}</end>", true);
              }

              continue;
            }

            $confirmed_files[] = $potential_file;
          }
        }
      }

      if (!$confirmed_files) {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <message>Input files for</end> <subject>{$group_output}</end> <message>turned up zero valid results</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }

        continue;
      }

      $file_groups[$group_output] = $confirmed_files;
    }

    return $file_groups;
  }

  public function validate_input_entry(string|array $input_entry, $args) {
    extract(array_merge(Format::arr_values(self::$defaults, array(
      'quiet',
      'ignore_errors',
      'import_paths',
      'verbosity',
      'debug'
    )), $args));

    $this->debug = $debug;

    $writer = new Writer();

    if (is_dir($input_entry)) {
      foreach(array_merge(array(''), $import_paths) as $import_path) {
        $glob_str = "{$input_entry}/*.{" . implode(',', self::$supported_extensions) . "}";

        if ($import_path) {
          $glob_str = "{$import_path}/{$glob_str}";
        }

        if ($directory_files = glob($glob_str, GLOB_BRACE)) {
          break;
        }
      }

      if ($directory_files) {
        return $directory_files;
      } else {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <message>No supported files were found in the</end> <subject>{$input_entry}</end> directory", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    } elseif (is_file($input_entry)) {
      $ext = pathinfo($input_entry, PATHINFO_EXTENSION);

      if (in_array($ext, self::$supported_extensions)) {
        return array($input_entry);
      } else {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <subject>{$input_entry}</end> <message>file type not supported</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    } else {
      foreach(array_merge(array(''), $import_paths) as $import_path) {
        $glob_str = $input_entry;

        if ($import_path) {
          $import_path_dir = dirname($import_path);

          if (str_starts_with($input_entry, $import_path_dir)) {
            $glob_str = substr($glob_str, strlen($import_path_dir) + 1);
          }

          $glob_str = "{$import_path}/{$glob_str}";
        }

        if ($potential_files = glob($glob_str, GLOB_BRACE)) {
          break;
        }
      }

      $valid_potential_files = array();

      foreach($potential_files as $potential_file) {
        $ext = pathinfo($potential_file, PATHINFO_EXTENSION);

        if (in_array($ext, self::$supported_extensions)) {
          $valid_potential_files[] = $potential_file;
        } else {
          if (!$quiet && $verbosity) {
            $writer->colors("<warn>Warning</end> <message>File type not supported</end>: <subject>{$potential_file}</end>", true);
          }
        }
      }

      if ($valid_potential_files) {
        return $valid_potential_files;
      } else {
        $this->errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end> <subject>{$input_entry}</end> <message>turned up zero valid results</end>", true);
        }

        if (!$ignore_errors) {
          exit(1);
        }
      }
    }

    return array();
  }

  public function watch($args) {
    set_time_limit(0);
    system('stty -icanon');
    stream_set_blocking(STDIN, false);

    $this->watching = true;

    $args['yes_to_overwrite'] = true;

    $watching_msg = 'Watching for file changes... (Press any key to stop)';
    $writer = new Writer;
    $stdin = fopen('php://stdin', 'r');

    $writer->colors('Doing an initial compile...', true);
    $this->run($args);
    $writer->colors($watching_msg);

    while(true) {
      if (ord(fgetc($stdin))) break;

      clearstatcache();

      $watch_files = Format::array_flatten($this->file_groups);

      foreach($watch_files as $watch_file) {
        $last_modified = filemtime($watch_file);

        if ($last_modified >= time()) {
          $time = date('Y-m-d H:i:s');
          $writer->colors("\n[<time>{$time}</end>] Change detected <subject>{$watch_file}</end>", true);
          $this->run($args);
          sleep(1);
          $writer->colors($watching_msg);
        }
      }
    }

    $this->watching = false;
    $args['event_source'] = null;
    $args['watch'] = null;

    $writer->colors('Compiling one last time...', true);
    $this->run($args);

    $writer->colors('Bye!', true);
  }

  public function log(...$log) {
    if (!$this->debug) return;

    foreach ($log as $key => $val) {
      if (is_array($val) || is_object($val)) {
        $log[$key] = print_r($val, true);
      }
    }

    $writer = new Writer;
    $caller = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
    $log_msg = implode(' ', $log);
    $msg = "[<warn>DEBUG</end>] {<info>{$caller}</end>} {$log_msg}";

    $writer->colors($msg, true);
  }
}
?>