@once
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
<style>
.inv-env-stage { max-width: 420px; }
.inv-env {
    --env-ease: cubic-bezier(.22, 1, .36, 1);
    position: relative;
    perspective: 1400px;
}
.inv-env-shell {
    position: relative;
    max-height: 320px;
    overflow: hidden;
    border-radius: 6px 6px 14px 14px;
    box-shadow: 0 22px 50px -18px rgb(0 0 0 / .45);
    transition: max-height 1.15s var(--env-ease) .35s, box-shadow .6s ease;
}
.inv-env.is-open .inv-env-shell {
    max-height: 920px;
    overflow: visible;
    box-shadow: 0 18px 40px -20px rgb(0 0 0 / .28);
}
.inv-env-flap {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 46%;
    z-index: 30;
    transform-origin: top center;
    transform: rotateX(0deg);
    transition: transform .95s var(--env-ease);
    pointer-events: none;
}
.inv-env-flap-face {
    width: 100%;
    height: 100%;
    clip-path: polygon(0 0, 100% 0, 50% 100%);
    box-shadow: 0 8px 18px rgb(0 0 0 / .18);
}
.inv-env.is-open .inv-env-flap {
    transform: rotateX(-172deg);
    z-index: 4;
}
.inv-env-seal {
    position: absolute;
    left: 50%;
    top: 42%;
    width: 72px;
    height: 72px;
    margin-left: -36px;
    z-index: 46;
    pointer-events: none;
    display: block;
    transition: transform .4s var(--env-ease), opacity .35s ease;
}
.inv-env-seal-wax {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background:
        radial-gradient(circle at 30% 26%, #f7d9a4 0 14%, #d4a017 38%, #9a6410 72%, #6a430c 100%);
    box-shadow:
        0 0 0 3px #e8c56a,
        0 0 0 7px #8c4d16,
        0 10px 18px rgb(0 0 0 / .4),
        inset 0 2px 0 rgb(255 255 255 / .28);
}
.inv-env-seal-wax::before {
    content: '';
    position: absolute;
    inset: 9px;
    border-radius: 50%;
    border: 1.5px dashed rgb(90 40 8 / .45);
}
.inv-env-seal-icon {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    line-height: 1;
    color: #5a2e0a;
    text-shadow: 0 1px 0 rgb(255 255 255 / .35);
}
.inv-env.is-open .inv-env-seal {
    transform: scale(0.15);
    opacity: 0;
}
.inv-env-front {
    position: absolute;
    inset: 36% 0 0;
    z-index: 20;
    pointer-events: none;
    clip-path: polygon(0 0, 50% 42%, 100% 0, 100% 100%, 0 100%);
    transition: opacity .55s ease .75s;
}
.inv-env.is-open .inv-env-front { opacity: 0; }
.inv-env-letter {
    position: relative;
    z-index: 10;
    transform: translateY(38%);
    filter: brightness(.96);
    transition: transform 1.05s var(--env-ease) .4s, filter .6s ease .4s;
}
.inv-env.is-open .inv-env-letter {
    transform: translateY(0);
    filter: none;
}
.inv-env-hit {
    position: absolute;
    inset: 0;
    z-index: 80;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    padding: 0 1.25rem 1.35rem;
    background: transparent;
    border: 0;
    cursor: pointer;
    pointer-events: auto;
}
.inv-env.is-open .inv-env-hit { display: none; }
.inv-env.is-done {
    pointer-events: none;
}
.inv-env.is-done .inv-env-flap,
.inv-env.is-done .inv-env-front,
.inv-env.is-done .inv-env-seal,
.inv-env.is-done .inv-env-hit,
.inv-env.is-done .inv-env-cta {
    display: none !important;
}
.inv-env-cta {
    pointer-events: none;
    text-align: center;
    animation: inv-env-pulse 2.2s ease-in-out infinite;
}
.inv-env-replay { display: none; }
.inv-env.is-open ~ .inv-env-replay { display: block; }
@keyframes inv-env-pulse {
    0%, 100% { transform: translateY(0); opacity: 1; }
    50% { transform: translateY(-4px); opacity: .85; }
}
@media (prefers-reduced-motion: reduce) {
    .inv-env-shell, .inv-env-flap, .inv-env-letter, .inv-env-front, .inv-env-seal, .inv-env-cta {
        transition: none !important;
        animation: none !important;
    }
    .inv-env .inv-env-shell { max-height: none; overflow: visible; }
    .inv-env .inv-env-flap, .inv-env .inv-env-front, .inv-env .inv-env-hit, .inv-env .inv-env-seal { display: none !important; }
    .inv-env .inv-env-letter { transform: none; filter: none; }
}
</style>
<script>
document.addEventListener('click', function (e) {
    const replay = e.target.closest('.js-replay-envelope');
    if (replay) {
        const stage = replay.closest('.inv-env-stage') || replay.closest('.relative');
        const env = stage ? stage.querySelector('.inv-env') : e.target.closest('.inv-env');
        if (env) {
            env.classList.remove('is-open', 'is-done');
        }
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    const hit = e.target.closest('.inv-env-hit, .js-open-envelope');
    if (!hit) return;
    const env = hit.closest('.inv-env');
    if (!env || env.classList.contains('is-open')) return;
    e.preventDefault();
    e.stopPropagation();
    env.classList.add('is-open');
    window.setTimeout(function () { env.classList.add('is-done'); }, 1400);
}, true);
document.addEventListener('mousedown', function (e) {
    const hit = e.target.closest('.inv-env-hit, .js-open-envelope');
    if (!hit) return;
    const env = hit.closest('.inv-env');
    if (!env || env.classList.contains('is-open')) return;
    e.preventDefault();
    e.stopPropagation();
}, true);
</script>
@endonce
