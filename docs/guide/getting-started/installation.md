# Installation

## Requirements

- PHP 8.2 or higher
- The [FFI extension](https://www.php.net/manual/en/book.ffi.php) enabled

## Install via Composer

```bash
composer require phpmlkit/sndfile
```

The package ships pre-compiled shared libraries for all major platforms.

::: tip Platform Support
Pre-compiled libraries are provided for:
- **macOS** — arm64 (Apple Silicon), x86_64 (Intel)
- **Linux** — x86_64, arm64
- **Windows** — x64
:::

## Verifying the Installation

```php
<?php

require_once 'vendor/autoload.php';

use function PhpMlKit\Sndfile\snd_info;

$info = snd_info(__DIR__.'/test.wav');
echo "Frames: {$info->frames}, Channels: {$info->channels}, Sample rate: {$info->sampleRate}";
```

If this runs without errors, the library and its native dependencies are loaded correctly.

If anything fails, see:

- [Troubleshooting](/guide/advanced/troubleshooting)
