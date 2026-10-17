<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Whether public/storage points at storage/app/public, so uploaded files
     * (course pictures, among others) can actually be served.
     *
     * On Windows "php artisan storage:link" leaves a junction that both
     * is_link() and is_dir() report as false, which had the admin panel
     * telling people to run storage:link when it was already linked and
     * pictures were serving fine. Fall back to resolving the link target.
     */
    protected function storageLinked(): bool
    {
        $link = public_path('storage');

        if (is_dir($link) || is_link($link)) {
            return true;
        }

        return file_exists($link) && is_dir((string) @readlink($link));
    }
}
