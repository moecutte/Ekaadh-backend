<script>
function profilePhoto() {
    return {
        previewUrl: @json($currentUrl ?? null),
        fileName: '',
        removed: false,
        onFileSelect(e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            this.removed = false;
            this.fileName = file.name;
            if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = URL.createObjectURL(file);
        },
        removePhoto() {
            this.removed = true;
            this.fileName = '';
            if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = null;
            if (this.$refs.photoInput) {
                this.$refs.photoInput.value = '';
            }
        },
    };
}
</script>
