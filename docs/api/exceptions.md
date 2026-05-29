# Exceptions

## SoundFileException

The single exception class for all soundfile errors. Extends `\RuntimeException`.

```php
namespace PhpMlKit\SoundFile\Exceptions;

final class SoundFileException extends \RuntimeException {}
```

### When it is thrown

| Operation         | Condition                                                     |
|-------------------|---------------------------------------------------------------|
| `sf_read()`       | File cannot be opened, or a read error occurs                 |
| `sf_write()`      | File cannot be opened, format/subtype invalid, or write error |
| `sf_info()`       | File cannot be opened                                         |
| `new SoundFile()` | File cannot be opened, or format/subtype invalid              |
| `$sf->read()`     | Handle is closed, or read error                               |
| `$sf->write()`    | Handle is closed, channel mismatch, or write error            |
| `$sf->seek()`     | Handle is closed, file is not seekable, or seek fails         |
| `sf_resample()`   | Resampler fails or produces zero output                       |

### Handling

```php
use PhpMlKit\SoundFile\Exceptions\SoundFileException;

try {
    [$audio, $info] = sf_read('song.wav');
} catch (SoundFileException $e) {
    echo "Failed to read: " . $e->getMessage();
}
```

The message includes the underlying libsndfile or libsamplerate error string when available.
