<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile;

use PhpMlKit\NDArray\DType;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\FileMode;
use PhpMlKit\Sndfile\Enums\ResampleQuality;
use PhpMlKit\Sndfile\Enums\SampleFormat;
use PhpMlKit\Sndfile\Exceptions\SndfileException;
use PhpMlKit\Sndfile\FFI\Libsamplerate;
use PhpMlKit\Sndfile\FFI\Libsndfile;

/**
 * Read an audio file into a single NDArray.
 *
 * Opens the file, allocates one output buffer, reads data in chunks, and
 * returns the complete signal as an NDArray along with its sample rate.
 *
 * The dtype matches the file's native format. For mono files, the default
 * output is a 1D array of shape [frames]; set $always2d to true for the
 * canonical [frames, 1] shape.
 *
 * @param string   $file      Path to the audio file
 * @param null|int $start     First frame index to read (0-based; null = beginning)
 * @param null|int $stop      One past the last frame index (null = end of file)
 * @param bool     $always2d  If true, mono files return [frames, 1] instead of [frames]
 * @param int      $blocksize Frames per internal read chunk (affects memory usage, not results)
 *
 * @return array{NDArray, int} [signal data, sample rate in Hz]
 *
 * @throws SndfileException If the file cannot be opened or a read error occurs
 */
function snd_read(
    string $file,
    ?int $start = null,
    ?int $stop = null,
    bool $always2d = false,
    int $blocksize = 4096,
): array {
    $lib = Libsndfile::get();
    $sfInfo = $lib->newInfo();
    $handle = $lib->open($file, FileMode::Read, $sfInfo);

    if (null === $handle) {
        throw new SndfileException("Failed to open '{$file}': ".$lib->strError(null));
    }

    try {
        $info = SndfileInfo::fromSfInfo($sfInfo);
        $sampleRate = $info->sampleRate;
        $channels = $info->channels;
        $totalFileFrames = $info->frames;
        $dtype = $info->sampleFormat->toDtype();

        $start ??= 0;
        $stop ??= $totalFileFrames;
        $stop = min($stop, $totalFileFrames);
        $totalFrames = max(0, $stop - $start);

        if ($totalFrames <= 0) {
            return [NDArray::zeros([0, $channels], $dtype), $sampleRate];
        }

        if ($start > 0) {
            $lib->seek($handle, $start, \SEEK_SET);
        }

        $totalSamples = $totalFrames * $channels;
        [$cType, $readFn] = $lib->readFn($dtype);
        $buffer = $lib->new("{$cType}[{$totalSamples}]");

        $offset = 0;
        $remaining = $totalFrames;

        while ($remaining > 0) {
            $toRead = min($blocksize, $remaining);
            $read = $readFn($lib, $handle, \FFI::addr($buffer[$offset]), $toRead);

            if ($read <= 0) {
                break;
            }

            $offset += $read * $channels;
            $remaining -= $read;
        }

        $data = NDArray::fromBuffer($buffer, [$totalFrames, $channels], $dtype);

        if (!$always2d && 1 === $channels) {
            $data = $data->squeeze();
        }
    } finally {
        $lib->close($handle);
    }

    return [$data, $sampleRate];
}

/**
 * Write an NDArray to an audio file.
 *
 * The array must be 1D [frames] for mono or 2D [frames, channels] for
 * multi-channel. The dtype is automatically converted to match the target
 * file subtype. The output format is inferred from the file extension
 * when not explicitly provided.
 *
 * @param string            $file       Output file path
 * @param NDArray           $data       Signal data, [frames] or [frames, channels]
 * @param int               $sampleRate Sample rate in Hz
 * @param null|AudioFormat  $format     Output container format (default: inferred from extension)
 * @param null|SampleFormat $subtype    Output encoding subtype (default: format's preferred)
 *
 * @throws SndfileException If the format is invalid, the file cannot be opened, or a write error occurs
 */
function snd_write(
    string $file,
    NDArray $data,
    int $sampleRate,
    ?AudioFormat $format = null,
    ?SampleFormat $subtype = null,
): void {
    if (1 === $data->ndim()) {
        $data = $data->insertaxis(1);
    }

    $shape = $data->shape();
    $frames = $shape[0];
    $channels = $shape[1] ?? 1;

    $format ??= AudioFormat::fromPath($file)
        ?? throw new SndfileException("Cannot determine format for '{$file}'");

    $subtype ??= $format->defaultSampleFormat();

    if (!snd_check_format($format, $subtype)) {
        throw new SndfileException(
            "Incompatible format/subtype: {$format->name} + {$subtype->name}"
        );
    }

    $dtype = $subtype->toDtype();
    $dataOut = $dtype === $data->dtype() ? $data : $data->astype($dtype);

    $lib = Libsndfile::get();
    $sfInfo = $lib->newInfo();
    $writeInfo = SndfileInfo::forWrite($frames, $channels, $sampleRate, $format, $subtype);
    $writeInfo->populateSfInfo($sfInfo);

    $handle = $lib->open($file, FileMode::Write, $sfInfo);

    if (null === $handle) {
        throw new SndfileException("Failed to open '{$file}' for writing: ".$lib->strError(null));
    }

    try {
        $total = $frames * $channels;
        [$cType, $writeFn] = $lib->writeFn($dtype);
        $buffer = $lib->new("{$cType}[{$total}]");
        $dataOut->toBuffer($buffer);

        $written = $writeFn($lib, $handle, $buffer, $frames);

        if ($written !== $frames) {
            throw new SndfileException(
                "Write error: wrote {$written}/{$frames} frames: ".$lib->strError($handle)
            );
        }
    } finally {
        $lib->close($handle);
    }
}

