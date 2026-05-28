<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile;

use FFI\CData;
use PhpMlKit\NDArray\DType;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\FileMode;
use PhpMlKit\Sndfile\Enums\SampleFormat;
use PhpMlKit\Sndfile\Exceptions\SndfileException;
use PhpMlKit\Sndfile\FFI\Libsndfile;

/**
 * An opened audio file — streaming read/write with seeking and block iteration.
 *
 * Read mode:
 *   $sf = new SndFile('audio.wav', FileMode::Read);
 *   $chunk = $sf->read(512);
 *   foreach ($sf->blocks(1024) as $block) { ... }
 *
 * Write mode:
 *   $sf = new SndFile('out.wav', FileMode::Write,
 *       sampleRate: 44100, channels: 2,
 *       format: AudioFormat::Wav, subtype: SampleFormat::Float);
 *   $sf->write($data);
 *   $sf->close();
 *
 * ReadWrite mode:
 *   $sf = new SndFile('file.wav', FileMode::ReadWrite,
 *       sampleRate: 44100, channels: 2,
 *       format: AudioFormat::Wav, subtype: SampleFormat::Float);
 *   $sf->write($data);
 *   $sf->seek(0);
 *   $sf->read(100);
 *
 * For one-shot read/write without managing the handle, use the convenience
 * functions snd_read() and snd_write() instead.
 *
 * @see snd_read()
 * @see snd_write()
 */
final class SndFile
{
    private ?CData $handle = null;
    private SndfileInfo $info;
    private int $position = 0;
    private readonly Libsndfile $lib;
    private readonly FileMode $mode;

    /**
     * Open a sound file.
     *
     * In read mode, metadata is read from the file header. In write or
     * read-write mode, sampleRate, channels, format, and subtype must be
     * provided.
     *
     * @param string            $path       Path to the audio file
     * @param FileMode          $mode       Read (default), Write, or ReadWrite
     * @param null|int          $sampleRate Sample rate in Hz (write / read-write mode)
     * @param null|int          $channels   Number of channels (write / read-write mode; defaults to 1)
     * @param null|AudioFormat  $format     Container format (write / read-write mode; default: inferred from extension)
     * @param null|SampleFormat $subtype    Encoding subtype (write / read-write mode; default: format's preferred)
     *
     * @throws SndfileException If the file cannot be opened or the format is invalid
     */
    public function __construct(
        string $path,
        FileMode $mode = FileMode::Read,
        ?int $sampleRate = null,
        ?int $channels = null,
        ?AudioFormat $format = null,
        ?SampleFormat $subtype = null,
    ) {
        $this->mode = $mode;
        $this->lib = Libsndfile::get();
        $sfInfo = $this->lib->newInfo();

        if (FileMode::Read === $mode || FileMode::ReadWrite === $mode) {
            $handle = $this->lib->open($path, $mode, $sfInfo);

            if (null === $handle) {
                throw new SndfileException(
                    "Failed to open '{$path}': ".$this->lib->strError(null)
                );
            }

            $this->handle = $handle;
            $this->info = SndfileInfo::fromSfInfo($sfInfo);

            return;
        }

        // Write mode
        $format ??= AudioFormat::fromPath($path)
            ?? throw new SndfileException("Cannot determine format for '{$path}'");

        $subtype ??= $format->defaultSampleFormat();

        if (!snd_check_format($format, $subtype)) {
            throw new SndfileException(
                "Incompatible format/subtype: {$format->name} + {$subtype->name}"
            );
        }

        $writeInfo = SndfileInfo::forWrite(0, $channels ?? 1, $sampleRate ?? 44100, $format, $subtype);
        $writeInfo->populateSfInfo($sfInfo);

        $handle = $this->lib->open($path, $mode, $sfInfo);

        if (null === $handle) {
            throw new SndfileException(
                "Failed to open '{$path}' for writing: ".$this->lib->strError(null)
            );
        }

        $this->handle = $handle;
        $this->info = $writeInfo;
    }

    public function __destruct()
    {
        $this->close();
    }

    // ===================================================================
    // I/O
    // ===================================================================

