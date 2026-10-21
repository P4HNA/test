<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One question: a prompt, up to four answers ({en, km}) and which one is right. */
class QuizQuestion extends Model
{
    use Concerns\HasBilingualFields;

    protected $fillable = ['quiz_id', 'prompt_en', 'prompt_km', 'options', 'correct_index', 'explanation_en', 'explanation_km', 'sort_order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_index' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return array<int, string> the answers in the reader's language */
    public function optionTexts(): array
    {
        $km = app()->getLocale() === 'km';

        return array_map(
            fn ($o) => (string) (($km && filled($o['km'] ?? null)) ? $o['km'] : ($o['en'] ?? '')),
            array_values((array) $this->options)
        );
    }
}
