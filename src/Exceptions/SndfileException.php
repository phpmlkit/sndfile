<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile\Exceptions;

/**
 * Base exception for all sndfile errors — I/O failures, invalid formats,
 * unsupported operations, and resampling errors.
 */
final class SndfileException extends \RuntimeException {}