    /**
     * Read up to $numFrames frames from the current position.
     *
     * The returned NDArray has shape [framesRead × channels] in the file's
     * native dtype. Each call advances the position; subsequent reads
     * continue from where the previous one left off.
     *
     * Pass null to read all remaining frames from the current position.
     *
     * @param null|int $numFrames Maximum frames to read (null = remaining)
     *
     * @throws SndfileException If the file is closed, opened in write-only
     *                          mode, or a read error occurs
     */
    public function read(?int $numFrames = null): NDArray
    {
        if (null === $this->handle) {
            throw new SndfileException('Cannot read from a closed file');
        }

        if (FileMode::Write === $this->mode) {
            throw new SndfileException('Cannot read from a write-only file');
        }

        $numFrames ??= $this->remaining();
        $total = $numFrames * $this->info->channels;
        $dtype = $this->info->sampleFormat->toDtype();

        [$cType, $readFn] = $this->lib->readFn($dtype);
        $buffer = $this->lib->new("{$cType}[{$total}]");

        $read = $readFn($this->lib, $this->handle, $buffer, $numFrames);

        if ($read < 0) {
            throw new SndfileException('Read error: '.$this->lib->strError($this->handle));
        }

        $this->position += $read;

        return NDArray::fromBuffer($buffer, [$read, $this->info->channels], $dtype);
    }

    /**
     * Write frames to the file.
     *
     * Can be called multiple times — each call appends the data and advances
     * the position. Only the channel count must match the file's channel count;
     * the frame count in $data can be any size.
     *
     * The dtype is automatically converted to match the file's subtype.
     * After writing, $sf->tell() reflects the total frames written so far.
     *
     * @param NDArray $data Data of shape [N, channels] where channels matches
     *                      the file's channel count
     *
     * @throws SndfileException If the handle is closed, opened in read-only
     *                          mode, channels mismatch, or a write error occurs
     */
    public function write(NDArray $data): void
    {
        if (null === $this->handle) {
            throw new SndfileException('Cannot write to a closed file');
        }

        if (FileMode::Read === $this->mode) {
            throw new SndfileException('Cannot write to a read-only file');
        }

        $shape = $data->shape();
        $frames = $shape[0];
        $channels = $shape[1] ?? 1;

        if ($channels !== $this->info->channels) {
            throw new SndfileException(
                "Channel mismatch: expected {$this->info->channels}, got {$channels}"
            );
        }

        $dtype = $this->info->sampleFormat->toDtype();
        $dataOut = $dtype === $data->dtype() ? $data : $data->astype($dtype);

        $total = $frames * $channels;
        [$cType, $writeFn] = $this->lib->writeFn($dtype);
        $buffer = $this->lib->new("{$cType}[{$total}]");
        $dataOut->toBuffer($buffer);
        $written = $writeFn($this->lib, $this->handle, $buffer, $frames);

        if ($written !== $frames) {
            throw new SndfileException(
                "Write error: wrote {$written}/{$frames} frames: ".$this->lib->strError($this->handle)
            );
        }

        $this->position += $written;
        $this->info = $this->info->withFrames($this->position);
    }

    // ===================================================================
    // Block iteration
    // ===================================================================

    /**
     * Iterate over the file in blocks of up to $blocksize frames.
     *
     * Each yielded value is an NDArray of shape [framesRead × channels]
     * (the final block may be smaller). The generator stops when EOF is
     * reached.
     *
     * Only available for handles opened in read or read-write mode.
     *
     * @param int $blocksize Maximum frames per block
     *
     * @return \Generator<int, NDArray>
     */
    public function blocks(int $blocksize = 4096): \Generator
    {
        while (!$this->eof()) {
            $chunk = $this->read($blocksize);

            if (0 === $chunk->shape()[0]) {
                break;
            }

            yield $chunk;
        }
    }

    // ===================================================================
    // Position
    // ===================================================================

    /**
     * Move the read/write position to a frame offset.
     *
     * @param int $frameOffset Target frame position
     * @param int $whence      SEEK_SET (0), SEEK_CUR (1), or SEEK_END (2)
     *
     * @throws SndfileException If the file is closed, not seekable, or the seek fails
     */
    public function seek(int $frameOffset, int $whence = \SEEK_SET): void
    {
        if (!$this->info->seekable) {
            throw new SndfileException('This file does not support seeking');
        }

        if (null === $this->handle) {
            throw new SndfileException('Cannot seek a closed file');
        }

        $result = $this->lib->seek($this->handle, $frameOffset, $whence);

        if ($result < 0) {
            throw new SndfileException('Seek error: '.$this->lib->strError($this->handle));
        }

        $this->position = $result;
    }

    /**
     * Current frame position.
     *
     * Updated by read, write, and seek. Starts at 0 on open and resets
     * to 0 on close.
     */
    public function tell(): int
    {
        return $this->position;
    }