/**
 * Quickly probe an audio file's metadata without loading its data.
 *
 * Opens the file, reads the header, and closes immediately.
 *
 * @throws SndfileException If the file cannot be opened
 */
function snd_info(string $file): SndfileInfo
{
    return SndfileInfo::probe($file);
}

/**
 * Validate whether a format + subtype combination is supported by libsndfile.
 *
 * @return bool True if the combination can be written
 */
function snd_check_format(AudioFormat $format, SampleFormat $subtype): bool
{
    $lib = Libsndfile::get();
    $info = $lib->newInfo();
    $info->format = $format->value | $subtype->value;
    $info->channels = 1;
    $info->samplerate = 44100;

    return $lib->formatCheck($info);
}

/**
 * Resample an NDArray to a new sample rate.
 *
 * When $chunkSize is non-null (default 2048), uses src_process in a loop for
 * safe memory usage on large signals. The output is written to a single
 * pre-allocated buffer and wrapped in one NDArray at the end.
 *
 * When $chunkSize is null, uses src_simple for a single-pass conversion.
 * Best for small signals where the convenience outweighs the memory trade-off.
 *
 * Both paths use 32-bit float internally.
 *
 * @param NDArray         $input      Signal of shape [frames, channels]
 * @param int             $inputRate  Source sample rate in Hz
 * @param int             $outputRate Target sample rate in Hz
 * @param ResampleQuality $quality    Converter quality level
 * @param null|int        $chunkSize  Frames per processing chunk (null = one-shot)
 *
 * @return NDArray Resampled signal of shape [newFrames, channels]
 *
 * @throws SndfileException If the resampler fails or produces zero output
 */
function snd_resample(
    NDArray $input,
    int $inputRate,
    int $outputRate,
    ResampleQuality $quality = ResampleQuality::Best,
    ?int $chunkSize = 2048,
): NDArray {
    if ($inputRate === $outputRate) {
        return $input;
    }

    $lib = Libsamplerate::get();

    $shape = $input->shape();
    $frames = $shape[0];
    $channels = $shape[1] ?? 1;

    $f32 = DType::Float32 === $input->dtype() ? $input : $input->astype(DType::Float32);

    $ratio = $outputRate / $inputRate;

    if (null === $chunkSize) {
        $outFrames = (int) ceil($frames * $ratio);
        $outBuf = $lib->new('float['.($outFrames * $channels).']');

        $data = $lib->new('SRC_DATA');
        $data->data_in = \FFI::addr($f32->toBuffer()[0]);
        $data->data_out = \FFI::addr($outBuf[0]);
        $data->input_frames = $frames;
        $data->output_frames = $outFrames;
        $data->src_ratio = $ratio;

        $err = $lib->simple($data, $quality, $channels);

        if (0 !== $err) {
            throw new SndfileException($lib->strError($err));
        }

        $actualOut = (int) $data->output_frames_gen;

        return NDArray::fromBuffer($outBuf, [$actualOut, $channels], DType::Float32);
    }

    // Chunked progressive
    $outError = $lib->new('int');
    $state = $lib->newState($quality, $channels, $outError);

    if (null === $state) {
        \assert(\is_int($outError->cdata), 'Unexpected error code type from libsamplerate');

        throw new SndfileException($lib->strError($outError->cdata));
    }

    $inputBuffer = $f32->toBuffer();

    $tailPad = 4096;
    $chunkPad = 32;

    $maxOutputFrames = (int) ceil(($frames + $tailPad) * $ratio);
    $maxOutSamples = $maxOutputFrames * $channels;

    $outputBuffer = $lib->new("float[{$maxOutSamples}]");
    $maxOutFramesPerChunk = (int) ceil($chunkSize * $ratio) + $chunkPad;

    $data = $lib->new('SRC_DATA');
    $data->src_ratio = $ratio;

    $inputOffset = 0;
    $outputOffset = 0;
    $remainingInputFrames = $frames;
    $done = false;

    while (!$done) {
        $data->input_frames = $remainingInputFrames > 0
            ? min($chunkSize, $remainingInputFrames)
            : 0;

        $data->data_in = $remainingInputFrames > 0
            ? \FFI::addr($inputBuffer[$inputOffset])
            : null;

        $data->output_frames = $maxOutFramesPerChunk;
        $data->data_out = \FFI::addr($outputBuffer[$outputOffset]);
        $data->end_of_input = ($remainingInputFrames <= $chunkSize) ? 1 : 0;

        $err = $lib->process($state, $data);

        if (0 !== $err) {
            $lib->deleteState($state);

            throw new SndfileException($lib->strError($err));
        }

        $inputFramesUsed = (int) $data->input_frames_used;
        $outputFramesGen = (int) $data->output_frames_gen;

        $inputOffset += $inputFramesUsed * $channels;
        $outputOffset += $outputFramesGen * $channels;
        $remainingInputFrames -= $inputFramesUsed;

        if ($data->end_of_input && 0 === $outputFramesGen) {
            $done = true;
        }
    }

    $lib->deleteState($state);

    $totalOutputFrames = (int) ($outputOffset / $channels);

    if (0 === $totalOutputFrames) {
        throw new SndfileException('Resampling produced zero frames');
    }

    return NDArray::fromBuffer($outputBuffer, [$totalOutputFrames, $channels], DType::Float32);
}
