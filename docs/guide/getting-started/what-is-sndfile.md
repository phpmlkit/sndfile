# What is SndFile?

SndFile is a low-level PHP library for reading, writing, and resampling audio files. It provides direct FFI bindings to [libsndfile](https://github.com/libsndfile/libsndfile) and [libsamplerate](https://github.com/libsndfile/libsamplerate), two battle-tested C libraries that handle over 25 audio formats.

## What it does

- **Read** audio files into `NDArray` objects
- **Write** `NDArray` objects to audio files
- **Resample** audio between sample rates with four quality levels
- **Stream** audio in blocks for memory-efficient processing of large files
- **Manage metadata** — read and write title, artist, album tags, and arbitrary fields

## What it is NOT

SndFile is not a full-featured audio processing library. It does not try to be a DSP library (filters, spectrograms, effects, etc.). For those capabilities, you want a higher-level library built on top of SndFile or you could pair this with `phpmlkit/ndarray` operations (FFT, windows, etc.).

It is also not a CLI tool or a standalone application. It is a PHP library meant to be used within your PHP projects.

## Architecture

SndFile sits at the boundary between PHP and C:

```
┌──────────────────────────┐
│ Your PHP application     │
├──────────────────────────┤
│ SndFile (this package)   │  ← FFI bindings, singleton backends
├──────────────────────────┤
│ libsndfile + libsamplerate│  ← C shared libraries
└──────────────────────────┘
```

The package ships pre-compiled shared libraries for macOS (arm64, x86_64), Linux (x86_64, arm64), and Windows (x64). They are installed automatically.

## Two usage styles

SndFile provides two ways to work with audio:

### Namespaced functions — simple, one-shot

Use these when you want to load a whole file, save a whole file, or probe metadata in one call.

- `snd_read()` — load an entire file (or a slice) into one NDArray
- `snd_write()` — write an NDArray to disk
- `snd_info()` — probe metadata without loading the audio
- `snd_resample()` — resample an NDArray

```php
use function PhpMlKit\Sndfile\snd_read;

[$audio, $sr] = snd_read('song.wav');
```

The file is opened, the operation is performed, and the handle is closed — all internally.

### SndFile class — streaming, full control

Use this when you need to stream large files, seek to arbitrary positions, append data with multiple writes, set/read string metadata tags, or iterate in blocks.

```php
use PhpMlKit\Sndfile\SndFile;
use PhpMlKit\Sndfile\Enums\FileMode;

$sf = new SndFile('large-file.wav', FileMode::Read);
foreach ($sf->blocks(4096) as $block) {
    // Process each block without loading the entire file
}
$sf->close();
```

You control when the handle is opened and closed.
