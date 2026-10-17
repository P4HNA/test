<form method="POST" action="{{ route('locale') }}">
    @csrf
    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'km' ? 'en' : 'km' }}">
    {{-- Sits inside the top bar's language/theme pill (.sn-cluster), which styles it. --}}
    <button type="submit" class="sn-lang" aria-label="Switch language">
        {{ app()->getLocale() === 'km' ? 'EN' : 'KM' }}
    </button>
</form>
