<?php
namespace Piler\Handler;

use \MatthiasMullie\Minify\JS as Minifier;

class JsHandler extends Handler {
  public $event_source = null;
  public $minify = true;
  public $watching = false;

  public function __construct($import_paths = array(), $args = array()) {
    if (isset($args['event_source'])) $this->event_source = $args['event_source'];
    if (isset($args['minify'])) $this->minify = $args['minify'];
    if (isset($args['watching'])) $this->watching = $args['watching'];
  }

  public function process_input($input, $input_file) {
    return $input;
  }

  public function process_output($output, $output_file) {
    if ($this->watching && $this->event_source) {
      $output = "// Piler Hot-Reload Script
if (!window.pilerHotReloader) {
  window.pilerHotReloader = new EventSource('{$this->event_source}')

  console.log('[Piler] Listening for reload instructions from {$this->event_source}')

  pilerHotReloader.addEventListener('message', function (e) {
    if (e.data) {
      try {
        var data = JSON.parse(e.data)
        if (data && data.action && data.action == 'reload') {
          console.log('[Piler] Change detected. Refreshing page...', data)
          location.reload()
        }
      } catch(error) {
        console.error('[Piler] Error', error)
      }
    }
  });
}\n\n" . $output;
    }

    if ($this->minify) {
      $minifier = new Minifier($output);
      return $minifier->minify();
    }

    return $output;
  }
}
?>