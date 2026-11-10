<?php
use \PHPUnit\Framework\TestCase;
use \Piler\Compile;
use \Piler\Helper\File;
use \Piler\Helper\Format;

class CompileTest extends TestCase {
  static $playground = 'tests' . DIRECTORY_SEPARATOR . 'playground' . DIRECTORY_SEPARATOR . 'CompileTest';
  static $test_files = 'tests' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'files';

  protected function setUp(): void {
    File::delete_directory(self::$playground);
    File::create_directory(self::$playground);
  }

  public function tearDown(): void {
    File::delete_directory(self::$playground);
  }

  static function copyTestFile(string $file_name, string $dest_dir, string $new_name = null) {
    $from_path = Format::build_path(self::$test_files, $file_name);
    $to_path = Format::build_path($dest_dir, $new_name ?: basename($file_name));

    File::create_directory($dest_dir);

    if (copy($from_path, $to_path)) {
      return $to_path;
    }

    return false;
  }

  public function testBasicCompile1() {
    $path = Format::build_path(self::$playground, 'testBasicCompile1');
    $cmd = new Compile();

    self::copyTestFile('test.scss', $path);
    self::copyTestFile('test.js', $path);

    $cmd->run(array(
      'input'             => array(
        '*.{scss,js}'
      ),
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'test.min.css');
    $test_file2 = Format::build_path($path, 'test.min.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
  }

  public function testBasicCompile2() {
    $path = Format::build_path(self::$playground, 'testBasicCompile2');
    $cmd = new Compile();

    self::copyTestFile('test.scss', $path);
    self::copyTestFile('test.js', $path);

    $cmd->run(array(
      'input'             => array(
        '*.{css,scss,js}'
      ),
      'output'            => array(
        '{types}.{ext}'
      ),
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'styles.css');
    $test_file2 = Format::build_path($path, 'scripts.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
  }

  public function testBasicCompile3() {
    $path = Format::build_path(self::$playground, 'testBasicCompile3');
    $cmd = new Compile();

    self::copyTestFile('test.scss', $path);
    self::copyTestFile('test2.scss', $path);
    self::copyTestFile('test.js', $path);

    $cmd->run(array(
      'input'             => array(
        '*.{css,scss,js}'
      ),
      'output'            => array(
        '{name}.min.{ext}'
      ),
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'test.min.css');
    $test_file2 = Format::build_path($path, 'test2.min.css');
    $test_file3 = Format::build_path($path, 'test.min.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
    $this->assertTrue(is_file($test_file3));
  }

  public function testBasicCompile4() {
    $path = Format::build_path(self::$playground, 'testBasicCompile4');
    $cmd = new Compile();

    self::copyTestFile('test.scss', $path);
    self::copyTestFile('test2.scss', $path);
    self::copyTestFile('test.js', $path);
    self::copyTestFile(
      Format::build_path('only-scripts', 'only-scripts.js'),
      Format::build_path($path, 'only-scripts')
    );
    self::copyTestFile(
      Format::build_path('only-scripts', 'scripts.js'),
      Format::build_path($path, 'only-scripts')
    );

    $cmd->run(array(
      'input'             => array(
        '*.{css,scss,js}',
        'only-scripts/*.js'
      ),
      'output'            => array(
        'scripts/{name}.min.js',
        '{name}.min.{ext}'
      ),
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'test.min.css');
    $test_file2 = Format::build_path($path, 'test2.min.css');
    $test_file3 = Format::build_path($path, 'scripts/test.min.js');
    $test_file4 = Format::build_path($path, 'scripts/only-scripts.min.js');
    $test_file5 = Format::build_path($path, 'scripts/scripts.min.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
    $this->assertTrue(is_file($test_file3));
    $this->assertTrue(is_file($test_file4));
    $this->assertTrue(is_file($test_file5));
  }

  public function testBasicCompile5() {
    $path = Format::build_path(self::$playground, 'testBasicCompile5');
    $cmd = new Compile();

    self::copyTestFile('test.js', $path);
    self::copyTestFile(
      Format::build_path('only-scripts', 'only-scripts.js'),
      Format::build_path($path, 'only-scripts')
    );

    $cmd->run(array(
      'input'             => array(
        '*.js',
        'only-scripts/*.js'
      ),
      'output'            => array(
        'test.min.js',
        'only-scripts.min.js'
      ),
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'test.min.js');
    $test_file2 = Format::build_path($path, 'only-scripts.min.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
  }

  public function testConfigCompile1() {
    $path = Format::build_path(self::$playground, 'testConfigCompile1');
    $cmd = new Compile();

    self::copyTestFile('compile.json', $path);
    self::copyTestFile('test.css', $path);
    self::copyTestFile('test.js', $path);
    self::copyTestFile('test2.scss', $path);
    self::copyTestFile('only-styles/only-styles.css', Format::build_path($path, 'only-styles'));
    self::copyTestFile('only-scripts/only-scripts.js', Format::build_path($path, 'only-scripts'));

    $cmd->run(array(
      'config_path'       => 'compile.json',
      'quiet'             => true,
      'working_directory' => $path
    ));

    $test_file1 = Format::build_path($path, 'test-compiled.css');
    $test_file2 = Format::build_path($path, 'styles-compiled.css');
    $test_file3 = Format::build_path($path, 'scripts-compiled.js');

    $this->assertTrue(is_file($test_file1));
    $this->assertTrue(is_file($test_file2));
    $this->assertTrue(is_file($test_file3));
  }

  public function testJsStatementBoundary() {
    $cases = array(
      'function' => array(
        'file' => 'starts-with-function.js',
        'next' => 'function'
      ),
      'paren' => array(
        'file' => 'starts-with-paren.js',
        'next' => '(function'
      )
    );

    $brace = file_get_contents(Format::build_path(self::$test_files, 'join/ends-with-brace.js'));
    $this->assertIsString($brace);
    $this->assertStringEndsWith('}', $brace);
    $this->assertDoesNotMatchRegularExpression("/\n$/", $brace);

    foreach (array(true, false) as $minify) {
      foreach ($cases as $name => $case) {
        $path = Format::build_path(self::$playground, 'testJsStatementBoundary-' . ($minify ? 'min' : 'plain') . '-' . $name);
        $cmd = new Compile();

        self::copyTestFile('join/ends-with-brace.js', $path, 'ends-with-brace.js');
        self::copyTestFile('join/' . $case['file'], $path, $case['file']);

        $cmd->run(array(
          'input'             => array(
            'ends-with-brace.js',
            $case['file']
          ),
          'output'            => array(
            'bundle.js'
          ),
          'minify'            => $minify,
          'quiet'             => true,
          'working_directory' => $path,
          'yes_to_overwrite'  => true
        ));

        $bundle = Format::build_path($path, 'bundle.js');
        $this->assertTrue(is_file($bundle));

        $contents = file_get_contents($bundle);
        $this->assertIsString($contents);
        $this->assertStringNotContainsString('}' . $case['next'], $contents);

        if ($minify) {
          $this->assertStringContainsString('};' . $case['next'], $contents);
        } else {
          $this->assertStringContainsString("}\n;\n" . $case['next'], $contents);
        }
      }
    }
  }
}
?>
