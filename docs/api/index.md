# API Reference

Complete reference for all SndFile classes, functions, and enums.

- [Global Functions](/api/global-functions) — `snd_read`, `snd_write`, `snd_info`, `snd_check_format`, `snd_resample`
- [SndFile](/api/sndfile-class) — Opened audio file handle with read, write, seek, blocks, and metadata
- [SndfileInfo](/api/sndfile-info) — Immutable metadata value object
- [Enums](/api/enums) — `AudioFormat`, `SampleFormat`, `FileMode`, `ResampleQuality`
- [Exceptions](/api/exceptions) — `SndfileException`

## Quick Reference

### Global Functions

| Function                                                    | Returns          | Description                             |
|-------------------------------------------------------------|------------------|-----------------------------------------|
| `snd_read(file, start?, stop?, always2d?, blocksize?)`      | `[NDArray, int]` | Read audio file into NDArray            |
| `snd_write(file, data, sampleRate, format?, subtype?)`      | `void`           | Write NDArray to audio file             |
| `snd_info(file)`                                            | `SndfileInfo`    | Read file metadata without loading data |
| `snd_check_format(format, subtype)`                         | `bool`           | Validate format/subtype compatibility   |
| `snd_resample(data, inRate, outRate, quality?, chunkSize?)` | `NDArray`        | Convert sample rate                     |

### SndFile Instance

| Method                           | Description                       |
|----------------------------------|-----------------------------------|
| `read(?int $n)`                  | Read frames from current position |
| `write(NDArray $data)`           | Write frames                      |
| `seek(int $offset, int $whence)` | Move position                     |
| `tell()`                         | Current position                  |
| `eof()`                          | End of file reached               |
| `blocks(int $size)`              | Generator yielding NDArrays       |
| `close()`                        | Close the handle                  |
| `info()`                         | Full file metadata                |
| `title()` / `setTitle()`         | Metadata accessors                |

### SndfileInfo

| Method                       | Returns | Description              |
|------------------------------|---------|--------------------------|
| `SndfileInfo::probe($path)`  | `self`  | Open, read header, close |
| `SndfileInfo::forWrite(...)` | `self`  | Create write-ready info  |
| `duration()`                 | `float` | Duration in seconds      |
| `nSamples()`                 | `int`   | Total sample count       |
| `withFrames($n)`             | `self`  | Builder — change frames  |
