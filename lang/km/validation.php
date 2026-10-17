<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines (Khmer)
    |--------------------------------------------------------------------------
    |
    | Covers the rules this project actually validates against. Any rule not
    | listed here falls back to the framework's English message, so add the
    | rule here when you start using it.
    |
    */

    'accepted' => 'ត្រូវតែទទួលយក :attribute។',
    'array' => ':attribute ត្រូវតែជាបញ្ជី។',
    'boolean' => ':attribute ត្រូវតែជា true ឬ false។',
    'confirmed' => 'ការបញ្ជាក់ :attribute មិនត្រូវគ្នាទេ។',
    'current_password' => 'ពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ។',
    'date' => ':attribute ត្រូវតែជាកាលបរិច្ឆេទត្រឹមត្រូវ។',
    'decimal' => ':attribute ត្រូវតែមានចំនួនទសភាគ :decimal ខ្ទង់។',
    'digits' => ':attribute ត្រូវតែមាន :digits ខ្ទង់។',
    'digits_between' => ':attribute ត្រូវតែមានចន្លោះពី :min ដល់ :max ខ្ទង់។',
    'email' => ':attribute ត្រូវតែជាអាសយដ្ឋានអ៊ីមែលត្រឹមត្រូវ។',
    'exists' => ':attribute ដែលបានជ្រើសមិនត្រឹមត្រូវទេ។',
    'file' => ':attribute ត្រូវតែជាឯកសារ។',
    'image' => ':attribute ត្រូវតែជារូបភាព។',
    'in' => ':attribute ដែលបានជ្រើសមិនត្រឹមត្រូវទេ។',
    'integer' => ':attribute ត្រូវតែជាលេខគត់។',
    'lowercase' => ':attribute ត្រូវតែជាអក្សរតូច។',
    'mimes' => ':attribute ត្រូវតែជាឯកសារប្រភេទ៖ :values។',
    'not_regex' => 'ទម្រង់របស់ :attribute មិនត្រឹមត្រូវទេ។',
    'numeric' => ':attribute ត្រូវតែជាលេខ។',
    'regex' => 'ទម្រង់របស់ :attribute មិនត្រឹមត្រូវទេ។',
    'required' => 'ត្រូវបំពេញ :attribute។',
    'same' => ':attribute និង :other ត្រូវតែដូចគ្នា។',
    'string' => ':attribute ត្រូវតែជាអក្សរ។',
    'unique' => ':attribute នេះត្រូវបានប្រើរួចហើយ។',
    'uploaded' => 'ការផ្ទុកឡើង :attribute បានបរាជ័យ។',
    'url' => ':attribute ត្រូវតែជាតំណត្រឹមត្រូវ។',

    'max' => [
        'array' => ':attribute មិនអាចមានលើសពី :max ធាតុទេ។',
        'file' => ':attribute មិនអាចធំជាង :max គីឡូបៃទេ។',
        'numeric' => ':attribute មិនអាចធំជាង :max ទេ។',
        'string' => ':attribute មិនអាចវែងជាង :max តួអក្សរទេ។',
    ],

    'min' => [
        'array' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min ធាតុ។',
        'file' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min គីឡូបៃ។',
        'numeric' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min។',
        'string' => ':attribute ត្រូវមានយ៉ាងហោចណាស់ :min តួអក្សរ។',
    ],

    'between' => [
        'array' => ':attribute ត្រូវមានចន្លោះពី :min ដល់ :max ធាតុ។',
        'file' => ':attribute ត្រូវមានចន្លោះពី :min ដល់ :max គីឡូបៃ។',
        'numeric' => ':attribute ត្រូវមានចន្លោះពី :min ដល់ :max។',
        'string' => ':attribute ត្រូវមានចន្លោះពី :min ដល់ :max តួអក្សរ។',
    ],

    'gt' => [
        'numeric' => ':attribute ត្រូវតែធំជាង :value។',
    ],

    'gte' => [
        'numeric' => ':attribute ត្រូវតែធំជាង ឬស្មើ :value។',
    ],

    'lt' => [
        'numeric' => ':attribute ត្រូវតែតូចជាង :value។',
    ],

    'lte' => [
        'numeric' => ':attribute ត្រូវតែតូចជាង ឬស្មើ :value។',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'ឈ្មោះ',
        'email' => 'អ៊ីមែល',
        'phone' => 'លេខទូរសព្ទ',
        'password' => 'ពាក្យសម្ងាត់',
        'password_confirmation' => 'ការបញ្ជាក់ពាក្យសម្ងាត់',
        'current_password' => 'ពាក្យសម្ងាត់បច្ចុប្បន្ន',
        'code' => 'កូដផ្ទៀងផ្ទាត់',
        'locale' => 'ភាសា',
        'title_en' => 'ចំណងជើង (អង់គ្លេស)',
        'title_km' => 'ចំណងជើង (ខ្មែរ)',
        'price' => 'តម្លៃ',
        'level' => 'កម្រិត',
        'category' => 'ប្រភេទ',
        'thumbnail' => 'រូបភាព',
        'thumbnail_url' => 'តំណរូបភាព',
    ],

];
