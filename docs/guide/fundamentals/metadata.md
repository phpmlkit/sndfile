# Metadata

SndFile supports reading and writing string metadata tags on open handles. The tags follow libsndfile's `SF_STR_*`
constants and are available on WAV, FLAC, AIFF, and other formats that support embedded metadata.

## Reading Metadata

Call the named getter methods on a `SndFile` instance opened in read mode:

```php
$sf = new SndFile('song.wav', FileMode::Read);

$title  = $sf->title();       // ?string
$artist = $sf->artist();      // ?string
$album  = $sf->album();       // ?string
$track  = $sf->trackNumber(); // ?string
$genre  = $sf->genre();       // ?string

$sf->close();
```

All getters return `null` if the tag is not present in the file.

## Writing Metadata

Call the setter methods on a handle opened in write mode, then write your audio data:

```php
$sf = new SndFile('output.wav', FileMode::Write,
    sampleRate: 44100, channels: 2,
);

// Set metadata before writing
$sf->setTitle('My Song');
$sf->setArtist('My Band');
$sf->setAlbum('My Album');
$sf->setTrackNumber('3');

// Write the audio
$sf->write($audioData);
$sf->close();

// Re-open to verify
$sf2 = new SndFile('output.wav', FileMode::Read);
echo $sf2->title(); // 'My Song'
$sf2->close();
```

::: tip
Set metadata before writing the audio data. Some formats finalize the header on close, and metadata set after writing
may not be persisted correctly.
:::

## Available Tags

| Getter          | Setter             | Constant | Description       |
|-----------------|--------------------|----------|-------------------|
| `title()`       | `setTitle()`       | `0x01`   | Track title       |
| `copyright()`   | `setCopyright()`   | `0x02`   | Copyright notice  |
| `software()`    | `setSoftware()`    | `0x03`   | Encoder software  |
| `artist()`      | `setArtist()`      | `0x04`   | Artist name       |
| `comment()`     | `setComment()`     | `0x05`   | Free-text comment |
| `date()`        | `setDate()`        | `0x06`   | Creation date     |
| `album()`       | `setAlbum()`       | `0x07`   | Album title       |
| `license()`     | `setLicense()`     | `0x08`   | License string    |
| `trackNumber()` | `setTrackNumber()` | `0x09`   | Track number      |
| `genre()`       | `setGenre()`       | `0x10`   | Genre name        |

## Arbitrary Tags

For SF_STR constants not covered by the named methods, use `getString()` and `setString()`:

```php
$sf->setString(0x04, 'Custom Artist'); // Equivalent to setArtist()
$value = $sf->getString(0x04);         // Equivalent to artist()
```

## Notes

- Not every container supports every metadata field.
- `setString()` will throw `SndfileException` if called on a closed handle.
- Attempting to call a setter on a read-mode handle throws
  a `SndfileException`
