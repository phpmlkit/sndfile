<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile;

use FFI\CData;
use PhpMlKit\SoundFile\Enums\AudioFormat;
use PhpMlKit\SoundFile\Enums\SampleFormat;
use PhpMlKit\SoundFile\FFI\Libsndfile;

/**
 * Immutable signal properties describing an audio file — frame count,
 * channel count, sample rate, container format, encoding subtype,
 * seekability, and derived values (duration, sample count).
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
