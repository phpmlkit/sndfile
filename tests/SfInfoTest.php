<?php

declare(strict_types=1);

namespace PhpMlKit\SoundFile\Tests;

use PhpMlKit\SoundFile\Enums\AudioFormat;
use PhpMlKit\SoundFile\Enums\SampleFormat;
use PhpMlKit\SoundFile\Exceptions\SoundFileException;
use PhpMlKit\SoundFile\SfInfo;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class SfInfoTest extends TestCase
{
    public function testProbeReturnsCorrectMetadata(): void
    {
        $info = SfInfo::probe(Fixtures::monoFloatWav());

        $this->assertSame(800, $info->frames);
        $this->assertSame(1, $info->channels);
        $this->assertSame(8000, $info->sampleRate);
        $this->assertSame(AudioFormat::Wav, $info->format);
        $this->assertSame(SampleFormat::Float, $info->sampleFormat);
        $this->assertTrue($info->seekable);
    }

    public function testProbeStereoFile(): void
    {
        $info = SfInfo::probe(Fixtures::stereoFloatWav());

        $this->assertSame(2, $info->channels);
        $this->assertSame(800, $info->frames);
    }

    public function testProbeOnMissingFileThrows(): void
    {
        $this->expectException(SoundFileException::class);

        SfInfo::probe(sys_get_temp_dir().'/sndfile_nonexistent_'.uniqid().'.wav');
    }

    public function testProbeReturnsCorrectDurations(): void
    {
        $info = SfInfo::probe(Fixtures::monoFloatWav());

        $this->assertEqualsWithDelta(0.1, $info->duration(), 0.001);
    }

    public function testSampleCount(): void
    {
        $info = SfInfo::probe(Fixtures::monoFloatWav());

        $this->assertSame(800, $info->nSamples());
    }

    public function testWithFramesPreservesOtherFields(): void
    {
        $info = SfInfo::probe(Fixtures::monoFloatWav());
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
        $info = SfInfo::probe(Fixtures::monoFloatWav());
        $modified = $info->withChannels(4);

        $this->assertSame(4, $modified->channels);
        $this->assertSame($info->frames, $modified->frames);
        $this->assertSame($info->sampleRate, $modified->sampleRate);
    }

    public function testWithSampleRatePreservesOtherFields(): void
    {
        $info = SfInfo::probe(Fixtures::monoFloatWav());
        $modified = $info->withSampleRate(44100);

        $this->assertSame(44100, $modified->sampleRate);
        $this->assertSame($info->frames, $modified->frames);
        $this->assertSame($info->channels, $modified->channels);
    }
}
