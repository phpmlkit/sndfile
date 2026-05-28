# SndfileInfo

Immutable value object describing an audio file's metadata.

## Properties

| Property | Type | Description |
|----------|------|------------|
| `frames` | `int` | Total frame count |
| `channels` | `int` | Number of audio channels |
| `sampleRate` | `int` | Sample rate in Hz |
| `format` | `AudioFormat` | Container format |
| `sampleFormat` | `SampleFormat` | Encoding subtype |
| `sections` | `int` | Number of data sections |
| `seekable` | `bool` | Whether the file supports seeking |

## Factory Methods

### probe()

Open a file, read the header, and close immediately.

```php
public static function probe(string $path): self
```

**Returns:** `self` with all fields populated from the file header.

**Throws:** `SndfileException` if the file cannot be opened.

**Example:**

```php
$info = SndfileInfo::probe('song.wav');
echo "{$info->frames} frames × {$info->channels} channels";
```

### forWrite()

Create a write-ready info object.

```php
public static function forWrite(
    int $frames,
    int $channels,
    int $sampleRate,
    AudioFormat $format,
    SampleFormat $sampleFormat,
): self
```

`$seekable` is always `true` for write-mode info.

### fromSfInfo()

Create from an already-populated libsndfile `SF_INFO` struct. Internal use.

```php
public static function fromSfInfo(FFI\CData $sfInfo): self
```

## Derived Values

### duration()

Duration of the audio in seconds.

```php
public function duration(): float
```

Returns `0.0` if `sampleRate` is `0`.

### nSamples()

Total number of individual sample values.

```php
public function nSamples(): int
```

Equivalent to `$frames * $channels`.

## Builders

Each returns a new `SndfileInfo` with one field changed and all others preserved.

### withFrames()

```php
public function withFrames(int $f): self
```

### withChannels()

```php
public function withChannels(int $c): self
```

### withSampleRate()

```php
public function withSampleRate(int $sr): self
```

**Example:**

```php
$info = SndfileInfo::probe('song.wav');
$modified = $info->withSampleRate(48000)->withChannels(1);
```
