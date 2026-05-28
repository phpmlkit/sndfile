<?php

declare(strict_types=1);

namespace PhpMlKit\Sndfile\FFI;

use Codewithkyrian\PlatformPackageInstaller\Platform;
use FFI;
use FFI\CData;

/**
 * Abstract base for low-level FFI shared-library bindings.
 *
 * Handles platform detection, library path resolution, and FFI::cdef()
 * loading. Each subclass specifies its header file name, library basename,
 * and version via the abstract methods.
 *
 * Subclasses are singletons — use ::get() to obtain the shared instance.
 */
abstract class NativeLibrary
{
    /** @var array<string, array{directory: string, libraryTemplate: string}> */
    protected const PLATFORMS = [
        'linux-x86_64' => [
            'directory' => 'linux-x86_64',
            'libraryTemplate' => 'lib{name}.so.{version}',
        ],
        'linux-arm64' => [
            'directory' => 'linux-arm64',
            'libraryTemplate' => 'lib{name}.so.{version}',
        ],
        'darwin-x86_64' => [
            'directory' => 'darwin-x86_64',
            'libraryTemplate' => 'lib{name}.{version}.dylib',
        ],
        'darwin-arm64' => [
            'directory' => 'darwin-arm64',
            'libraryTemplate' => 'lib{name}.{version}.dylib',
        ],
        'windows-64' => [
            'directory' => 'windows-64',
            'libraryTemplate' => '{name}-{version}.dll',
        ],
    ];

    protected \FFI $ffi;

    /** @var array{directory: string, libraryTemplate: string} */
    protected array $platformConfig;

    protected function __construct()
    {
        $config = Platform::findBestMatch(self::PLATFORMS);

        if (false === $config) {
            $current = Platform::current();

            throw new \RuntimeException(
                "Unsupported platform: {$current['os']}-{$current['arch']}. "
                .'Supported platforms: '.implode(', ', array_keys(self::PLATFORMS))
            );
        }

        $this->platformConfig = $config;

        $this->loadLibrary();
    }

    /** The underlying FFI instance for this library. */
    public function ffi(): \FFI
    {
        return $this->ffi;
    }

    /**
     * Allocate a new C value in this library's FFI scope.
     *
     * @param string $type       C type name (e.g. 'float[1024]', 'struct SF_INFO')
     * @param bool   $owned      Whether PHP owns the memory (default true)
     * @param bool   $persistent Whether the allocation survives requests
     */
    public function new(string $type, bool $owned = true, bool $persistent = false): CData
    {
        $result = $this->ffi->new($type, $owned, $persistent);
        \assert(null !== $result);

        return $result;
    }

    /** Full path to the C header file. */
    protected function getHeaderPath(): string
    {
        return \dirname(__DIR__, 2).'/include/'.$this->getHeaderName().'.h';
    }

    /** Full path to the shared library binary. */
    protected function getLibraryPath(): string
    {
        $template = $this->platformConfig['libraryTemplate'];
        $name = $this->getLibraryName();
        $version = $this->getLibraryVersion();

        if (str_contains($template, 'lib{name}') && str_starts_with($name, 'lib')) {
            $template = str_replace('lib{name}', '{name}', $template);
        }

        $filename = str_replace(['{name}', '{version}'], [$name, $version], $template);

        return \dirname(__DIR__, 2).'/lib/'.$this->platformConfig['directory'].'/'.$filename;
    }

    /** C header filename without the .h extension. */
    abstract protected function getHeaderName(): string;

    /** Library basename (without version or extension). */
    abstract protected function getLibraryName(): string;

    /** Library version string used in the filename. */
    abstract protected function getLibraryVersion(): string;

    /**
     * Parse the C header and load the shared library via FFI::cdef().
     *
     * Called once during construction. The header and library paths are
     * resolved from the subclass's getHeaderName / getLibraryName / getLibraryVersion.
     */
    protected function loadLibrary(): void
    {
        $headerPath = $this->getHeaderPath();

        if (!file_exists($headerPath)) {
            throw new \RuntimeException("Header file not found: {$headerPath}");
        }

        $libraryPath = $this->getLibraryPath();

        if (!file_exists($libraryPath)) {
            throw new \RuntimeException("Library file not found: {$libraryPath}");
        }

        $headerContent = file_get_contents($headerPath);

        if (false === $headerContent) {
            throw new \RuntimeException("Failed to read header file: {$headerPath}");
        }

        $this->ffi = \FFI::cdef($headerContent, $libraryPath);
    }
}
