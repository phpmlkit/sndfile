<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile\Enums;

/**
 * File open mode constants matching libsndfile's SFM_READ / SFM_WRITE / SFM_RDWR.
 */
enum FileMode: int
{
    /** Open for reading only. */
    case Read = 0x10;

    /** Open for writing (creates or truncates). */
    case Write = 0x20;

    /** Open for both reading and writing. */
    case ReadWrite = 0x30;
}
