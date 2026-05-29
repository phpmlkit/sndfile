<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile\FFI;

use FFI;
use FFI\CData;
use PhpMlKit\SoundFile\Enums\ResampleQuality;

/**
 * Low-level libsamplerate FFI wrapper.
 *
 * Exposes src_simple (one-shot), src_new / src_process / src_delete
 * (progressive), and error reporting. All float-based.
 *
 * This class is a singleton — use Libsamplerate::get() to obtain the shared instance.
 */
final class Libsamplerate extends NativeLibrary
{
    private static ?self $instance = null;

    private function __construct()
    {
        parent::__construct();
    }

    /** Obtain the singleton instance. */
    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * One-shot resampling via src_simple.
     *
     * @param CData $data SRC_DATA struct with input/output buffers and ratio set
     *
     * @return int 0 on success, non-zero error code on failure
     */
    public function simple(CData $data, ResampleQuality $quality, int $channels): int
    {
        return $this->ffi->src_simple(\FFI::addr($data), $quality->value, $channels);
    }

    /**
     * Create a new resampler state for progressive processing.
     *
     * @param CData $outError int CData — populated with error code if null is returned
     *
     * @return null|CData SRC_STATE handle, or null on error
     */
    public function newState(ResampleQuality $quality, int $channels, CData $outError): ?CData
    {
        $state = $this->ffi->src_new($quality->value, $channels, \FFI::addr($outError));

        return null === $state ? null : $state;
    }

    /**
     * Process one chunk through a progressive resampler.
     *
     * @return int 0 on success, non-zero error code on failure
     */
    public function process(CData $state, CData $data): int
    {
        return $this->ffi->src_process($state, \FFI::addr($data));
    }

    /** Delete a resampler state. */
    public function deleteState(CData $state): void
    {
        $this->ffi->src_delete($state);
    }

    /** Get a human-readable error message for a libsamplerate error code. */
    public function strError(int $error): string
    {
        return $this->ffi->src_strerror($error);
    }

    protected function getHeaderName(): string
    {
        return 'samplerate';
    }

    protected function getLibraryName(): string
    {
        return 'samplerate';
    }

    protected function getLibraryVersion(): string
    {
        return '0.2.2';
    }
}
