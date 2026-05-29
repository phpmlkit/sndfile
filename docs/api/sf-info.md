# SfInfo

Immutable signal properties describing an audio file — frame count,
channel count, sample rate, container format, encoding subtype,
seekability, and derived values.

## Properties

| Property       | Type           | Description                       |
|----------------|----------------|-----------------------------------|
| `frames`       | `int`          | Total frame count                 |
| `channels`     | `int`          | Number of audio channels          |
| `sampleRate`   | `int`          | Sample rate in Hz                 |
| `format`       | `AudioFormat`  | Container format                  |
| `sampleFormat` | `SampleFormat` | Encoding subtype                  |
| `sections`     | `int`          | Number of data sections           |
| `seekable`     | `bool`         | Whether the file supports seeking |

## Factory Methods

### fromCData()

Create from an already-populated libsndfile `SF_INFO` struct. Internal use.

```php
public static function fromCData(FFI\CData $sfInfo): self
```

## Methods

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
$info = sf_info('song.wav');
$modified = $info->withSampleRate(48000)->withChannels(1);
```
