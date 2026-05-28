# Formats and DTypes

When writing an audio file, three independent “type” concepts interact:

- **NDArray dtype**: how samples are stored in memory (e.g. `Float32`, `Int16`)
- **SampleFormat**: how samples are encoded in the file (e.g. PCM16, Float, Vorbis)
- **AudioFormat**: the container format (e.g. WAV, FLAC, OGG, MP3)

This guide explains how SndFile combines them on **read** and **write**.

---

## NDArray dtype (in-memory)

`phpmlkit/ndarray` uses `DType` to represent in-memory types.

Examples:

- `DType::Int16` — common for PCM WAV
- `DType::Float32` — common for floating-point audio
- `DType::Float64` — high precision

---

## SampleFormat (file encoding subtype)

`SampleFormat` maps to libsndfile `SF_FORMAT_*` subtype constants.

Examples:

- `SampleFormat::Pcm16`
- `SampleFormat::Float`
- `SampleFormat::Vorbis`
- `SampleFormat::MpegLayerIII`

Important details:

- Some subtypes have a meaningful `bitDepth()` (PCM), while compressed formats return 0.
- `toDtype()` provides the dtype used when reading/writing that subtype.

---

## AudioFormat (container)

`AudioFormat` maps to libsndfile major format constants (`SF_FORMAT_WAV`, `SF_FORMAT_FLAC`, ...).

It also provides convenience:

- `AudioFormat::fromExtension()` / `fromPath()`
- `extension()`
- `defaultSampleFormat()`
- `compatibleSampleFormats()`

---

## What happens on read?

When you read a file, SndFle:

- Reads the file header to determine format + subtype.
- Chooses the dtype based on the Sample Format.
- Allocates one C buffer and reads frames in chunks into that buffer.
- Creates an NDArray from that buffer.

## What happens on write?

- If you omit `AudioFormat`, it is inferred from the file extension.
- If the extension is unknown, SndFile throws:
- If you omit `SampleFormat`, it defaults to the Audio format's default sample format:
  - WAV → PCM16
  - FLAC → PCM16
  - OGG → Vorbis
  - MP3 → MpegLayerIII
  - CAF → Float
- The input NDArray is converted to the dtype implied by the chosen SampleFormat:
  - WAV + PCM16 → `Int16`
  - WAV + Float → `Float32`
  - WAV + Double → `Float64`
- The data is written to the file.

## Format Compatibility

Not every AudioFormat supports every SampleFormat. Use `snd_check_format()` to validate:

```php
use function PhpMlKit\Sndfile\snd_check_format;
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\SampleFormat;

// Common valid combinations
snd_check_format(AudioFormat::Wav, SampleFormat::Pcm16);     // true
snd_check_format(AudioFormat::Wav, SampleFormat::Float);     // true
snd_check_format(AudioFormat::Flac, SampleFormat::Pcm16);    // true
snd_check_format(AudioFormat::Flac, SampleFormat::Pcm24);    // true
snd_check_format(AudioFormat::Ogg, SampleFormat::Vorbis);    // true
snd_check_format(AudioFormat::Aiff, SampleFormat::Float);    // true

// Invalid combinations
snd_check_format(AudioFormat::Ogg, SampleFormat::Pcm16);     // false
snd_check_format(AudioFormat::Flac, SampleFormat::Float);    // false
```

An invalid combination throws a `SndfileException` before any file is created.

## Bit Depth and Clipping

PCM formats store integer samples (e.g. `Int16`) and floating formats store `Float32/Float64`.

SndFile does not impose an extra normalization layer; you are responsible for choosing a representation that matches
your pipeline. If you write float data to a PCM subtype, it will be converted to integers. For example, when writing Float21 data as Pcm16, values outside the Int16 range (-32768 to 32767) are clipped by libsndfile:

```php
// Values > 32767 clip to 32767
$loud = NDArray::array([[50000.0], [-50000.0]], DType::Float32);
snd_write('loud.wav', $loud, 44100); // Written as Pcm16 — values clipped

[$read, $] = snd_read('loud.wav');
$arr = $read->toArray();
// $arr[0] will be ~32767, $arr[1] will be ~-32768
```

If you need explicit scaling/clipping rules for your application, apply them in NDArray before writing.