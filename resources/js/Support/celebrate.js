// A short confetti burst for the moment your own run finishes an event.
//
// Its own canvas on <body>, above every dialog, with pointer-events off so it
// never sits between a person and the button they were about to press. It
// removes itself when it is done, and does nothing at all for someone who
// asked for reduced motion or on the server.

const COLORS = ['#fbbf24', '#f59e0b', '#fde68a', '#22c55e', '#86efac', '#ffffff'];
const DURATION = 2600;
const FADE = 700;

export function celebrate({ count = 160 } = {}) {
    if (typeof window === 'undefined') return;
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;

    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    canvas.dataset.celebration = '';
    Object.assign(canvas.style, { position: 'fixed', inset: '0', width: '100%', height: '100%', pointerEvents: 'none', zIndex: '80' });
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        canvas.remove();
        return;
    }
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const w = window.innerWidth;
    const h = window.innerHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    ctx.scale(dpr, dpr);

    // Two cannons in the bottom corners, aimed up and inwards.
    const pieces = Array.from({ length: count }, (_, i) => {
        const left = i % 2 === 0;
        const angle = (left ? -1 : 1) * (0.15 + Math.random() * 0.5);
        const speed = (0.6 + Math.random() * 0.4) * Math.max(h, 500) * 1.7;
        return {
            x: left ? -10 : w + 10,
            y: h + 10,
            vx: Math.sin(angle) * speed * -1,
            vy: -Math.cos(angle) * speed,
            size: 6 + Math.random() * 7,
            spin: (Math.random() - 0.5) * 12,
            turn: Math.random() * Math.PI,
            round: Math.random() < 0.3,
            color: COLORS[i % COLORS.length],
        };
    });

    const start = performance.now();
    let last = start;

    function frame(now) {
        const t = now - start;
        const dt = Math.min(0.05, (now - last) / 1000);
        last = now;
        ctx.clearRect(0, 0, w, h);
        ctx.globalAlpha = t > DURATION - FADE ? Math.max(0, (DURATION - t) / FADE) : 1;
        for (const p of pieces) {
            p.vy += 1400 * dt;
            p.vx *= 1 - 0.9 * dt;
            p.vy *= 1 - 0.6 * dt;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            p.turn += p.spin * dt;
            ctx.fillStyle = p.color;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.turn);
            if (p.round) {
                ctx.beginPath();
                ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2 * Math.abs(Math.cos(p.turn)) + 1);
            }
            ctx.restore();
        }
        if (t < DURATION) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
        }
    }
    requestAnimationFrame(frame);
}
