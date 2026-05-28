<p align="center">
  <h1 align="center">PHP SndFile</h1>
</p>

<p align="center">Low-level audio I/O and resampling for PHP — backed by libsndfile and libsamplerate</p>

<p align="center">
  <a href="https://packagist.org/packages/phpmlkit/sndfile"><img src="https://img.shields.io/packagist/v/phpmlkit/sndfile?style=flat-square" alt="Latest Version"></a>
  <a href="https://github.com/phpmlkit/sndfile/actions"><img src="https://img.shields.io/github/actions/workflow/status/phpmlkit/sndfile/tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Workflow Status"></a>
  <a href="https://packagist.org/packages/phpmlkit/sndfile"><img src="https://img.shields.io/packagist/dt/phpmlkit/sndfile?style=flat-square" alt="Total Downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green.svg" alt="License"></a>
</p>

## Features

- **Read & write audio files** — WAV, FLAC, Ogg Vorbis, MP3, AIFF, and 20+ other formats
- **NDArray-native** — data flows in and out as `[frames × channels]` NDArrays, no intermediate buffers
- **Streaming I/O** — instance-based `SndFile` class with read, write, seek, tell, and block iteration
- **One-shot convenience** — `snd_read()` and `snd_write()` for simple load/save without managing a handle
- **Sample rate conversion** — `snd_resample()` with chunked progressive mode for large files, one-shot simple mode for
  small signals, and four quality levels
- **Metadata** — read and write title, artist, album, track number, and arbitrary SF_STR tags
- **Type-safe** — PHP 8.2+ with backed enums, readonly value objects, and strict types throughout

## Installation

```bash
composer require phpmlkit/sndfile
```

**Requirements:** PHP 8.2 or higher with the FFI extension enabled.

The package ships pre-compiled shared libraries for macOS (arm64, x86_64), Linux (x86_64, arm64), and Windows (x64).

## Quick Start

### Read and write

```php
use function PhpMlKit\Sndfile\{snd_read, snd_write, snd_info};

// Read a file — returns [NDArray, sampleRate]
[$audio, $sr] = snd_read('input.wav');
// $audio shape: [441000] (mono, Float32), $sr: 44100

// Write it back (format and subtype auto-detected from extension)
snd_write('output.wav', $audio, $sr);

// Probe metadata without loading data
$info = snd_info('input.wav');
echo "{$info->frames} frames, {$info->channels} channels, {$info->duration()}s";
```

### Resample

```php
use function PhpMlKit\Sndfile\snd_resample;
use PhpMlKit\Sndfile\Enums\ResampleQuality;

// Chunked progressive — safe for large files (default)
$resampled = snd_resample($audio, inputRate: 44100, outputRate: 22050);

// One-shot simple — best for small signals
$resampled = snd_resample($audio, 44100, 16000, chunkSize: null);

// Best quality, explicit chunk size
$resampled = snd_resample(
    $audio, 44100, 8000,
    quality: ResampleQuality::Best,
    chunkSize: 4096,
);
```

### Streaming with SndFile

```php
use PhpMlKit\Sndfile\SndFile;
use PhpMlKit\Sndfile\Enums\FileMode;

// Open for reading
$sf = new SndFile('input.wav', FileMode::Read);

// Seek and read
$sf->seek(44100);
$chunk = $sf->read(512);
echo $sf->tell(); // 44612

// Iterate in blocks
foreach ($sf->blocks(1024) as $block) {
    process($block);
}
$sf->close();

// Open for writing
$out = new SndFile('output.wav', FileMode::Write,
    sampleRate: 44100, channels: 2,
);
$out->setTitle('My Track');
$out->setArtist('My Artist');
$out->write($stereoData);
$out->close();
```

