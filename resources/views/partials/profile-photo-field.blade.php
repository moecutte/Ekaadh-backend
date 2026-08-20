<input type="file" name="avatar" x-ref="photoInput" accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden" @change="onFileSelect($event)">
<input type="hidden" name="remove_avatar" :value="removed ? 1 : 0">
<div class="flex items-center gap-4">
    <button type="button" @click="$refs.photoInput.click()" class="relative group shrink-0">
        <img x-show="previewUrl" x-cloak :src="previewUrl" alt="Profile" class="w-20 h-20 rounded-2xl object-cover border border-slate-100 bg-brand/10">
        <div x-show="!previewUrl" class="w-20 h-20 rounded-2xl bg-brand/12 flex items-center justify-center text-brand text-xl font-black">
            {{ auth()->user()->initials() }}
        </div>
        <span class="absolute inset-0 rounded-2xl bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[11px] font-bold">Change</span>
    </button>
    <div class="min-w-0">
        <button type="button" @click="$refs.photoInput.click()" class="text-sm font-bold text-brand">Upload image</button>
        <p class="text-xs text-mute mt-1">PNG, JPG or WEBP · max 5 MB</p>
        <button type="button" x-show="previewUrl" x-cloak @click="removePhoto()" class="mt-2 text-xs font-bold text-red-500">Remove photo</button>
        <p class="text-xs text-mute mt-1" x-show="fileName" x-cloak x-text="'Selected: ' + fileName"></p>
    </div>
</div>
