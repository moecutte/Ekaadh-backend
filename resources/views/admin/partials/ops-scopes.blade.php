<div class="flex flex-wrap items-center gap-1.5 mb-4">
    @foreach($scopes as $chip)
        <a href="{{ $chip['url'] }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors {{ $chip['active'] ? 'bg-ink text-white border-ink' : 'bg-white text-mute border-slate-200 hover:border-ink hover:text-ink' }}">
            {{ $chip['label'] }}
            @if($chip['count'] !== null)
                <span class="min-w-[1.1rem] text-center {{ $chip['active'] ? 'text-white/80' : 'text-slate-400' }}">{{ $chip['count'] }}</span>
            @endif
        </a>
    @endforeach
</div>
