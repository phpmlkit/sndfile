<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile;

use FFI\CData;
use PhpMlKit\SoundFile\Enums\AudioFormat;
use PhpMlKit\SoundFile\Enums\FileMode;
use PhpMlKit\SoundFile\Enums\SampleFormat;
use PhpMlKit\SoundFile\Exceptions\SoundFileException;
use PhpMlKit\SoundFile\FFI\Libsndfile;

/**
 * Immutable metadata describing an audio file or a write target.
 *
 * Provides frame count, channel count, sample rate, container format,
 * encoding subtype, seekability, and derived values (duration, sample count).
 */
final readonly class SfInfo
{
    public function __construct(
        public int $frames,
        public int $channels,
        public int $sampleRate,
        public AudioFormat $format,
        public SampleFormat $sampleFormat,
        public int $sections = 1,
        public bool $seekable = true,
    ) {}

    /** Duration of the audio in seconds. */
    public function duration(): float
    {
        return $this->sampleRate > 0 ? $this->frames / $this->sampleRate : 0.0;
    }

    /** Total number of individual sample values (frames × channels). */
    public function nSamples(): int
    {
        return $this->frames * $this->channels;
    }

    /**
     * Create from a libsndfile SF_INFO struct that has been populated by sf_open().
     *
     * @internal
     */
    public static function fromCData(CData $sfInfo): self
    {
        $frames = (int) $sfInfo->frames;
        $sampleRate = (int) $sfInfo->samplerate;
        $channels = (int) $sfInfo->channels;
        $sections = (int) $sfInfo->sections;
        $combinedFormat = (int) $sfInfo->format;

        $format = AudioFormat::fromSndfileFormat($combinedFormat)
            ?? throw new \RuntimeException("Unknown audio format: {$combinedFormat}");

        $sampleFormat = SampleFormat::fromSndfileFormat($combinedFormat)
            ?? throw new \RuntimeException("Unknown sample format: {$combinedFormat}");

        return new self($frames, $channels, $sampleRate, $format, $sampleFormat, $sections, 0 !== $sfInfo->seekable);
    }

    /**
     * Create a populated libsndfile SF_INFO C struct from this metadata.
     *
     * @internal
     */
    public function toCData(Libsndfile $lib): CData
    {
        $sfInfo = $lib->newInfo();
        $sfInfo->frames = $this->frames;
        $sfInfo->samplerate = $this->sampleRate;
        $sfInfo->channels = $this->channels;
        $sfInfo->format = $this->format->value | $this->sampleFormat->value;

        return $sfInfo;
    }

    /**
     * Quickly read the header of an audio file without loading its data.
     *
     * Opens the file, reads the SF_INFO struct, and closes immediately.
     *
     * @throws SoundFileException If the file cannot be opened
     */
    public static function probe(string $path): self
    {
        $lib = Libsndfile::get();
        $sfInfo = $lib->newInfo();

        $handle = $lib->open($path, FileMode::Read, $sfInfo);

        if (null === $handle) {
            throw new SoundFileException(
                "Failed to probe '{$path}': ".$lib->strError(null)
            );
        }

        $info = self::fromCData($sfInfo);
        $lib->close($handle);

        return $info;
    }

    /** Return a copy with a different frame count. */
    public function withFrames(int $f): self
    {
        return new self($f, $this->channels, $this->sampleRate, $this->format, $this->sampleFormat, $this->sections, $this->seekable);
    }

    /** Return a copy with a different channel count. */
    public function withChannels(int $c): self
    {
        return new self($this->frames, $c, $this->sampleRate, $this->format, $this->sampleFormat, $this->sections, $this->seekable);
    }

    /** Return a copy with a different sample rate. */
    public function withSampleRate(int $sr): self
    {
        return new self($this->frames, $this->channels, $sr, $this->format, $this->sampleFormat, $this->sections, $this->seekable);
    }
}
