<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile;

use FFI\CData;
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\FileMode;
use PhpMlKit\Sndfile\Enums\SampleFormat;
use PhpMlKit\Sndfile\Exceptions\SndfileException;
use PhpMlKit\Sndfile\FFI\Libsndfile;

/**
 * Immutable metadata describing an audio file or a write target.
 *
 * Provides frame count, channel count, sample rate, container format,
 * encoding subtype, seekability, and derived values (duration, sample count).
 *
 * Created via factory methods: probe() for reading file headers,
 * forWrite() when opening for output, or fromSfInfo() from an existing
 * libsndfile SF_INFO struct.
 */
final readonly class SndfileInfo
{
    private function __construct(
        public int $frames,
        public int $channels,
        public int $sampleRate,
        public AudioFormat $format,
        public SampleFormat $sampleFormat,
        public int $sections,
        public bool $seekable,
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
     * @internal used by SndFile and by probe()
     */
    public static function fromSfInfo(CData $sfInfo): self
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
     * Create a write-ready info with the given parameters.
     *
     * Frames is set to 0 (updated when writing completes). The seekable flag
     * is always true for write mode.
     */
    public static function forWrite(
        int $frames,
        int $channels,
        int $sampleRate,
        AudioFormat $format,
        SampleFormat $sampleFormat,
    ): self {
        return new self($frames, $channels, $sampleRate, $format, $sampleFormat, 1, true);
    }

    /**
     * Populate a libsndfile SF_INFO C struct in preparation for sf_open() on write.
     *
     * @internal
     */
    public function populateSfInfo(CData $sfInfo): void
    {
        $sfInfo->frames = $this->frames;
        $sfInfo->samplerate = $this->sampleRate;
        $sfInfo->channels = $this->channels;
        $sfInfo->format = $this->format->value | $this->sampleFormat->value;
    }

    /**
     * Quickly read the header of an audio file without loading its data.
     *
     * Opens the file, reads the SF_INFO struct, and closes immediately.
     *
     * @throws SndfileException If the file cannot be opened
     */
    public static function probe(string $path): self
    {
        $lib = Libsndfile::get();
        $sfInfo = $lib->newInfo();

        $handle = $lib->open($path, FileMode::Read, $sfInfo);

        if (null === $handle) {
            throw new SndfileException(
                "Failed to probe '{$path}': ".$lib->strError(null)
            );
        }

        $info = self::fromSfInfo($sfInfo);
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
