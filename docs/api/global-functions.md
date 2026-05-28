# Global Functions

All global functions live in the `PhpMlKit\Sndfile` namespace and are importable with `use function`.

## Importing

```php
use function PhpMlKit\Sndfile\snd_read;
use function PhpMlKit\Sndfile\snd_write;
use function PhpMlKit\Sndfile\{snd_read, snd_write, snd_resample};
```

---

## snd_read()

Read an audio file into a single NDArray. Opens the file, reads data in chunks via a single pre-allocated buffer, and
closes automatically.

```php
function snd_read(
    string $file,
    ?int $start = null,
    ?int $stop = null,
    bool $always2d = false,
    int $blocksize = 4096,
): array // [NDArray, int sampleRate]
```

**Parameters:**

| Parameter    | Type     | Description                                                                       |
|--------------|----------|-----------------------------------------------------------------------------------|
| `$file`      | `string` | Path to the audio file                                                            |
| `$start`     | `?int`   | First frame index to read (0-based). `null` = beginning.                          |
| `$stop`      | `?int`   | One past the last frame index. `null` = end of file. Clipped to file length.      |
| `$always2d`  | `bool`   | If `true`, mono files return `[N, 1]` instead of `[N]`. Default `false`.          |
| `$blocksize` | `int`    | Frames per internal read chunk. Affects memory usage during read, not the result. |

**Returns:** `[NDArray, int]` — the signal data (dtype matches file's native encoding) and its sample rate in Hz.

**Throws:** `SndfileException` if the file cannot be opened or a read error occurs.

**Examples:**

```php
// Read entire file
[$audio, $sr] = snd_read('song.wav');

// Read frames 100–299 (200 frames starting at frame 100)
[$slice, $sr] = snd_read('song.wav', start: 100, stop: 300);

// Read mono file as 2D
[$audio, $sr] = snd_read('song.wav', always2d: true);
// Shape: [frames, 1]
```

---

## snd_write()

Write an NDArray to an audio file. Format is inferred from the file extension when not specified. DType is
auto-converted to match the target subtype.

```php
function snd_write(
    string $file,
    NDArray $data,
    int $sampleRate,
    ?AudioFormat $format = null,
    ?SampleFormat $subtype = null,
): void
```

**Parameters:**

| Parameter     | Type            | Description                                                             |
|---------------|-----------------|-------------------------------------------------------------------------|
| `$file`       | `string`        | Output file path                                                        |
| `$data`       | `NDArray`       | Signal data, `[N]` or `[N, channels]`. 1D is auto-expanded to `[N, 1]`. |
| `$sampleRate` | `int`           | Sample rate in Hz                                                       |
| `$format`     | `?AudioFormat`  | Container format. `null` = inferred from extension.                     |
| `$subtype`    | `?SampleFormat` | Encoding subtype. `null` = format's default.                            |

**Throws:** `SndfileException` if the format/subtype combination is invalid or a write error occurs.

**Examples:**

```php
// Simple write — format and subtype auto-detected
snd_write('output.wav', $audio, sampleRate: 44100);

// Explicit format and subtype
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\SampleFormat;

snd_write('output.flac', $audio, 44100,
    format: AudioFormat::Flac,
    subtype: SampleFormat::Pcm24,
);

// 1D input works
$mono = NDArray::array([0.1, 0.2, 0.3], DType::Float32);
snd_write('out.wav', $mono, 8000); // Auto-expands to [3, 1]
```

---

## snd_info()

Read file metadata without loading audio data.

```php
function snd_info(string $file): SndfileInfo
```

**Returns:** `SndfileInfo` — all fields populated from the file header.

**Throws:** `SndfileException` if the file cannot be opened.

**Example:**

```php
$info = snd_info('song.wav');
echo "{$info->frames} frames, {$info->channels} channels, {$info->duration()}s";
```

---

## snd_check_format()

Validate that an AudioFormat and SampleFormat combination is supported by libsndfile.

```php
function snd_check_format(AudioFormat $format, SampleFormat $subtype): bool
```

**Parameters:**

| Parameter  | Type           | Description      |
|------------|----------------|------------------|
| `$format`  | `AudioFormat`  | Container format |
| `$subtype` | `SampleFormat` | Encoding subtype |

**Returns:** `bool` — `true` if the combination is valid.

**Examples:**

```php
snd_check_format(AudioFormat::Wav, SampleFormat::Pcm16);   // true
snd_check_format(AudioFormat::Ogg, SampleFormat::Pcm16);   // false
snd_check_format(AudioFormat::Flac, SampleFormat::Float);  // false
```

---

## snd_resample()

Convert an NDArray from one sample rate to another using libsamplerate.

```php
function snd_resample(
    NDArray $input,
    int $inputRate,
    int $outputRate,
    ResampleQuality $quality = ResampleQuality::Best,
    ?int $chunkSize = 2048,
): NDArray
```

**Parameters:**

| Parameter     | Type              | Description                                                                 |
|---------------|-------------------|-----------------------------------------------------------------------------|
| `$input`      | `NDArray`         | Signal of shape `[frames, channels]`                                        |
| `$inputRate`  | `int`             | Source sample rate in Hz                                                    |
| `$outputRate` | `int`             | Target sample rate in Hz                                                    |
| `$quality`    | `ResampleQuality` | Converter quality level. Default `Best`.                                    |
| `$chunkSize`  | `?int`            | Frames per processing chunk. `null` = one-shot simple mode. Default `2048`. |

**Returns:** `NDArray` of shape `[newFrames, channels]` with `DType::Float32`.

**Throws:** `SndfileException` if the resampler fails or produces zero output.

**Examples:**

```php
// Chunked progressive (default) — safe for large files
$r = snd_resample($audio, 44100, 22050);

// One-shot simple — best for small signals
$r = snd_resample($audio, 44100, 8000, chunkSize: null);

// Explicit quality and chunk size
$r = snd_resample($audio, 44100, 48000,
    quality: ResampleQuality::Fastest,
    chunkSize: 1024,
);
```
