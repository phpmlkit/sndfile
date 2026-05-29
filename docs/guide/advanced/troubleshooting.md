---
title: Troubleshooting
---

# Troubleshooting

## “FFI extension not loaded”

Make sure the `FFI` extension is enabled for the PHP binary you're running:

```bash
php -m | grep FFI
php --ini
```

Enable it in the `php.ini` used by the CLI:

```ini
extension=ffi
```

## “Platform package not found” / missing binaries

This package loads platform-specific shared libraries from its `lib/<platform>/` directory.

If installation did not include the correct binaries:

- verify your platform is supported (macOS arm64/x86_64, Linux arm64/x86_64, Windows x64)
- clear composer cache: `composer clear-cache`
- reinstall: `composer require phpmlkit/soundfile`

## “Library file not found” / “Header file not found”

These errors come from `PhpMlKit\SoundFile\FFI\NativeLibrary`.

They usually indicate:

- a broken vendor install (missing `lib/` or `include/`)
- filesystem permissions that prevent reading from `vendor/`

## “Cannot determine format for '...'”

Format inference uses the file extension via `AudioFormat::fromPath()`.

Fix:

- use a recognized extension (`.wav`, `.flac`, `.ogg`, `.mp3`, ...)
- or pass `AudioFormat` explicitly:

```php
use PhpMlKit\SoundFile\Enums\AudioFormat;
use function PhpMlKit\SoundFile\sf_write;

sf_write('output.audio', $x, 44100, format: AudioFormat::Wav);
```

## “Incompatible format/subtype”

Not all format/subtype combinations are writable. Example: OGG + PCM16 is invalid.

Use `sf_check_format()` to validate:

```php
use PhpMlKit\SoundFile\Enums\AudioFormat;
use PhpMlKit\SoundFile\Enums\SampleFormat;
use function PhpMlKit\SoundFile\sf_check_format;

if (!sf_check_format(AudioFormat::Ogg, SampleFormat::Pcm16)) {
    // pick Vorbis instead
}
```

## Seek errors

If a file is not seekable, `SoundFile::seek()` throws:

- “This file does not support seeking”

For non-seekable sources, use sequential reads (`blocks()`), and avoid random access.

## Metadata returns null

If `title()` / `artist()` etc. return `null`, either:

- the tag is not present
- or the file format doesn’t store that field the way libsndfile expects

Try another container like WAV/AIFF/CAF for metadata-heavy workflows.

