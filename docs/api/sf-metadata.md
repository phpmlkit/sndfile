# SfMetadata

Immutable embedded string tags from an audio file.

## Properties

| Property | Type | Description |
|----------|------|------------|
| `title` | `?string` | Track title |
| `copyright` | `?string` | Copyright notice |
| `software` | `?string` | Encoder software |
| `artist` | `?string` | Artist name |
| `comment` | `?string` | Free-text comment |
| `date` | `?string` | Creation date |
| `album` | `?string` | Album title |
| `license` | `?string` | License string |
| `trackNumber` | `?string` | Track number |
| `genre` | `?string` | Genre name |

## Creating an Instance

### sf_metadata()

Read all embedded tags from an audio file.

```php
use function PhpMlKit\SoundFile\sf_metadata;

$meta = sf_metadata('song.wav');
echo $meta->artist;
echo $meta->album;
```

Opens the file, reads all tags, and closes immediately. Each tag is `null` if not present in the file.
