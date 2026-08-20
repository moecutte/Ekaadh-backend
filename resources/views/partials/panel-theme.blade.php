<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: { DEFAULT: '#323891', soft: '#eef0f8', dark: '#262a6d' },
                    gold: { DEFAULT: '#b8892d', soft: '#c9a24d', dark: '#8f6a1e' },
                    ink: '#1a1f3a',
                    mute: '#5d6478',
                    page: '#f4f1ea',
                    navy: '#16183a',
                },
                fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            }
        }
    }
</script>
<style>
    [x-cloak] { display: none !important; }

    body.panel-app {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #1a1f3a;
        background: #f4f1ea;
    }

    .panel-shell { min-height: 100vh; display: flex; }

    .panel-sidebar {
        width: 232px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        color: #e8eaf8;
        background:
            radial-gradient(ellipse 120% 60% at 0% 100%, rgba(184, 137, 45, 0.22), transparent 55%),
            radial-gradient(ellipse 90% 40% at 100% 0%, rgba(80, 96, 210, 0.35), transparent 50%),
            linear-gradient(180deg, #1c2150 0%, #16183a 58%, #12142e 100%);
        box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.06);
    }
    .panel-sidebar nav::-webkit-scrollbar { width: 6px; }
    .panel-sidebar nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.18);
        border-radius: 999px;
    }

    .panel-sidebar-head,
    .panel-sidebar-foot {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    .panel-chip {
        font-size: 9px;
        font-weight: 900;
        letter-spacing: 0.14em;
        padding: 3px 7px;
        border-radius: 999px;
        background: linear-gradient(180deg, #e0bf6a, #b8892d);
        color: #1a1f3a;
        flex-shrink: 0;
    }

    .panel-nav-link {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgba(226, 232, 255, 0.72);
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }
    .panel-nav-link:hover {
        background: rgba(255, 255, 255, 0.07);
        color: #fff;
    }
    .panel-nav-link.is-on {
        color: #fff;
        background: linear-gradient(90deg, rgba(184, 137, 45, 0.28), rgba(255, 255, 255, 0.08));
        box-shadow: inset 3px 0 0 #c9a24d;
    }

    .panel-nav-group {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0.75rem;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(201, 162, 77, 0.7);
    }
    .panel-nav-group:hover { color: #e0bf6a; }

    .panel-nav-ghost {
        color: rgba(226, 232, 255, 0.45);
    }
    .panel-nav-ghost:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.07);
    }

    .panel-logout {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: rgba(226, 232, 255, 0.65);
        transition: background 0.15s ease, color 0.15s ease;
    }
    .panel-logout:hover {
        background: rgba(255, 80, 80, 0.12);
        color: #fecaca;
    }

    .panel-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        position: relative;
        background:
            radial-gradient(ellipse 70% 45% at 0% 0%, rgba(50, 56, 145, 0.12), transparent 55%),
            radial-gradient(ellipse 55% 40% at 100% 0%, rgba(184, 137, 45, 0.16), transparent 50%),
            linear-gradient(180deg, #f8f4ec 0%, #eef0f6 100%);
    }

    .panel-header {
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
        gap: 1rem;
        flex-shrink: 0;
        background: rgba(255, 252, 247, 0.78);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid rgba(50, 56, 145, 0.08);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.7) inset;
    }

    .panel-canvas { flex: 1; overflow-y: auto; padding: 1.5rem; }

    .panel-canvas .bg-white.rounded-xl,
    .panel-canvas .bg-white.rounded-2xl,
    .panel-canvas .bg-white.rounded-3xl {
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.9) inset, 0 14px 36px -16px rgba(26, 31, 58, 0.16);
        border-color: rgba(50, 56, 145, 0.08) !important;
    }

    .panel-hero {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        color: #fff;
        background:
            radial-gradient(ellipse 80% 120% at 100% -20%, rgba(224, 191, 106, 0.35), transparent 55%),
            linear-gradient(120deg, #1c2150 0%, #323891 55%, #4a52b8 100%);
        box-shadow: 0 18px 40px -18px rgba(50, 56, 145, 0.55);
    }
    .panel-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -60px;
        bottom: -90px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    .panel-stat {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #fbfaf6 100%);
        border: 1px solid rgba(50, 56, 145, 0.08);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 14px 36px -16px rgba(26, 31, 58, 0.14);
    }
    .panel-stat::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--stat-accent, #323891);
    }
</style>
