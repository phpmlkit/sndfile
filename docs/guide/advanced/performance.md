---
title: Performance & memory
---

# Performance & memory

SndFile is designed to be efficient for common I/O tasks, but audio can be large. This page outlines the main levers you
have.

## One-shot vs streaming

- **One-shot (`snd_read`)** loads the entire signal into memory.
- **Streaming (`SndFile::blocks`)** lets you process incrementally with bounded memory.

If you are processing long recordings, prefer streaming.

## `snd_read` block size

`snd_read()` reads data in a loop using `blocksize` frames per iteration, but still allocates a single output buffer for
the full requested range.

`blocksize` affects performance and temporary working set (not the final result).

```php
[$x, $sr] = snd_read('input.wav', blocksize: 16384);
```

## Resampling: chunked mode

The default resampler path is chunked progressive processing (`chunkSize` is non-null). This avoids repeatedly
allocating output arrays per chunk and is safer for large signals.

```php
$y = snd_resample($x, 44100, 16000, chunkSize: 8192);
```

For small signals, one-shot `chunkSize: null` is often fine and can be simpler.

## DTypes and conversion cost

Writing converts the NDArray dtype to match the chosen `SampleFormat`.

If you already know what you’ll write, you can pre-convert once in your pipeline to avoid repeated conversions:

```php
use PhpMlKit\NDArray\DType;
use PhpMlKit\Sndfile\Enums\SampleFormat;

$x = $x->astype(SampleFormat::Pcm16->toDtype()); // Int16
```

## Practical tips

- Prefer `SndFile::blocks()` + incremental processing for long audio.
- Keep shapes consistent (`[frames, channels]`) to avoid repeated reshaping.
- When resampling, pick quality intentionally:
  - `Best` for offline processing
  - `Fastest` for throughput
  - `Linear` for cheap previews

