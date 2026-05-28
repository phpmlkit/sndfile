# Exceptions

## SndfileException

The single exception class for all sndfile errors. Extends `\RuntimeException`.

```php
namespace PhpMlKit\Sndfile\Exceptions;

final class SndfileException extends \RuntimeException {}
```

### When it is thrown

| Operation | Condition |
|-----------|-----------|
| `snd_read()` | File cannot be opened, or a read error occurs |
| `snd_write()` | File cannot be opened, format/subtype invalid, or write error |
| `snd_info()` | File cannot be opened |
| `new SndFile()` | File cannot be opened, or format/subtype invalid |
| `$sf->read()` | Handle is closed, or read error |
| `$sf->write()` | Handle is closed, channel mismatch, or write error |
| `$sf->seek()` | Handle is closed, file is not seekable, or seek fails |
| `snd_resample()` | Resampler fails or produces zero output |

### Handling

```php
use PhpMlKit\Sndfile\Exceptions\SndfileException;

try {
    [$audio, $sr] = snd_read('song.wav');
} catch (SndfileException $e) {
    echo "Failed to read: " . $e->getMessage();
}
```

The message includes the underlying libsndfile or libsamplerate error string when available.
