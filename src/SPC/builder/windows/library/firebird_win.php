<?php

declare(strict_types=1);

namespace SPC\builder\windows\library;

use SPC\exception\BuildFailureException;
use SPC\store\FileSystem;

/**
 * Firebird client headers and import library, taken from the official
 * Firebird 3 x64 zip kit.
 *
 * Only the build-time pieces are used: the top-level headers (ibase.h pulls
 * in iberror.h and others beside it), the firebird/ C++ interface headers
 * (pdo_firebird in PHP 8.4+ includes firebird/Interface.h) and the
 * fbclient_ms.lib import library. fbclient.dll itself is not bundled into
 * php.exe; pdo_firebird delay-loads it, so it must ship next to php.exe or on
 * PATH wherever Firebird is actually used.
 */
class firebird_win extends WindowsLibraryBase
{
    public const NAME = 'firebird-win';

    protected function build(): void
    {
        copy($this->source_dir . '\lib\fbclient_ms.lib', BUILD_LIB_PATH . '\fbclient_ms.lib');
        // Forward slashes: a backslash in a glob pattern can read as an escape.
        $headers = glob(str_replace('\\', '/', $this->source_dir) . '/include/*.h') ?: [];
        if (!in_array('ibase.h', array_map('basename', $headers), true)) {
            throw new BuildFailureException('Firebird kit has no include/ibase.h');
        }
        foreach ($headers as $header) {
            copy($header, BUILD_INCLUDE_PATH . '\\' . basename($header));
        }
        FileSystem::copyDir($this->source_dir . '\include\firebird', BUILD_INCLUDE_PATH . '\firebird');
    }
}
