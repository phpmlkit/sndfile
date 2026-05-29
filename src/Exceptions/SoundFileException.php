<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile\Exceptions;

/**
 * Base exception for all sndfile errors — I/O failures, invalid formats,
 * unsupported operations, and resampling errors.
 */
final class SoundFileException extends \RuntimeException {}
