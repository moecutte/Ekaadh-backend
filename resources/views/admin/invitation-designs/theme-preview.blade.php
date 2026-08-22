<div class="lg:col-span-7">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 sticky top-4">
        <div class="flex items-center justify-between mb-3 gap-2">
            <div>
                <h3 class="text-sm font-bold">Live theme preview</h3>
                <p class="text-xs text-mute mt-0.5">Same invitation guests receive — tap the envelope to open it.</p>
            </div>
        </div>

        <div class="mx-auto rounded-xl border border-slate-200 shadow-inner bg-slate-50/50 p-2 relative overflow-hidden"
             style="max-width: 436px;">
            @if($design->exists)
                <iframe src="{{ route('admin.invitation-designs.preview', $design) }}?t={{ $design->updated_at?->timestamp }}"
                        id="admin-invite-preview"
                        title="Invitation theme preview"
                        class="w-full border-0 block bg-transparent rounded-lg"
                        style="min-height: 560px; height: 640px;"></iframe>
            @else
                <div class="rounded-lg bg-white px-6 py-16 text-center text-sm text-mute">
                    Choose a web theme and save. The live HTML invitation will appear here.
                </div>
            @endif
        </div>
        @if($design->exists)
            <p class="text-[11px] text-mute mt-3 text-center">
                This is the same card and envelope as the invite link. Use <strong>Replay envelope opening</strong> after it opens.
            </p>
            <script>
                window.addEventListener('message', function (e) {
                    if (!e.data || e.data.type !== 'ekaadh-invite-preview-height' || !e.data.height) return;
                    var frame = document.getElementById('admin-invite-preview');
                    if (frame) frame.style.height = Math.max(560, Number(e.data.height)) + 'px';
                });
            </script>
        @endif
    </div>
</div>
