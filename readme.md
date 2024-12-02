<div align="center">

  ![Piler](./assets/piler.png)

  # Piler

  A CLI tool written in PHP that compiles and minifies JavaScript and CSS/SCSS files.

  [![packagist package version](https://img.shields.io/packagist/v/fivefifteen/piler.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/piler)
  [![packagist package downloads](https://img.shields.io/packagist/dt/fivefifteen/piler.svg?style=flat-square)](https://packagist.org/packages/fivefifteen/piler)
  [![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/fivefifteen/piler?style=flat-square)](https://github.com/fivefifteen/piler)
  [![license](https://img.shields.io/github/license/fivefifteen/piler.svg?style=flat-square)](https://github.com/fivefifteen/piler/blob/main/license.md)

  <a href="https://asciinema.org/a/693084" target="_blank"><img src="https://asciinema.org/a/693084.svg" width="75%" /></a>

  <a href="https://fivefifteen.com" target="_blank"><img src="./assets/fivefifteen.png" /><br /><b>A Five Fifteen Project</b></a>

</div>


## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
    - [Hot-Reloading](#hot-reloading)
- [Related Projects](#related-projects)
- [License Information](#license-information)


## Requirements

- PHP 8.1 or above
- Composer


## Installation

### To a package (local)

```
composer require-dev fivefifteen/piler
./vendor/bin/piler --version
```

### To your system (global)

```sh
composer global require fivefifteen/piler
piler --version
```


## Usage

```sh
piler [options...] [input...]
```


##### Options

 - `[-n|--bail-on-overwrite]` - Skips the confirmation prompt and bails if a file already exists in the output directory (takes priority over -y)
 - `[-c|--config]` - Path to a config JSON file [default: `pile.json`]
 - `[-d|--dry-run]` - Don't actually write anything so that you can test the command
 - `[-e|--event-source]` - Server accessible path to point EventSource to for hot-reloading (JS required)
 - `[-h|--help]` - Show help
 - `[-i|--ignore-errors]` - Ignore any errors that may occur and continue processing as much as possible
 - `[-p|--import-path...]` - Additional directory path(s) to import from (SCSS only)
 - `[-m|--no-minify]` - Minfy files [default: `true`]
 - `[-o|--output...]` - The directory path(s) or filename(s) to write to
 - `[-q|--quiet]` - Run but don't output anything in the terminal
 - `[-s|--save]` - Save list of compiled files to pile.json to easily run again later
 - `[-v|--verbosity]` - Verbosity level [default: `0`]
 - `[-V|--version]` - Show version
 - `[-W|--watch]` - Monitor defined files for changes and re-process when they are changed (enables -y)
 - `[-w|--working-directory]` - Sets the working directory that all paths will be relative to [default: `.`]
 - `[-y|--yes-to-overwrite]` - Skips the confirmation prompt and overwrites files if they already exist in the output directory


##### Examples

```sh
# Process files listed in the `compile` section of a custom config file
piler --config content/themes/my-theme/compile.json

# Minify all .js files in the `scripts` directory (minified files will be saved as *.min.js in the scripts directory)
piler scripts/*.js

# Minify all .js files in the `scripts` directory and save the minified files to the build directory
piler scripts/*.js --output build

# Minify all .js files in the `scripts` directory and save the minified files with a customized prefix to the build directory
piler scripts/*.js --output build/compiled-{name}.min.js

# Compile all .scss files in the `styles` directory to minified css (minified files will be saved as *.min.css in the styles directory)
piler styles/*.scss

# Compile all .scss files in the `styles` directory to minified css and save the files to the build directory
piler styles/*.scss --output build

# Compile all .scss and .css files in the `styles` directory to minified css and save the files with a customized prefix to the build directory
piler styles/*.{scss,css} --output build/compiled-{name}.min.css
piler styles/*.{scss,css} scripts/*.js -o build/compiled-{name}.min.css # same as above since output is specifically set to .css so .js files will be ignored

# Minify all .js files in the `scripts` directory and compile all .scss and .css files in the `styles` directory, put all minified .js and .css files in the build directory with a custom prefix
piler styles/*.{scss,css} scripts/*.js --output build/compiled-{name}.min.{ext}
piler styles/*.{scss,css} scripts/*.js --output build/compiled-{name}.min.css --output build/compiled-{name}.min.js # same as above
piler styles/*.{scss,css} scripts/*.js -o build/compiled-{name}.min.css -o build/compiled-{name}.min.js # same as above

# Concat and minify all .js files in the `scripts` directory and concat and compile all .scss and .css files in the `styles` directory, put all minified .js and .css files in the build directory with a custom prefix (when concat option is used, {name} will become 'script' for js and 'style' for css. use {names} for plural)
piler styles/*.{scss,css} scripts/*.js --output build/compiled-{name}.min.{ext} # will end up with a compiled-script.min.js and a compiled-style.min.css
piler styles/*.{scss,css} scripts/*.js -o build/compiled-script.min.js -o build/compiled-style.min.js # same as above
piler styles/*.{scss,css} scripts/*.js --output build/compiled-{names}.min.{ext} # will end up with a compiled-scripts.min.js and a compiled-styles.min.css
piler styles/*.{scss,css} scripts/*.js -o build/compiled-scripts.min.js -o build/compiled-styles.min.js # same as above

# Concat and minify all .js files in the `scripts/src` and `scripts/vendor` directories into one minified .js file in the build directory
piler scripts/src/*.js scripts/vendor/*.js -o build/compiled-scripts.min.js

# Concat and minify all .js files in the `scripts/src` and `scripts/vendor` directories into two minified .js files in the build directory
piler scripts/src/*.js scripts/vendor/*.js -o build/compiled-scripts.min.js -o build/vendor-scripts.min.js
```


## Configuration

While Piler can be used out of the box without any configuration, a config file allows for better customization and easier package management.

```json
{
  "compile": {
    "style.css": "scss/style.scss",
    "js/scripts.min.js": [
      "fetched/bluzky/nice-select2/dist/js/nice-select2.js",
      "js/src/*.js"
    ]
  },
  "settings": {
    "piler": {
      "config_path": "compile.json",
      "event_source": "/reloader.php",
      "import_paths": ["fetched"],
      "minify": true,
      "yes_to_overwrite": true
    }
  }
}
```

### Hot-Reloading

Piler can be configured to automatically reload your page whenever you make changes to your compiled files. This works by injecting a small piece of JavaScript into your code (which means at least one compiled JavaScript file is required).

To set this up, create a `reloader.php` file somewhere accessible by the front-end of your website and place the following contents in there:

```php
<?php
require_once('./vendor/autoload.php'); // Update this to the path of your project's composer autoload.php
new \Piler\Reloader('./pile.json'); // Update this to the path of your projects pile.json
?>
```

Then just set the event source to the front-end accessible path of the file above either by setting the `event_source` setting in a config file (example above), or by using the `--event-source` option in the command line.

After that, simply use the `--watch` option and your page should then auto-reload when you make changes to your files.

#### Examples

```sh
piler --watch --event-source "/reloader.php"
piler -We "reloader.php" # same as above

# The --event-source option isn't needed if `event_source` is set in the config file
piler --watch
piler -W # same as above
```

## Related Projects

 - [piler](https://github.com/fivefifteen/piler) - A package manager written in PHP that supports installing dependencies from GitHub, npm, custom URLs, and local file paths. 🐶


## License Information

MIT. See the [license.md file](license.md) for more info.