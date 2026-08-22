@include('invitations.partials.invitation-fonts')
@once
<style>
.invite-motion { --inv-ease: cubic-bezier(.22,1,.36,1); }
.invite-motion .inv-rise { animation: inv-rise .9s var(--inv-ease) both; }
.invite-motion .inv-rise-2 { animation: inv-rise 1s var(--inv-ease) .12s both; }
.invite-motion .inv-rise-3 { animation: inv-rise 1.05s var(--inv-ease) .22s both; }
.invite-motion .inv-rise-4 { animation: inv-rise 1.1s var(--inv-ease) .34s both; }
.invite-motion .inv-float { animation: inv-float 5.5s ease-in-out infinite; }
.invite-motion .inv-pulse { animation: inv-pulse 2.8s ease-in-out infinite; }
.invite-motion .inv-spin-slow { animation: inv-spin 28s linear infinite; }
.invite-motion .inv-shimmer-text {
    background-size: 220% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: inv-shimmer 5s linear infinite;
}
.invite-motion .inv-foil {
    background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,.45) 45%, transparent 70%);
    background-size: 220% 100%;
    animation: inv-foil 6.5s ease-in-out infinite;
    pointer-events: none;
}
.invite-motion .inv-glow { animation: inv-glow 4s ease-in-out infinite; }
.invite-motion .inv-twinkle { animation: inv-twinkle 2.4s ease-in-out infinite; }
.invite-motion .inv-sway { animation: inv-sway 7s ease-in-out infinite; }
.invite-motion .inv-draw { stroke-dasharray: 240; stroke-dashoffset: 240; animation: inv-draw 3.2s var(--inv-ease) .4s forwards; }

@keyframes inv-rise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: none; } }
@keyframes inv-float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
@keyframes inv-pulse { 0%,100% { transform: scale(1); opacity: .9; } 50% { transform: scale(1.08); opacity: 1; } }
@keyframes inv-spin { to { transform: rotate(360deg); } }
@keyframes inv-shimmer { 0% { background-position: 0% center; } 100% { background-position: 220% center; } }
@keyframes inv-foil { 0% { background-position: 120% 0; } 100% { background-position: -40% 0; } }
@keyframes inv-glow { 0%,100% { opacity: .35; } 50% { opacity: .8; } }
@keyframes inv-twinkle { 0%,100% { opacity: .15; transform: scale(.7); } 50% { opacity: 1; transform: scale(1); } }
@keyframes inv-sway { 0%,100% { transform: translateY(0) rotate(-4deg); } 50% { transform: translateY(-14px) rotate(5deg); } }
@keyframes inv-draw { to { stroke-dashoffset: 0; } }
@keyframes inv-fall {
    0% { transform: translate3d(0,-12%,0) rotate(0deg); opacity: 0; }
    12% { opacity: .55; }
    100% { transform: translate3d(18px,118%,0) rotate(280deg); opacity: 0; }
}
@keyframes inv-lantern {
    0% { transform: translateY(8%) scale(.92); opacity: 0; }
    18% { opacity: .85; }
    100% { transform: translateY(-118%) scale(1.05); opacity: 0; }
}
@keyframes inv-aurora {
    0%,100% { transform: translateX(-8%) scale(1); opacity: .45; }
    50% { transform: translateX(10%) scale(1.08); opacity: .8; }
}

.invite-motion .inv-petal {
    position: absolute; top: -8%; font-size: 14px; line-height: 1;
    animation: inv-fall linear infinite; pointer-events: none; opacity: 0;
}
.invite-motion .inv-star {
    position: absolute; width: 3px; height: 3px; border-radius: 50%;
    background: #fff; box-shadow: 0 0 6px #fff8; pointer-events: none;
}
.invite-motion .inv-lantern {
    position: absolute; bottom: -8%; pointer-events: none;
    animation: inv-lantern linear infinite;
}

@media (prefers-reduced-motion: reduce), print {
    .invite-motion, .invite-motion * {
        animation: none !important;
        transition: none !important;
    }
    .invite-motion .inv-draw { stroke-dashoffset: 0; }
    .invite-motion .inv-shimmer-text { -webkit-text-fill-color: currentColor; background: none; }
}
.invite-capture, .invite-capture * {
    animation: none !important;
}
</style>
@endonce
