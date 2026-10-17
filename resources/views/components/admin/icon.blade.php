{{-- The admin shares the product icon set; see components/icon.blade.php. --}}
@props(['name'])
<x-icon :name="$name" :stroke="1.7" {{ $attributes }} />
