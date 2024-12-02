<?php
use \PHPUnit\Framework\TestCase;
use \Piler\Compile;

class CompileFunctionsTest extends TestCase {
  protected function setUp(): void {}

  public function testAssignInputsToOutputs1() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js'
    );

    $output = array(
      'tests/playground/styles.css',
      'tests/playground/scripts.js'
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/playground/styles.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
      ),
      'tests/playground/scripts.js' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testAssignInputsToOutputs2() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js'
    );

    $output = array();

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/data/files/test.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/data/files/test2.min.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/data/files/test.min.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/data/files/only-scripts/only-scripts.min.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/data/files/only-scripts/scripts.min.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testAssignInputsToOutputs3() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js'
    );

    $output = array();

    $args = array(
      'quiet'   => true,
      'minify'  => false
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/data/files/test.compiled.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/data/files/test2.compiled.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/data/files/test.compiled.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/data/files/only-scripts/only-scripts.compiled.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/data/files/only-scripts/scripts.compiled.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testAssignInputsToOutputs4() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js',
    );

    $output = array(
      'tests/playground/styles',
      'tests/playground/scripts/{name}.js'
    );

    $args = array(
      'quiet' => false
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/playground/styles/{name}.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss'
      ),
      'tests/playground/scripts/{name}.js' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testAssignInputsToOutputs5() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js',
    );

    $output = array(
      'tests/playground/styles/{name}.css',
      'tests/playground/scripts/{name}.js'
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/playground/styles/{name}.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
      ),
      'tests/playground/scripts/{name}.js' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testAssignInputsToOutputs6() {
    $cmd = new Compile();

    $input = array(
      'tests/data/files/{,**/}*.{css,scss}',
      'tests/data/files/test.js',
      'tests/data/files/only-scripts/only-scripts.js',
      'tests/data/files/only-scripts/scripts.js',
    );

    $output = array(
      'tests/playground/styles/{name}.css',
      'tests/playground/scripts/{name}.js'
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->assign_inputs_to_outputs($input, $output, $args);

    $expected_result = array(
      'tests/playground/styles/{name}.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
        'tests/data/files/only-styles/only-styles.css',
        'tests/data/files/only-styles/styles.scss'
      ),
      'tests/playground/scripts/{name}.js' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testFileGroupValidation1() {
    $cmd = new Compile();

    $file_groups = array(
      'tests/data/files/test.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/data/files/test2.min.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/data/files/test.min.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/data/files/only-scripts/only-scripts.min.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/data/files/only-scripts/scripts.min.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $args = array(
      'quiet'             => true,
      'working_directory' => 'tests/data/files'
    );

    $actual_result = $cmd->validate_file_groups($file_groups, $args);

    $expected_result = array(
      'tests/data/files/test.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/data/files/test2.min.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/data/files/test.min.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/data/files/only-scripts/only-scripts.min.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/data/files/only-scripts/scripts.min.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testInputEntryValidation1() {
    $cmd = new Compile();

    $input_entry = 'tests/data/files';

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->validate_input_entry($input_entry, $args);

    $expected_result = array(
      'tests/data/files/test.js',
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testInputEntryValidation2() {
    $cmd = new Compile();

    $input_entry = 'tests/data/files/*.{css,scss}';

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->validate_input_entry($input_entry, $args);

    $expected_result = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testInputEntryValidation3() {
    $cmd = new Compile();

    $input_entry = 'tests/data/files/{,**/}*.{css,scss}';

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->validate_input_entry($input_entry, $args);

    $expected_result = array(
      'tests/data/files/test.css',
      'tests/data/files/test.scss',
      'tests/data/files/test2.scss',
      'tests/data/files/only-styles/only-styles.css',
      'tests/data/files/only-styles/styles.scss'
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testFileGroupParsing1() {
    $cmd = new Compile();

    $file_groups = array(
      'tests/playground/styles/{name}.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
      ),
      'tests/playground/{types}' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->parse_file_groups($file_groups, $args);

    $expected_result = array(
      'tests/playground/styles/test.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/playground/styles/test2.min.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/playground/scripts/test.min.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/playground/scripts/only-scripts.min.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/playground/scripts/scripts.min.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testFileGroupParsing2() {
    $cmd = new Compile();

    $file_groups = array(
      'tests/playground/{types}/{name}.min.{ext}' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->parse_file_groups($file_groups, $args);

    $expected_result = array(
      'tests/playground/styles/test.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss'
      ),
      'tests/playground/styles/test2.min.css' => array(
        'tests/data/files/test2.scss'
      ),
      'tests/playground/scripts/test.min.js' => array(
        'tests/data/files/test.js'
      ),
      'tests/playground/scripts/only-scripts.min.js' => array(
        'tests/data/files/only-scripts/only-scripts.js'
      ),
      'tests/playground/scripts/scripts.min.js' => array(
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }

  public function testFileGroupParsing3() {
    $cmd = new Compile();

    $file_groups = array(
      'tests/playground/compiled-{types}.min.{ext}' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss',
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $args = array(
      'quiet' => true
    );

    $actual_result = $cmd->parse_file_groups($file_groups, $args);

    $expected_result = array(
      'tests/playground/compiled-styles.min.css' => array(
        'tests/data/files/test.css',
        'tests/data/files/test.scss',
        'tests/data/files/test2.scss'
      ),
      'tests/playground/compiled-scripts.min.js' => array(
        'tests/data/files/test.js',
        'tests/data/files/only-scripts/only-scripts.js',
        'tests/data/files/only-scripts/scripts.js'
      )
    );

    $this->assertSame($expected_result, $actual_result);
  }
}
?>