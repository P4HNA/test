<?php

namespace App\Support;

use App\Models\Course;

/**
 * The picture shown for a course on the live-design pages: its own cover
 * when it has one, otherwise a stock photo that matches its software.
 */
class CoursePicture
{
    private const STOCK = [
        'ps' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
        'ai' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
        'id' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80',
        'gd' => 'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?w=800&auto=format&fit=crop&q=80',
        'ux' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800&auto=format&fit=crop&q=80',
        'mo' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80',
    ];

    private const BY_MARK = [
        'ps' => 'ps', 'ai' => 'ai', 'id' => 'id', 'gd' => 'gd',
        'fg' => 'ux', 'xd' => 'ux', 'ae' => 'mo', 'pr' => 'mo',
    ];

    public static function for(Course $course, int $width = 800): string
    {
        $own = $course->thumbnailSrc();

        if ($own) {
            return $own;
        }

        $key = self::BY_MARK[strtolower(trim((string) $course->mark))] ?? self::fromCategory((string) $course->category);

        return str_replace('w=800', 'w='.$width, self::STOCK[$key]);
    }

    private static function fromCategory(string $category): string
    {
        $c = strtolower($category);

        return match (true) {
            (bool) preg_match('/ux|ui|figma|xd|web|app/', $c) => 'ux',
            (bool) preg_match('/motion|after effects|animation|video|premiere/', $c) => 'mo',
            str_contains($c, 'photoshop') => 'ps',
            str_contains($c, 'illustrator') => 'ai',
            str_contains($c, 'indesign') => 'id',
            default => 'gd',
        };
    }
}
