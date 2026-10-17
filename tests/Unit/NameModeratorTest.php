<?php

namespace Tests\Unit;

use App\Support\NameModerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NameModeratorTest extends TestCase
{
    #[DataProvider('blockedKhmerNames')]
    public function test_blocks_khmer_terms(string $name): void
    {
        $this->assertTrue(NameModerator::isRestricted($name), "Expected \"{$name}\" to be restricted.");
    }

    public static function blockedKhmerNames(): array
    {
        return [
            'exact vulgar term' => ['ចុយ'],
            'exact vulgar term 2' => ['ក្តួយ'],
            'obfuscated/reversed term' => ['ជាខ្នុយ'],
            'short blocked fragment' => ['ក្ត'],
            'embedded inside a longer chosen name' => ['សុភាចុយសុខា'],
            'with surrounding spaces' => ['  ចុយ  '],
        ];
    }

    #[DataProvider('blockedLatinNames')]
    public function test_blocks_latin_romanized_slang(string $name): void
    {
        $this->assertTrue(NameModerator::isRestricted($name), "Expected \"{$name}\" to be restricted.");
    }

    public static function blockedLatinNames(): array
    {
        return [
            'lowercase' => ['jkj'],
            'uppercase' => ['JKJ'],
            'mixed case' => ['Jmr'],
            'uppercase mixed' => ['JMR'],
            'two-word slang term' => ['Ov hg'],
            'two-word slang term, different case' => ['OV HG'],
            'kdmv exact' => ['Kdmv'],
            'as a whole word among other words' => ['Cool jkj Guy'],
            'with extra/repeated spaces' => ['  jkj   '],
            'with a separator punctuation trick' => ['j.k.j'],
            'with a dash separator trick' => ['j-k-j'],
            'with a repeated-character trick' => ['jjkkjj'],
            'english profanity as a word' => ['Big Shit'],
            'english profanity, uppercase with dots' => ['B.I.T.C.H'],
            'english profanity, doubled letters' => ['Fuuuck'],
            'english inflection inside a longer name' => ['The mother fucker'],
            'english compound written as one word' => ['Motherfucker'],
            'english compound split with separators' => ['mother.fucker'],
        ];
    }

    #[DataProvider('legitimateNames')]
    public function test_allows_legitimate_names(string $name): void
    {
        $this->assertFalse(NameModerator::isRestricted($name), "Did not expect \"{$name}\" to be restricted.");
    }

    public static function legitimateNames(): array
    {
        return [
            'simple english name' => ['Sokha'],
            'full english name' => ['Sok Dara'],
            'name with doubled letters' => ['Anna Lee'],
            'name with an apostrophe' => ["O'Brien"],
            'ordinary khmer name' => ['សុខា'],
            'another ordinary khmer name' => ['ដារា'],
            'short unrelated latin initials' => ['JM'],
            'a longer word that merely starts with a blocked fragment' => ['Jmrith'],
            'hyphenated legitimate name' => ['Anne-Marie'],
            'name merely containing an english blocked word' => ['Shitij Kumar'],
            'place name containing a blocked word' => ['Scunthorpe'],
        ];
    }
}
