// The moment your own run finishes an event: confetti and a short jingle.
//
// Its own canvas on <body>, above every dialog, with pointer-events off so it
// never sits between a person and the button they were about to press. It
// removes itself when it is done. Both halves follow the account's display
// preferences (App\Support\DisplayPreference): the confetti is motion, so the
// OS reduced-motion setting stills it unless the account plays animations
// anyway; the jingle is not motion and has its own switch.
//
// One jingle per event type. They are original pieces rendered with the
// FluidR3_GM soundfont (CC BY 3.0, credited on the About page), not game
// audio: the Fan Content Policy does not cover sound effects.

const COLORS = ['#fbbf24', '#f59e0b', '#fde68a', '#22c55e', '#86efac', '#ffffff'];
const DURATION = 3400;
const FADE = 800;

export const JINGLES = {
    SNAKES_LADDERS: '/sounds/finish-quest-f57e76cc.m4a',
    BINGO: '/sounds/finish-medal-a0467a91.m4a',
    SKILL_RACE: '/sounds/finish-triumph-e2ebbc89.m4a',
    DROP_RACE: '/sounds/finish-boss-2a3a6e07.m4a',
};

export function celebrate(display = {}, type = 'BINGO') {
    if (typeof window === 'undefined') return;

    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (display.celebrate_finish !== false && (!reduced || display.play_when_reduced_motion === true)) {
        confetti();
    }
    if (display.celebrate_sound !== false) {
        jingle(JINGLES[type] ?? JINGLES.BINGO);
    }
}

function confetti() {
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

    // Two volleys from the bottom corners, the second a beat later, then a
    // drizzle from the top while the first ones come down.
    const pieces = [];
    const volley = (count, delay) => {
        for (let i = 0; i < count; i++) {
            const left = i % 2 === 0;
            const angle = (left ? -1 : 1) * (0.15 + Math.random() * 0.5);
            const speed = (0.6 + Math.random() * 0.4) * Math.max(h, 500) * 1.7;
            pieces.push(piece(left ? -10 : w + 10, h + 10, -Math.sin(angle) * speed, -Math.cos(angle) * speed, delay, i));
        }
    };
    volley(140, 0);
    volley(110, 380);
    for (let i = 0; i < 90; i++) {
        pieces.push(piece(Math.random() * w, -20 - Math.random() * 120, (Math.random() - 0.5) * 120, 80 + Math.random() * 160, 700 + Math.random() * 900, i));
    }

    const start = performance.now();
    let last = start;

    function frame(now) {
        const t = now - start;
        const dt = Math.min(0.05, (now - last) / 1000);
        last = now;
        ctx.clearRect(0, 0, w, h);
        ctx.globalAlpha = t > DURATION - FADE ? Math.max(0, (DURATION - t) / FADE) : 1;
        for (const p of pieces) {
            if (t < p.delay) continue;
            p.vy += 1400 * dt * p.weight;
            p.vx *= 1 - 0.9 * dt;
            p.vy *= 1 - 0.6 * dt;
            p.x += (p.vx + Math.sin(t / 180 + p.turn) * 30 * p.flutter) * dt;
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
                ctx.fillRect(-p.size / 2, -p.size / 4, p.size, (p.size / 2) * Math.abs(Math.cos(p.turn)) + 1);
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

function piece(x, y, vx, vy, delay, i) {
    const falling = vy > 0;
    return {
        x, y, vx, vy, delay,
        size: 6 + Math.random() * 8,
        spin: (Math.random() - 0.5) * 12,
        turn: Math.random() * Math.PI,
        round: Math.random() < 0.3,
        weight: falling ? 0.12 : 1,
        flutter: falling ? 1 : 0.3,
        color: COLORS[i % COLORS.length],
    };
}

// Browsers refuse sound before the page has had a click or a key press, so it
// is not even tried then; the confetti still runs.
function jingle(url) {
    if (navigator.userActivation && !navigator.userActivation.hasBeenActive) return;
    const audio = new Audio(url);
    audio.volume = 0.6;
    audio.play().catch((error) => console.error(error));
}
