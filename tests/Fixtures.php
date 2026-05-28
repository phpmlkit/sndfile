<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile\Tests;

use PhpMlKit\NDArray\DType;
use PhpMlKit\NDArray\NDArray;
use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\FileMode;
use PhpMlKit\Sndfile\Enums\SampleFormat;
use PhpMlKit\Sndfile\SndFile;

/**
 * Generates and caches small test audio files, cleaning up on shutdown.
 */
final class Fixtures
{
    private static bool $initialized = false;

    private static string $monoFloatWav;
    private static string $stereoFloatWav;
    private static string $monoPcm16Wav;
    private static string $monoPcm32Wav;
    private static string $monoDoubleWav;
    private static string $silenceWav;
    private static string $shortWav;
    private static string $metadataWav;

    public static function monoFloatWav(): string
    {
        self::init();

        return self::$monoFloatWav;
    }

    public static function stereoFloatWav(): string
    {
        self::init();

        return self::$stereoFloatWav;
    }

    public static function monoPcm16Wav(): string
    {
        self::init();

        return self::$monoPcm16Wav;
    }

    public static function monoPcm32Wav(): string
    {
        self::init();

        return self::$monoPcm32Wav;
    }

    public static function monoDoubleWav(): string
    {
        self::init();

        return self::$monoDoubleWav;
    }

    public static function silenceWav(): string
    {
        self::init();

        return self::$silenceWav;
    }

    public static function shortWav(): string
    {
        self::init();

        return self::$shortWav;
    }

    public static function metadataWav(): string
    {
        self::init();

        return self::$metadataWav;
    }

    private static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        $dir = sys_get_temp_dir().'/sndfile_tests';
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        $sr = 8000;

        self::$monoFloatWav = "{$dir}/mono_float.wav";
        self::generateSine(self::$monoFloatWav, $sr, 440.0, 0.1, 1, SampleFormat::Float);

        self::$stereoFloatWav = "{$dir}/stereo_float.wav";
        self::generateSine(self::$stereoFloatWav, $sr, 440.0, 0.1, 2, SampleFormat::Float);

        self::$monoPcm16Wav = "{$dir}/mono_pcm16.wav";
        self::generateSine(self::$monoPcm16Wav, $sr, 440.0, 0.1, 1, SampleFormat::Pcm16);

        self::$monoPcm32Wav = "{$dir}/mono_pcm32.wav";
        self::generateSine(self::$monoPcm32Wav, $sr, 440.0, 0.1, 1, SampleFormat::Pcm32);

        self::$monoDoubleWav = "{$dir}/mono_double.wav";
        self::generateSine(self::$monoDoubleWav, $sr, 440.0, 0.1, 1, SampleFormat::Double);

        self::$silenceWav = "{$dir}/silence.wav";
        $silence = NDArray::zeros([100, 1], DType::Float32);
        self::writeWav(self::$silenceWav, $silence, $sr, SampleFormat::Float);

        self::$shortWav = "{$dir}/short.wav";
        $short = NDArray::array([[0.1], [0.2], [0.3]], DType::Float32);
        self::writeWav(self::$shortWav, $short, $sr, SampleFormat::Float);

        self::$metadataWav = "{$dir}/metadata.wav";
        $sf = new SndFile(
            self::$metadataWav,
            FileMode::Write,
            sampleRate: $sr,
            channels: 1,
            format: AudioFormat::Wav,
            subtype: SampleFormat::Float,
        );
        $sf->setTitle('Test Title');
        $sf->setArtist('Test Artist');
        $sf->write(NDArray::array([[0.5], [-0.5]], DType::Float32));
        $sf->close();
    }

    private static function generateSine(string $path, int $sr, float $freq, float $duration, int $channels, SampleFormat $subtype): void
    {
        $frames = (int) ($sr * $duration);
        $t = NDArray::arange(0, $frames, 1, DType::Float32);
        $mono = $t->multiply(2 * \M_PI * $freq / $sr)->sin();

        if (1 === $channels) {
            $data = $mono->insertaxis(1);
        } else {
            $data = $mono->insertaxis(1)->tile([1, $channels]);
        }

        self::writeWav($path, $data, $sr, $subtype);
    }

    private static function writeWav(string $path, NDArray $data, int $sr, SampleFormat $subtype): void
    {
        $sf = new SndFile(
            $path,
            FileMode::Write,
            sampleRate: $sr,
            channels: $data->shape()[1] ?? 1,
            format: AudioFormat::Wav,
            subtype: $subtype,
        );
        $sf->write($data);
        $sf->close();
    }
}