    /**
     * Whether the read position has reached or passed the end of the file.
     *
     * In read mode, this is true when the position reaches the file's
     * total frame count. In write mode, this always returns false —
     * writing appends indefinitely, so there is no end to reach.
     */
    public function eof(): bool
    {
        if (null === $this->handle) {
            return true;
        }

        if (FileMode::Write === $this->mode) {
            return false;
        }

        return $this->position >= $this->info->frames;
    }

    /**
     * Close the file handle.
     *
     * After closing, all I/O methods throw. Called automatically by the
     * destructor.
     */
    public function close(): void
    {
        if (null !== $this->handle) {
            $this->lib->close($this->handle);
            $this->handle = null;
        }

        $this->position = 0;
    }

    // ===================================================================
    // Metadata
    // ===================================================================

    /**
     * Full file metadata (frames, channels, sample rate, format, etc.).
     *
     * In read mode, the frame count is the file's total frames. In write
     * or read-write mode, it reflects the total frames written so far.
     */
    public function info(): SndfileInfo
    {
        return $this->info;
    }

    /** Total frames in the file, or frames written so far in write mode. */
    public function frames(): int
    {
        return $this->info->frames;
    }

    /** Number of audio channels. */
    public function channels(): int
    {
        return $this->info->channels;
    }

    /** Sample rate in Hz. */
    public function sampleRate(): int
    {
        return $this->info->sampleRate;
    }

    /** The FileMode this handle was opened with. */
    public function mode(): FileMode
    {
        return $this->mode;
    }

    // ===================================================================
    // String metadata (SF_STR_*)
    // ===================================================================

    /** Title tag. */
    public function title(): ?string
    {
        return $this->getString(0x01);
    }

    /** Copyright tag. */
    public function copyright(): ?string
    {
        return $this->getString(0x02);
    }

    /** Encoder software tag. */
    public function software(): ?string
    {
        return $this->getString(0x03);
    }

    /** Artist tag. */
    public function artist(): ?string
    {
        return $this->getString(0x04);
    }

    /** Comment tag. */
    public function comment(): ?string
    {
        return $this->getString(0x05);
    }

    /** Date tag. */
    public function date(): ?string
    {
        return $this->getString(0x06);
    }

    /** Album tag. */
    public function album(): ?string
    {
        return $this->getString(0x07);
    }

    /** License tag. */
    public function license(): ?string
    {
        return $this->getString(0x08);
    }

    /** Track number tag. */
    public function trackNumber(): ?string
    {
        return $this->getString(0x09);
    }

    /** Genre tag. */
    public function genre(): ?string
    {
        return $this->getString(0x10);
    }

    /**
     * Read an arbitrary string metadata field.
     *
     * @param int $strType SF_STR_* constant (e.g. 0x01 = Title, 0x04 = Artist)
     */
    public function getString(int $strType): ?string
    {
        return null !== $this->handle ? $this->lib->getString($this->handle, $strType) : null;
    }

    /**
     * Write an arbitrary string metadata field.
     *
     * @param int $strType SF_STR_* constant
     *
     * @throws SndfileException If the file is closed
     */
    public function setString(int $strType, string $value): void
    {
        if (null === $this->handle) {
            throw new SndfileException('Cannot set metadata on a closed file');
        }

        $this->lib->setString($this->handle, $strType, $value);
    }

    /** @see setString() */
    public function setTitle(string $v): void
    {
        $this->setString(0x01, $v);
    }

    /** @see setString() */
    public function setCopyright(string $v): void
    {
        $this->setString(0x02, $v);
    }

    /** @see setString() */
    public function setSoftware(string $v): void
    {
        $this->setString(0x03, $v);
    }

    /** @see setString() */
    public function setArtist(string $v): void
    {
        $this->setString(0x04, $v);
    }

    /** @see setString() */
    public function setComment(string $v): void
    {
        $this->setString(0x05, $v);
    }

    /** @see setString() */
    public function setDate(string $v): void
    {
        $this->setString(0x06, $v);
    }

    /** @see setString() */
    public function setAlbum(string $v): void
    {
        $this->setString(0x07, $v);
    }

    /** @see setString() */
    public function setLicense(string $v): void
    {
        $this->setString(0x08, $v);
    }

    /** @see setString() */
    public function setTrackNumber(string $v): void
    {
        $this->setString(0x09, $v);
    }

    /** @see setString() */
    public function setGenre(string $v): void
    {
        $this->setString(0x10, $v);
    }

    /** Frames remaining from the current position to EOF (read mode only). */
    private function remaining(): int
    {
        return max(0, $this->info->frames - $this->position);
    }
}
