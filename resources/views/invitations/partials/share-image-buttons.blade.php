{{-- Capture invitation design as PNG (overlay: canvas compositor; else: DOM fallback). --}}
@php
    $shareTarget = $shareTarget ?? 'invitation-share-card';
    $shareTitle = $shareTitle ?? ($event->title ?? 'Ekaadh');
    $shareText = $shareText ?? __('ui.share_invitation_text', ['title' => $shareTitle]);
    $shareFileName = $shareFileName ?? 'ekaadh-invitation.png';
    $shareSpec = $shareSpec ?? null;
@endphp
<div
    class="mt-4 mb-6"
    x-data='invitationImageShare({
        targetId: @json($shareTarget),
        text: @json($shareText),
        fileName: @json($shareFileName),
        failMsg: @json(__("ui.share_invitation_failed")),
        preparingMsg: @json(__("ui.sharing_invitation")),
        shareLabel: @json(__("ui.share_invitation_image")),
        spec: @json($shareSpec),
    })'
>
    <p class="text-xs font-bold text-center mb-3" style="color: {{ $design['muted'] ?? '#64748b' }};">{{ __('ui.share_invitation') }}</p>
    <div class="flex flex-col sm:flex-row gap-2 max-w-sm mx-auto">
        <button
            type="button"
            @click="share()"
            :disabled="busy"
            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl text-sm font-bold py-3 text-white transition disabled:opacity-60"
            style="background: {{ $design['accent'] ?? '#323891' }};"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            <span x-text="busy ? preparingMsg : shareLabel"></span>
        </button>
        <button
            type="button"
            @click="download()"
            :disabled="busy"
            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl text-sm font-bold py-3 transition disabled:opacity-60"
            style="background: {{ $design['accent_soft'] ?? '#eef0f8' }}; color: {{ $design['accent'] ?? '#323891' }};"
        >
            {{ __('ui.download_invitation_image') }}
        </button>
    </div>
    <p x-show="error" x-cloak x-text="error" class="text-center text-xs text-red-600 mt-2"></p>
</div>
