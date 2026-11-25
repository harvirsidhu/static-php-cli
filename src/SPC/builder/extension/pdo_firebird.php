<?php

declare(strict_types=1);

namespace SPC\builder\extension;

use SPC\builder\Extension;
use SPC\store\FileSystem;
use SPC\util\CustomExt;

#[CustomExt('pdo_firebird')]
class pdo_firebird extends Extension
{
    /**
     * Delay-load fbclient.dll.
     *
     * A static php.exe that imports fbclient.dll normally will not start at
     * all on a machine without it, and PHP 8.4's pdo_firebird also imports
     * fb_get_master_interface, which a Firebird 2.5 client does not export.
     * Delay-loaded, the DLL is only resolved on the first Firebird call, and
     * the Firebird 3+ only entry points are never reached when the headers are
     * Firebird 3 (they sit behind FB_API_VER >= 40).
     */
    public function patchBeforeBuildconf(): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $file = SOURCE_PATH . '/php-src/ext/pdo_firebird/config.w32';
        $marker = 'ADD_FLAG("CFLAGS_PDO_FIREBIRD", "/EHsc");';

        if (str_contains(FileSystem::readFile($file), '/DELAYLOAD:fbclient.dll')) {
            return false;
        }

        FileSystem::replaceFileStr(
            $file,
            $marker,
            $marker . "\n\t\tADD_FLAG(\"LDFLAGS\", \"/DELAYLOAD:fbclient.dll\");\n\t\tADD_FLAG(\"LIBS\", \"delayimp.lib\");"
        );

        return true;
    }

    public function getWindowsConfigureArg(bool $shared = false): string
    {
        return '--with-pdo-firebird=yes';
    }
}
