---
layout: home

hero:
  name: "PHP SndFile"
  tagline: Low-level audio I/O and resampling for PHP
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started/what-is-sndfile
    - theme: alt
      text: View on GitHub
      link: https://github.com/phpmlkit/sndfile

features:
  - icon: 🎵
    title: Read & Write Audio
    details: WAV, FLAC, Ogg Vorbis, MP3, AIFF, and 20+ formats. Data flows in and out as NDArrays — no intermediate buffers.
  - icon: 🔄
    title: Sample Rate Conversion
    details: Four quality levels, chunked progressive mode for large files, one-shot simple mode for small signals.
  - icon: 🧵
    title: Streaming I/O
    details: Instance-based SndFile class with read, write, seek, tell, block iteration, and metadata tags.
  - icon: 🏷️
    title: Full Metadata Support
    details: Read and write title, artist, album, track number, genre, and arbitrary tags on open handles.
  - icon: 🔧
    title: Battle-Tested Foundation
    details: Powered by libsndfile and libsamplerate — the standard C libraries for audio I/O and sample rate conversion.
  - icon: 🎯
    title: PHP 8.2+ Native
    details: Backed enums, readonly value objects, strict types, and importable global functions.
---

## Quick Example

```php
use function PhpMlKit\Sndfile\{snd_read, snd_write, snd_resample};

// Read an audio file — returns [NDArray, sampleRate]
[$audio, $sr] = snd_read('input.wav');
// $audio shape: [441000] (mono, Float32), $sr: 44100

// Resample from 44.1kHz to 16kHz
$audio16k = snd_resample($audio, $sr, 16000);

// Write it back
snd_write('output.wav', $audio16k, 22050);
```

## Streaming with SndFile

```php
use PhpMlKit\Sndfile\SndFile;
use PhpMlKit\Sndfile\Enums\FileMode;

// Open for reading
$sf = new SndFile('input.wav', FileMode::Read);

// Seek and read
$sf->seek(44100);
$chunk = $sf->read(512);

// Iterate in blocks — each block is an NDArray
foreach ($sf->blocks(1024) as $block) {
    process($block);
}
$sf->close();

// Open for writing
$out = new SndFile('output.wav', FileMode::Write,
    sampleRate: 44100, channels: 2,
);
$out->setTitle('My Track');
$out->write($stereoData);
$out->close();
```

## Next steps

- [What is SndFile?](/guide/getting-started/what-is-sndfile)
- [Installation](/guide/getting-started/installation)
- [API Reference](/api/)