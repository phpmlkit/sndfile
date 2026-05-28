<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile\Enums;

/**
 * libsamplerate converter quality levels.
 *
 * Higher quality = better frequency response but slower.
 * SRC_SINC_BEST_QUALITY through SRC_LINEAR.
 */
enum ResampleQuality: int
{
    /** Band-limited sinc interpolation, highest quality, slowest. */
    case Best = 0;

    /** Band-limited sinc interpolation, medium quality. */
    case Medium = 1;

    /** Band-limited sinc interpolation, fastest. */
    case Fastest = 2;

    /** Linear interpolation, fastest but lowest quality. */
    case Linear = 3;
}
