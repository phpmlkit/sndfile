<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile;

use FFI\CData;
use PhpMlKit\SoundFile\FFI\Libsndfile;

/**
 * Immutable embedded string tags from an audio file.
 *
 * All fields are nullable — tags not present in the file return null.
 * Created via sf_metadata() or SoundFile lazy accessors.
 */
final readonly class SfMetadata
{
    public function __construct(
        public ?string $title = null,
        public ?string $copyright = null,
        public ?string $software = null,
        public ?string $artist = null,
        public ?string $comment = null,
        public ?string $date = null,
        public ?string $album = null,
        public ?string $license = null,
        public ?string $trackNumber = null,
        public ?string $genre = null,
    ) {}

    /**
     * Read all string metadata from an open handle.
     *
     * @internal
     */
    public static function fromHandle(Libsndfile $lib, CData $handle): self
    {
        return new self(
            $lib->getString($handle, 0x01),  // title
            $lib->getString($handle, 0x02),  // copyright
            $lib->getString($handle, 0x03),  // software
            $lib->getString($handle, 0x04),  // artist
            $lib->getString($handle, 0x05),  // comment
            $lib->getString($handle, 0x06),  // date
            $lib->getString($handle, 0x07),  // album
            $lib->getString($handle, 0x08),  // license
            $lib->getString($handle, 0x09),  // trackNumber
            $lib->getString($handle, 0x10),  // genre
        );
    }
}
