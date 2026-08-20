@php
    $simple = $simple ?? false;
    $showPerPage = $showPerPage ?? ! $simple;
    $perPageOptions = $perPageOptions ?? [10, 15, 25, 50];
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = [];
    if ($last <= 7) {
        $pages = range(1, $last);
    } else {
        $pages[] = 1;
        $start = max(2, $current - 1);
        $end = min($last - 1, $current + 1);
        if ($start > 2) {
            $pages[] = '...';
        }
        foreach (range($start, $end) as $n) {
            $pages[] = $n;
        }
        if ($end < $last - 1) {
            $pages[] = '...';
        }
        $pages[] = $last;
    }
@endphp

@if($paginator->total() > 0)
    <div class="px-4 sm:px-5 py-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-xs text-mute">
            {{ __('ui.showing_range', [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => number_format($paginator->total()),
            ]) }}
        </p>

        <div class="flex flex-wrap items-center gap-3">
            @if($showPerPage)
                <form method="GET" class="flex items-center gap-1.5">
                    @foreach(request()->except(['page', 'per_page']) as $key => $value)
                        @continue(str_ends_with((string) $key, '_page'))
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="text-[11px] font-bold uppercase tracking-wide text-mute">{{ __('ui.rows') }}</label>
                    <select name="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold outline-none focus:border-brand">
                        @foreach($perPageOptions as $n)
                            <option value="{{ $n }}" @selected((int) $paginator->perPage() === (int) $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if($paginator->hasPages())
                <nav class="flex items-center gap-1" aria-label="{{ __('ui.pagination') }}">
                    @if($paginator->onFirstPage())
                        <span class="px-2 py-1 rounded-lg text-xs font-bold text-slate-300">{{ __('ui.prev') }}</span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="px-2 py-1 rounded-lg text-xs font-bold text-brand hover:bg-brand/5">{{ __('ui.prev') }}</a>
                    @endif

                    @unless($simple)
                        @foreach($pages as $page)
                            @if($page === '...')
                                <span class="px-1 text-xs text-mute">…</span>
                            @elseif((int) $page === $current)
                                <span class="min-w-[1.75rem] px-2 py-1 rounded-lg text-xs font-bold bg-brand text-white text-center">{{ $page }}</span>
                            @else
                                <a href="{{ $paginator->url($page) }}" class="min-w-[1.75rem] px-2 py-1 rounded-lg text-xs font-bold text-ink hover:bg-slate-100 text-center">{{ $page }}</a>
                            @endif
                        @endforeach
                    @else
                        <span class="text-[11px] font-semibold text-mute px-1">{{ $current }}/{{ $last }}</span>
                    @endunless

                    @if($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="px-2 py-1 rounded-lg text-xs font-bold text-brand hover:bg-brand/5">{{ __('ui.next') }}</a>
                    @else
                        <span class="px-2 py-1 rounded-lg text-xs font-bold text-slate-300">{{ __('ui.next') }}</span>
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endif
