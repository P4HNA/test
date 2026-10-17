<?php

namespace App\Models\Concerns;

trait HasBilingualFields
{
    public function localized(string $field): string
    {
        $km = $this->{$field.'_km'} ?? null;

        if (app()->getLocale() === 'km' && filled($km)) {
            return (string) $km;
        }

        return (string) ($this->{$field.'_en'} ?? '');
    }
}
