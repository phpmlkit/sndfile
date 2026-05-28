# Resampling

`snd_resample()` converts an NDArray from one sample rate to another using libsamplerate.

## Full Signature

```php
function snd_resample(
    NDArray $input,
    int $inputRate,
    int $outputRate,
    ResampleQuality $quality = ResampleQuality::Best,
    ?int $chunkSize = 2048,
): NDArray
```

## Two Modes

### Chunked progressive (default)

When `$chunkSize` is non-null, the function processes the input signal in chunks. A single output buffer is pre-allocated and
filled chunk by chunk, then wrapped in a single NDArray at the end. This is safe for large signals.

```php
$y = snd_resample($x, 44100, 16000); // default chunkSize = 2048
```

You can control chunk size (in frames):

```php
$y = snd_resample($x, 44100, 16000, chunkSize: 8192);
```


### One-shot simple

When `$chunkSize` is `null`, the function process the input signal in a single pass. This is best for small signals where the convenience outweighs the memory trade-off.

```php
$resampled = snd_resample($audio, 44100, 8000, chunkSize: null);
```

Both modes produce identical output for the same input and ratio.

## Quality Levels

Four quality levels trade CPU time for frequency response accuracy:

| Level                      | Description                        | Use Case                     |
|----------------------------|------------------------------------|------------------------------|
| `ResampleQuality::Best`    | Band-limited sinc, highest quality | Final output, mastering      |
| `ResampleQuality::Medium`  | Band-limited sinc, medium quality  | General purpose              |
| `ResampleQuality::Fastest` | Band-limited sinc, fastest         | Preview, real-time           |
| `ResampleQuality::Linear`  | Linear interpolation               | Minimum latency, low quality |

```php
use PhpMlKit\Sndfile\Enums\ResampleQuality;

$high = snd_resample($audio, 44100, 48000, quality: ResampleQuality::Best);
$fast = snd_resample($audio, 44100, 48000, quality: ResampleQuality::Fastest);
```

## DType Handling

libsamplerate operates on 32-bit float internally. The input is automatically converted to `Float32` if it has a
different dtype:

```php
// Int16 input — automatically converted to Float32
$audio = NDArray::array([[100], [200], [300]], DType::Int16);
$resampled = snd_resample($audio, 8000, 16000);
// $resampled->dtype() === DType::Float32
```

The output is always `Float32`.

## Multi-channel

Stereo and multi-channel inputs are resampled with all channels preserved:

```php
$stereo = NDArray::array([[0.1, 0.2], [0.3, 0.4], [0.5, 0.6]], DType::Float32);
$resampled = snd_resample($stereo, 8000, 16000);
// Shape: [6, 2] — both channels resampled independently
```

## Frame Count Accuracy

The output frame count is computed as `ceil(inputFrames × outputRate / inputRate)`. For exact ratios this is precise.
For non-integer ratios, the output may have one extra frame due to ceiling.
