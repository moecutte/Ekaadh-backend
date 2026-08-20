<script>
function invitationImageShare(opts) {
    const DESIGN_W = 420;
    const DESIGN_H = Math.round(DESIGN_W * 4.2 / 3); // 588

    return {
        busy: false,
        error: '',
        preparingMsg: opts.preparingMsg,
        shareLabel: opts.shareLabel,

        async captureBlob() {
            this.error = '';
            try {
                if (opts.spec && opts.spec.mode === 'overlay') {
                    return await this.composeOverlayPng(opts.spec);
                }
                return await this.captureDomFallback();
            } catch (e) {
                console.error(e);
                this.error = opts.failMsg;
                return null;
            }
        },

        async composeOverlayPng(spec) {
            const pixelRatio = 2;
            const W = DESIGN_W * pixelRatio;
            const H = DESIGN_H * pixelRatio;
            const s = pixelRatio;

            if (document.fonts?.ready) {
                try { await document.fonts.ready; } catch (e) {}
            }

            const canvas = document.createElement('canvas');
            canvas.width = W;
            canvas.height = H;
            const ctx = canvas.getContext('2d');
            if (!ctx) throw new Error('canvas');

            // Background
            ctx.fillStyle = spec.cardBg || '#ffffff';
            ctx.fillRect(0, 0, W, H);

            if (spec.graphicUrl) {
                const img = await this.loadImage(spec.graphicUrl);
                this.drawCover(ctx, img, W, H);
            }

            for (const field of (spec.fields || [])) {
                await this.drawField(ctx, field, s);
            }

            return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        },

        loadImage(url) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = () => reject(new Error('image'));
                img.src = url;
            });
        },

        drawCover(ctx, img, W, H) {
            const ir = img.naturalWidth / img.naturalHeight;
            const cr = W / H;
            let dw, dh, dx, dy;
            if (ir > cr) {
                dh = H;
                dw = H * ir;
                dx = (W - dw) / 2;
                dy = 0;
            } else {
                dw = W;
                dh = W / ir;
                dx = 0;
                dy = (H - dh) / 2;
            }
            ctx.drawImage(img, dx, dy, dw, dh);
        },

        async drawField(ctx, field, s) {
            const text = String(field.text || '').trim();
            if (!text) return;

            const padX = 4 * s;
            const left = (Number(field.pos_x) / 100) * DESIGN_W * s + padX;
            const top = (Number(field.pos_y) / 100) * DESIGN_H * s;
            const boxW = Math.max(1, (Number(field.box_width) / 100) * DESIGN_W * s - padX * 2);
            const fontSize = Number(field.font_size || 18) * s;
            const lineHeight = fontSize * 1.25;
            const weight = String(field.font_weight || '400');
            const style = (field.font_style === 'italic') ? 'italic' : 'normal';
            const family = field.font_family || 'Montserrat, sans-serif';
            const familyName = String(family).split(',')[0].trim().replace(/['"]/g, '') || 'Montserrat';
            const align = field.text_align || 'center';
            const color = field.color || '#0f1a2e';

            // Ensure webfont is available before measuring.
            try {
                await document.fonts.load(`${style} ${weight} ${Math.round(fontSize)}px "${familyName}"`);
            } catch (e) {}

            ctx.save();
            ctx.fillStyle = color;
            ctx.font = `${style} ${weight} ${fontSize}px "${familyName}", sans-serif`;
            ctx.textBaseline = 'top';
            ctx.textAlign = align === 'left' ? 'left' : (align === 'right' ? 'right' : 'center');

            const lines = this.wrapLines(ctx, text, boxW);
            let y = top;
            for (const line of lines) {
                let x = left;
                if (align === 'center') x = left + boxW / 2;
                if (align === 'right') x = left + boxW;
                ctx.fillText(line, x, y);
                y += lineHeight;
            }
            ctx.restore();
        },

        wrapLines(ctx, text, maxWidth) {
            const paragraphs = String(text).split(/\n/);
            const lines = [];
            for (const para of paragraphs) {
                const words = para.split(/\s+/).filter(Boolean);
                if (words.length === 0) {
                    lines.push('');
                    continue;
                }
                let line = words[0];
                for (let i = 1; i < words.length; i++) {
                    const test = line + ' ' + words[i];
                    if (ctx.measureText(test).width <= maxWidth) {
                        line = test;
                    } else {
                        lines.push(line);
                        line = words[i];
                    }
                }
                lines.push(line);
            }
            return lines;
        },

        async captureDomFallback() {
            const root = document.getElementById(opts.targetId);
            if (!root || typeof html2canvas !== 'function') {
                this.error = opts.failMsg;
                return null;
            }
            const card = root.querySelector('.invitation-design-card') || root.querySelector('article') || root;
            if (document.fonts?.ready) {
                try { await document.fonts.ready; } catch (e) {}
            }
            card.classList.add('invite-capture');
            try {
                const canvas = await html2canvas(card, {
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: null,
                    scale: 2,
                    logging: false,
                });
                return await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
            } finally {
                card.classList.remove('invite-capture');
            }
        },

        async share() {
            if (this.busy) return;
            this.busy = true;
            try {
                const blob = await this.captureBlob();
                if (!blob) return;
                const file = new File([blob], opts.fileName, { type: 'image/png' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], title: opts.text, text: opts.text });
                    return;
                }
                this.triggerDownload(blob);
            } catch (e) {
                if (e && e.name === 'AbortError') return;
                this.error = opts.failMsg;
            } finally {
                this.busy = false;
            }
        },

        async download() {
            if (this.busy) return;
            this.busy = true;
            try {
                const blob = await this.captureBlob();
                if (!blob) return;
                this.triggerDownload(blob);
            } catch (e) {
                this.error = opts.failMsg;
            } finally {
                this.busy = false;
            }
        },

        triggerDownload(blob) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = opts.fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1500);
        },
    };
}
</script>
<style>[x-cloak] { display: none !important; }</style>
