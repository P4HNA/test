<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One file in the Resources library (a PSD mockup, a vector kit, a font, a
 * Figma kit...), shared by the teacher for students to download.
 */
class DesignResource extends Model
{
    use Concerns\HasBilingualFields, Concerns\HasUuid, HasFactory;

    /** The filter tabs on the Resources page, in order. */
    public const CATEGORIES = [
        'Photoshop',
        'Illustrator',
        'InDesign',
        'After Effects',
        'Figma',
        'Typography',
        'Branding',
        'Motion Graphics',
    ];

    /** Cover shown when a resource has no picture of its own, by category. */
    private const FALLBACK_PICTURES = [
        'Photoshop' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
        'Illustrator' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
        'InDesign' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80',
        'After Effects' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80',
        'Figma' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800&auto=format&fit=crop&q=80',
        'Typography' => 'https://images.unsplash.com/photo-1516962215378-7fa2e137ae93?w=800&auto=format&fit=crop&q=80',
        'Branding' => 'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?w=800&auto=format&fit=crop&q=80',
        'Motion Graphics' => 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?w=800&auto=format&fit=crop&q=80',
    ];

    protected $fillable = [
        'title_en',
        'title_km',
        'description_en',
        'description_km',
        'type',
        'software',
        'category',
        'tags',
        'file_path',
        'file_name',
        'file_size',
        'external_url',
        'thumbnail_url',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'file_size' => 'integer',
            'downloads_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** Students who bookmarked it. */
    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'design_resource_bookmarks')->withTimestamps();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Library order: the teacher's order first, newest first within it. */
    public function scopeLibraryOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function isDownloadable(): bool
    {
        return filled($this->file_path) || filled($this->external_url);
    }

    /** True when the download is a link to somewhere else rather than a file kept here. */
    public function isLink(): bool
    {
        return blank($this->file_path) && filled($this->external_url);
    }

    /** "45.2 MB", or "Link" for a resource that lives elsewhere. */
    public function sizeLabel(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return $this->isLink() ? 'Link' : '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $i);

        return ($i === 0 ? $value : number_format($value, 1)).' '.$units[$i];
    }

    /** Web address of its picture: an uploaded one, a pasted link, or the category's stock cover. */
    public function pictureSrc(): string
    {
        $url = trim((string) $this->thumbnail_url);

        if ($url !== '') {
            return preg_match('#^https?://#i', $url) ? $url : asset(ltrim($url, '/'));
        }

        return self::FALLBACK_PICTURES[$this->category] ?? self::FALLBACK_PICTURES['Photoshop'];
    }

    /** @return list<string> */
    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', (array) $this->tags)));
    }
}
