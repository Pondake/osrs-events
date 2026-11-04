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

/** Resolves when the jingle has finished, or the confetti when there is no jingle. */
export function celebrate(display = {}, type = 'BINGO') {
    if (typeof window === 'undefined') return Promise.resolve();

    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const burst = display.celebrate_finish !== false && (!reduced || display.play_when_reduced_motion === true)
        ? confetti()
        : Promise.resolve();
    return display.celebrate_sound !== false ? jingle(JINGLES[type] ?? JINGLES.BINGO) : burst;
}

function confetti() {
    return new Promise((done) => burst(done));
}

function burst(done) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    canvas.dataset.celebration = '';
    Object.assign(canvas.style, { position: 'fixed', inset: '0', width: '100%', height: '100%', pointerEvents: 'none', zIndex: '80' });
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    if (!ctx) {
        canvas.remove();
        done();
        return;
    }
    // 1.5 is plenty for confetti and keeps a phone's canvas a third the size.
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    const w = window.innerWidth;
    const h = window.innerHeight;
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);

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
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.globalAlpha = t > DURATION - FADE ? Math.max(0, (DURATION - t) / FADE) : 1;
        for (const p of pieces) {
            if (t < p.delay) continue;
            p.vy += 1400 * dt * p.weight;
            p.vx *= 1 - 0.9 * dt;
            p.vy *= 1 - 0.6 * dt;
            p.x += (p.vx + Math.sin(t / 180 + p.turn) * 30 * p.flutter) * dt;
            p.y += p.vy * dt;
            p.turn += p.spin * dt;
            // Gone below the screen for good: nothing to draw.
            if (p.vy > 0 && p.y > h + 30) continue;
            const cos = Math.cos(p.turn) * dpr;
            const sin = Math.sin(p.turn) * dpr;
            ctx.setTransform(cos, sin, -sin, cos, p.x * dpr, p.y * dpr);
            ctx.fillStyle = p.color;
            if (p.round) {
                ctx.beginPath();
                ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillRect(-p.size / 2, -p.size / 4, p.size, (p.size / 2) * Math.abs(cos / dpr) + 1);
            }
        }
        if (t < DURATION) {
            requestAnimationFrame(frame);
        } else {
            canvas.remove();
            done();
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
    if (navigator.userActivation && !navigator.userActivation.hasBeenActive) return Promise.resolve();
    const audio = new Audio(url);
    audio.volume = 0.6;
    return new Promise((done) => {
        audio.addEventListener('ended', done, { once: true });
        audio.addEventListener('error', done, { once: true });
        audio.play().catch((error) => {
            console.error(error);
            done();
        });
    });
}
