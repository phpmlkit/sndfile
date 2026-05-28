<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile\Tests;

use PhpMlKit\Sndfile\Enums\AudioFormat;
use PhpMlKit\Sndfile\Enums\SampleFormat;
use PhpMlKit\Sndfile\Exceptions\SndfileException;
use PhpMlKit\Sndfile\SndfileInfo;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SndfileInfoTest extends TestCase
{
    public function testProbeReturnsCorrectMetadata(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());

        $this->assertSame(800, $info->frames);
        $this->assertSame(1, $info->channels);
        $this->assertSame(8000, $info->sampleRate);
        $this->assertSame(AudioFormat::Wav, $info->format);
        $this->assertSame(SampleFormat::Float, $info->sampleFormat);
        $this->assertTrue($info->seekable);
    }

    public function testProbeStereoFile(): void
    {
        $info = SndfileInfo::probe(Fixtures::stereoFloatWav());

        $this->assertSame(2, $info->channels);
        $this->assertSame(800, $info->frames);
    }

    public function testProbeOnMissingFileThrows(): void
    {
        $this->expectException(SndfileException::class);

        SndfileInfo::probe(sys_get_temp_dir().'/sndfile_nonexistent_'.uniqid().'.wav');
    }

    public function testProbeReturnsCorrectDurations(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());

        $this->assertEqualsWithDelta(0.1, $info->duration(), 0.001);
    }

    public function testSampleCount(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());

        $this->assertSame(800, $info->nSamples());
    }

    public function testForWriteCreatesValidInfo(): void
    {
        $info = SndfileInfo::forWrite(100, 2, 44100, AudioFormat::Wav, SampleFormat::Float);

        $this->assertSame(100, $info->frames);
        $this->assertSame(2, $info->channels);
        $this->assertSame(44100, $info->sampleRate);
        $this->assertSame(AudioFormat::Wav, $info->format);
        $this->assertSame(SampleFormat::Float, $info->sampleFormat);
        $this->assertTrue($info->seekable);
    }

    public function testWithFramesPreservesOtherFields(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());
        $modified = $info->withFrames(100);

        $this->assertSame(100, $modified->frames);
        $this->assertSame($info->channels, $modified->channels);
        $this->assertSame($info->sampleRate, $modified->sampleRate);
        $this->assertSame($info->format, $modified->format);
        $this->assertSame($info->sampleFormat, $modified->sampleFormat);
        $this->assertSame($info->seekable, $modified->seekable);
    }

    public function testWithChannelsPreservesOtherFields(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());
        $modified = $info->withChannels(4);

        $this->assertSame(4, $modified->channels);
        $this->assertSame($info->frames, $modified->frames);
        $this->assertSame($info->sampleRate, $modified->sampleRate);
    }

    public function testWithSampleRatePreservesOtherFields(): void
    {
        $info = SndfileInfo::probe(Fixtures::monoFloatWav());
        $modified = $info->withSampleRate(44100);

        $this->assertSame(44100, $modified->sampleRate);
        $this->assertSame($info->frames, $modified->frames);
        $this->assertSame($info->channels, $modified->channels);
    }
}