> See the [full documentation](https://phpmlkit.github.io/sndfile/) for detailed guides, tutorials, and a complete API reference.

## Core Concepts

### Audio data layout

All NDArrays in this library use the shape `[frames × channels]` — time first, channel second. A mono file produces
`[N]` or `[N, 1]` depending on the `always2d` flag. Stereo files always produce `[N, 2]`.

### DType, SampleFormat, and AudioFormat

Three independent choices interact when writing a file:

| Concept           | What it controls                 | Example                       |
|-------------------|----------------------------------|-------------------------------|
| **NDArray DType** | How the data is stored in memory | `Float32`, `Int16`, `Float64` |
| **SampleFormat**  | The file's encoding subtype      | `Pcm16`, `Float`, `Vorbis`    |
| **AudioFormat**   | The container                    | `Wav`, `Flac`, `Ogg`          |

When you call `snd_write()`, the NDArray's dtype is **automatically converted** to match the target `SampleFormat`. You
never need to call `astype()` yourself. The combination of `AudioFormat` and `SampleFormat` is validated against
libsndfile's compatibility table — an incompatible pair (like `Ogg + Pcm16`) throws a `SndfileException` before any data
is written.

## API Reference

### Global Functions

These are importable with `use function PhpMlKit\Sndfile\{...}` and provide the simplest path for common operations.

#### `snd_read()`

Read an audio file into a single NDArray.

```php
function snd_read(
    string $file,
    ?int $start = null,     // First frame (0-based; null = beginning)
    ?int $stop = null,      // One past last frame (null = EOF)
    bool $always2d = false, // If true, mono returns [frames, 1] instead of [frames]
    int $blocksize = 4096,  // Chunk size for internal read loop
): array // [NDArray, int sampleRate]
```

The dtype matches the file's native format. For partial reads, use `start` and `stop` to specify a frame range. With
`$always2d = false` (default), mono files return a 1D array for convenience.

```php
[$data, $sr] = snd_read('song.wav');
[$part, $sr] = snd_read('song.wav', start: 44100, stop: 88200);
[$data, $sr] = snd_read('song.wav', always2d: true);
```

#### `snd_write()`

Write an NDArray to an audio file.

```php
function snd_write(
    string $file,
    NDArray $data,               // [frames] or [frames, channels]
    int $sampleRate,             // Sample rate in Hz
    ?AudioFormat $format = null, // Inferred from extension if null
    ?SampleFormat $subtype = null, // Format's default if null
): void
```

1D arrays are automatically expanded to `[frames, 1]` before writing. The NDArray dtype is converted to match the target
subtype.

```php
snd_write('out.wav', $data, sampleRate: 44100);
snd_write('out.flac', $data, 44100, subtype: SampleFormat::Pcm24);
```

#### `snd_info()`

Read metadata without loading audio data. Opens the file, reads the header, and closes immediately.

```php
function snd_info(string $file): SndfileInfo
```

#### `snd_check_format()`

Validate that a container format and encoding subtype are compatible.

```php
function snd_check_format(AudioFormat $format, SampleFormat $subtype): bool
```

```php
snd_check_format(AudioFormat::Wav, SampleFormat::Pcm16);  // true
snd_check_format(AudioFormat::Ogg, SampleFormat::Pcm16);  // false
```

#### `snd_resample()`

Convert an NDArray from one sample rate to another.

```php
function snd_resample(
    NDArray $input,                     // [frames, channels]
    int $inputRate,                     // Source sample rate in Hz
    int $outputRate,                    // Target sample rate in Hz
    ResampleQuality $quality = ResampleQuality::Best,
    ?int $chunkSize = 2048,             // null = one-shot, int = chunked progressive
): NDArray                                // [newFrames, channels] Float32
```

When `$chunkSize` is non-null (default), the function uses `src_process` in a loop with a single pre-allocated output
buffer — safe for large signals. When null, it uses `src_simple` for a single-pass conversion.

The output is always `Float32` regardless of input dtype. Inputs of other dtypes are automatically converted.

```php
$resampled = snd_resample($data, 44100, 22050);           // chunked progressive
$resampled = snd_resample($data, 44100, 8000, chunkSize: null); // one-shot simple
```

### Classes

#### `SndFile`

An opened audio file handle for streaming read/write.

**Constructor:**

```php
new SndFile(
    string $path,
    FileMode $mode = FileMode::Read,
    // Write-mode parameters:
    ?int $sampleRate = null,
    ?int $channels = null,
    ?AudioFormat $format = null,
    ?SampleFormat $subtype = null,
)
```

In **read mode**, only `$path` and `$mode` are needed — metadata is read from the file header. In **write mode**,
`$sampleRate`, `$channels`, `$format`, and `$subtype` are required (format defaults to the file extension, subtype
defaults to the format's preferred).

**Instance methods:**

| Method                                            | Description                                                      |
|---------------------------------------------------|------------------------------------------------------------------|
| `read(?int $numFrames): NDArray`                  | Read up to N frames from current position. Null = all remaining. |
| `write(NDArray $data): void`                      | Write frames. Shape must match the file's channel count.         |
| `seek(int $offset, int $whence = SEEK_SET): void` | Move the read/write position.                                    |
| `tell(): int`                                     | Current frame position.                                          |
| `eof(): bool`                                     | Whether the position has reached the end.                        |
| `blocks(int $size = 4096): Generator`             | Yield NDArrays of up to `$size` frames.                          |
| `close(): void`                                   | Close the handle (called automatically by destructor).           |
| `info(): SndfileInfo`                             | Full file metadata.                                              |
| `frames(): int`                                   | Total frames.                                                    |
| `channels(): int`                                 | Channel count.                                                   |
| `sampleRate(): int`                               | Sample rate in Hz.                                               |

**Metadata getters/setters** (read/write on open handles):

| Getter          | Setter                   | SF_STR constant |
|-----------------|--------------------------|-----------------|
| `title()`       | `setTitle(string)`       | 0x01            |
| `copyright()`   | `setCopyright(string)`   | 0x02            |
| `software()`    | `setSoftware(string)`    | 0x03            |
| `artist()`      | `setArtist(string)`      | 0x04            |
| `comment()`     | `setComment(string)`     | 0x05            |
| `date()`        | `setDate(string)`        | 0x06            |
| `album()`       | `setAlbum(string)`       | 0x07            |
| `license()`     | `setLicense(string)`     | 0x08            |
| `trackNumber()` | `setTrackNumber(string)` | 0x09            |
| `genre()`       | `setGenre(string)`       | 0x10            |

Plus `getString(int $strType)` and `setString(int $strType, string $value)` for arbitrary SF_STR constants.

#### `SndfileInfo`

Immutable value object describing an audio file's metadata.

**Factory methods:**

| Method                                                                                                                 | Description                                        |
|------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------|
| `SndfileInfo::probe(string $path): self`                                                                               | Open file, read header, close.                     |
| `SndfileInfo::fromSfInfo(FFI\CData $sfInfo): self`                                                                     | Create from a populated libsndfile SF_INFO struct. |
| `SndfileInfo::forWrite(int $frames, int $channels, int $sampleRate, AudioFormat $format, SampleFormat $subtype): self` | Create write-ready info.                           |

**Properties:**

| Property       | Type           | Description                       |
|----------------|----------------|-----------------------------------|
| `frames`       | `int`          | Total frame count                 |
| `channels`     | `int`          | Number of audio channels          |
| `sampleRate`   | `int`          | Sample rate in Hz                 |
| `format`       | `AudioFormat`  | Container format                  |
| `sampleFormat` | `SampleFormat` | Encoding subtype                  |
| `sections`     | `int`          | Number of data sections           |
| `seekable`     | `bool`         | Whether the file supports seeking |

**Derived values:**

| Method       | Returns | Description                               |
|--------------|---------|-------------------------------------------|
| `duration()` | `float` | Duration in seconds                       |
| `nSamples()` | `int`   | Total sample values (`frames × channels`) |

**Immutable builders** — return a copy with one field changed:

| Method                          | Description          |
|---------------------------------|----------------------|
| `withFrames(int $f): self`      | Change frame count   |
| `withChannels(int $c): self`    | Change channel count |
| `withSampleRate(int $sr): self` | Change sample rate   |

### Enums

#### `AudioFormat`

Container formats (25 cases). Values are libsndfile `SF_FORMAT_*` constants.

```php
AudioFormat::Wav->extension();           // 'wav'
AudioFormat::fromExtension('flac');      // AudioFormat::Flac
AudioFormat::fromPath('song.mp3');       // AudioFormat::Mpeg
AudioFormat::Wav->defaultSampleFormat(); // SampleFormat::Pcm16
AudioFormat::Wav->compatibleSampleFormats(); // [PcmS8, Pcm16, Pcm24, ...]
```

#### `SampleFormat`

Encoding subtypes (20 cases). Values are libsndfile `SF_FORMAT_*` subtype constants.

```php
SampleFormat::Pcm16->bitDepth();    // 16
SampleFormat::Pcm16->isInteger();   // true
SampleFormat::Float->isPcm();       // false
SampleFormat::Float->toDtype();     // DType::Float32
SampleFormat::fromDtype(DType::Int16); // SampleFormat::Pcm16
```

#### `FileMode`

File open modes.

| Case                  | Description                          |
|-----------------------|--------------------------------------|
| `FileMode::Read`      | Open for reading                     |
| `FileMode::Write`     | Open for writing (creates/truncates) |
| `FileMode::ReadWrite` | Open for both reading and writing    |

#### `ResampleQuality`

libsamplerate converter quality levels.

| Case                       | Description                                      |
|----------------------------|--------------------------------------------------|
| `ResampleQuality::Best`    | Band-limited sinc, highest quality, slowest      |
| `ResampleQuality::Medium`  | Band-limited sinc, medium quality                |
| `ResampleQuality::Fastest` | Band-limited sinc, fastest                       |
| `ResampleQuality::Linear`  | Linear interpolation, fastest but lowest quality |

### Exceptions

`SndfileException` — thrown for I/O errors, invalid format combinations, closed-file operations, and resampling
failures. Extends `\RuntimeException`.

## Documentation

- [Full Documentation](https://phpmlkit.github.io/sndfile/)
- [What is SndFile?](https://phpmlkit.github.io/sndfile/guide/getting-started/what-is-sndfile)
- [Quick Start](https://phpmlkit.github.io/sndfile/guide/getting-started/quick-start)
- [API Reference](https://phpmlkit.github.io/sndfile/api/)

## Development

```bash
# Install PHP dependencies
composer install

# Run tests
composer test

# Run static analysis (PHPStan level 8)
composer lint

# Format code
composer cs:fix
```

## License

MIT

## Credits

- [libsndfile](https://github.com/libsndfile/libsndfile) — the C library for reading and writing audio files
- [libsamplerate](https://github.com/libsndfile/libsamplerate) — the C library for sample rate conversion
- [phpmlkit/ndarray](https://github.com/phpmlkit/ndarray) — high-performance N-dimensional arrays for PHP
