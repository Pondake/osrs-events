// Puts a video at a time and optionally plays it. A paused video can have its
// media released by the browser, and a seek before it has metadata again is
// dropped, so it waits for the metadata when it has to.
export function seekVideo(el, time, play = false) {
    if (!el) return;
    const apply = () => {
        el.currentTime = time;
        if (play) el.play().catch((error) => console.error(error));
    };
    if (el.readyState >= 1) {
        apply();
    } else {
        el.addEventListener('loadedmetadata', apply, { once: true });
        el.load();
    }
}
