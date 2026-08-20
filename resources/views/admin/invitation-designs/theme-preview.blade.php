<div class="lg:col-span-7">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 sticky top-4">
        <div class="flex items-center justify-between mb-3 gap-2">
            <div>
                <h3 class="text-sm font-bold">Live theme preview</h3>
                <p class="text-xs text-mute mt-0.5">HTML invitation — animation included. Save colors/theme to refresh.</p>
            </div>
            @if($design->exists)
            <button type="button"
                    @click="startEnvelopeDemo()"
                    class="text-[10px] font-bold px-2.5 py-1 rounded-full border bg-white text-mute border-slate-200 hover:border-brand/40">
                Envelope demo
            </button>
            @endif
        </div>

        <div class="mx-auto rounded-xl border border-slate-200 shadow-inner bg-slate-50/50 p-2 relative overflow-hidden"
             style="max-width: 436px;">
            @if($design->exists)
                <div class="relative">
                    <iframe src="{{ route('admin.invitation-designs.preview', $design) }}?t={{ $design->updated_at?->timestamp }}"
                            title="Invitation theme preview"
                            class="w-full border-0 block bg-transparent rounded-lg"
                            style="min-height: 680px;"></iframe>
                    @include('invitations.partials.envelope-demo-overlay', ['design' => $design])
                </div>
            @else
                <div class="rounded-lg bg-white px-6 py-16 text-center text-sm text-mute">
                    Choose a web theme and save. The live HTML invitation will appear here.
                </div>
            @endif
        </div>
        @if($design->exists)
            <p class="text-[11px] text-mute mt-3 text-center">
                Tap the envelope to open it, or use <strong>Envelope demo</strong> to play it again.
            </p>
        @endif
    </div>
</div>
