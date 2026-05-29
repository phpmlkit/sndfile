# API Reference

Complete reference for all SoundFile classes, functions, and enums.

- [Global Functions](/api/global-functions) — `sf_read`, `sf_write`, `sf_info`, `sf_check_format`, `sf_resample`
- [SoundFile](/api/soundfile-class) — Opened audio file handle with read, write, seek, blocks, and metadata
- [SfInfo](/api/sf-info) — Immutable metadata value object
- [Enums](/api/enums) — `AudioFormat`, `SampleFormat`, `FileMode`, `ResampleQuality`
- [Exceptions](/api/exceptions) — `SoundFileException`

## Quick Reference

### Global Functions

| Function                                                    | Returns          | Description                             |
|-------------------------------------------------------------|------------------|-----------------------------------------|
| `sf_read(file, start?, stop?, always2d?, blocksize?)`      | `[NDArray, int]` | Read audio file into NDArray            |
| `sf_write(file, data, sampleRate, format?, subtype?)`      | `void`           | Write NDArray to audio file             |
| `sf_info(file)`                                            | `SfInfo`    | Read file metadata without loading data |
| `sf_check_format(format, subtype)`                         | `bool`           | Validate format/subtype compatibility   |
| `sf_resample(data, inRate, outRate, quality?, chunkSize?)` | `NDArray`        | Convert sample rate                     |

### SoundFile Instance

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

### SfInfo

| Method                       | Returns | Description              |
|------------------------------|---------|--------------------------|
| `SfInfo::probe($path)`  | `self`  | Open, read header, close |
| `SfInfo::forWrite(...)` | `self`  | Create write-ready info  |
| `duration()`                 | `float` | Duration in seconds      |
| `nSamples()`                 | `int`   | Total sample count       |
| `withFrames($n)`             | `self`  | Builder — change frames  |
