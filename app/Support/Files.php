<?php

namespace Azuriom\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Filesystem\Filesystem;

class Files
{
    public static function relativeLink(string $target, string $link): void
    {
        // Relative symlink seems broken on Windows
        windows_os() ? File::link($target, $link) : File::relativeLink($target, $link);
    }

    public static function removeLink(string $link): void
    {
        // Symlink can be treated as directories on Windows...
        if (! is_link($link) && is_dir($link)) {
            rmdir($link);

            return;
        }

        // Symfony Filesystem has better symlink support than the Laravel one
        (new Filesystem())->remove($link);
    }
}
