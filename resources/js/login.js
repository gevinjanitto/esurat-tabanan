import gsap from 'gsap';

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const stage = document.querySelector('[data-tilt-stage]');
const $ = (s) => document.querySelector(s);

/* Gerbang 3D Tabanan */
const gateStage = document.querySelector('[data-gate3d]');
if (gateStage) setTimeout(() => import('./gate3d').then(({ mountGate }) => mountGate(gateStage, { ornament: gateStage.dataset.ornament, reduceMotion: reduced })).catch(() => {}), reduced ? 0 : 900);

/* Mouse / gyro tilt */
if (stage && !reduced) {
    const hero = stage.querySelector('[data-tilt]');
    const layers = [...stage.querySelectorAll('[data-depth]')];
    const rotX = gsap.quickTo(hero, 'rotationX', { duration: .8, ease: 'power3.out' });
    const rotY = gsap.quickTo(hero, 'rotationY', { duration: .8, ease: 'power3.out' });
    const movers = layers.map((el) => ({ x: gsap.quickTo(el, 'x', { duration: 1, ease: 'power3.out' }), y: gsap.quickTo(el, 'y', { duration: 1, ease: 'power3.out' }), d: Number(el.dataset.depth) }));
    gsap.set(hero, { transformPerspective: 1100, transformStyle: 'preserve-3d' });
    const apply = (nx, ny) => {
        rotY(nx * 14);
        rotX(-ny * 10);
        movers.forEach((m) => { m.x(nx * m.d * 34); m.y(ny * m.d * 24); });
    };
    window.addEventListener('pointermove', (e) => {
        const r = stage.getBoundingClientRect();
        apply(Math.max(-1, Math.min(1, (e.clientX - (r.left + r.width / 2)) / (r.width / 2))), Math.max(-1, Math.min(1, (e.clientY - (r.top + r.height / 2)) / (r.height / 2))));
    });
    document.addEventListener('pointerleave', () => apply(0, 0));
    window.addEventListener('deviceorientation', (e) => {
        if (e.gamma != null) apply(Math.max(-1, Math.min(1, e.gamma / 30)), Math.max(-1, Math.min(1, (e.beta - 45) / 30)));
    });
    gsap.timeline({ defaults: { ease: 'power4.out' } })
        .from(hero, { y: 80, opacity: 0, rotationY: -20, duration: 1.3 })
        .from('[data-orbit]', { scale: 0, opacity: 0, duration: 1.2, stagger: .1, ease: 'back.out(1.6)' }, '-=1.1')
        .from('[data-bubble]', { scale: 0, transformOrigin: '90% 100%', duration: .6, ease: 'back.out(2)' }, '-=.6')
        .from('[data-login-item]', { y: 26, opacity: 0, duration: .8, stagger: .07, clearProps: 'transform,opacity' }, '-=1.2');
    gsap.to('[data-envelope]', { y: '-=14', rotation: 6, duration: 2.6, yoyo: true, repeat: -1, ease: 'sine.inOut' });
}

/* Character poses */
const poses = {};
document.querySelectorAll('[data-pose]').forEach((img) => (poses[img.dataset.pose] = img));
const character = $('[data-character]');
const bubbleText = $('[data-bubble-text]');
let current = 'typing';
let captchaOk = false;
let pwFocused = false;

function pose(name) {
    if (!poses[name] || name === current) return;
    Object.entries(poses).forEach(([k, img]) => gsap.to(img, { opacity: k === name ? 1 : 0, duration: .35, ease: 'power2.out' }));
    gsap.fromTo(character, { scale: .97 }, { scale: 1, duration: .5, ease: 'back.out(3)' });
    current = name;
    if (character) character.dataset.currentPose = name;
}
function say(text) {
    if (!bubbleText || bubbleText.textContent === text) return;
    gsap.timeline().to('[data-bubble]', { scale: .85, opacity: .4, duration: .12 })
        .add(() => (bubbleText.textContent = text))
        .to('[data-bubble]', { scale: 1, opacity: 1, duration: .45, ease: 'back.out(2.4)' });
}
const restPose = () => (captchaOk ? pose('thumb') : pose('typing'));

/* Typing: bounce + flying keys */
const keys = $('[data-keys]');
function typeBurst(char) {
    if (!character || reduced) return;
    gsap.fromTo(character, { y: 0 }, { y: -4, duration: .07, yoyo: true, repeat: 1, ease: 'power1.inOut' });
    if (!keys) return;
    const k = document.createElement('span');
    k.textContent = char || '•';
    k.className = 'absolute grid place-items-center w-7 h-7 -ml-3.5 -mt-3.5 rounded-lg bg-white text-maroon-800 text-xs font-extrabold shadow-lg ring-1 ring-ruby-900/10';
    keys.appendChild(k);
    gsap.fromTo(k, { x: 0, y: 0, scale: .4, opacity: 1, rotation: 0 }, {
        x: gsap.utils.random(-70, 50), y: gsap.utils.random(-120, -70), rotation: gsap.utils.random(-40, 40), scale: 1, opacity: 0,
        duration: 1, ease: 'power2.out', onComplete: () => k.remove(),
    });
}

const user = $('#username');
const pass = $('#password');
user?.addEventListener('focus', () => { if (!captchaOk) pose('typing'); say('Ketik username kamu ya…'); });
user?.addEventListener('input', (e) => {
    pose('typing');
    typeBurst(e.data ? e.data.slice(-1).toUpperCase() : '⌫');
    const v = user.value.trim();
    say(v ? `Halo, ${v.length > 14 ? v.slice(0, 14) + '…' : v}!` : 'Ketik username kamu ya…');
});
pass?.addEventListener('focus', () => { pwFocused = true; pose('cover'); say('Tenang, saya tutup mata. Tidak mengintip!'); });
pass?.addEventListener('input', () => {
    if (!reduced && character) gsap.fromTo(character, { rotation: 0 }, { rotation: 1.2, duration: .06, yoyo: true, repeat: 1 });
});
pass?.addEventListener('blur', () => {
    pwFocused = false;
    restPose();
    say(captchaOk ? 'Mantap! Silakan tekan Masuk.' : (document.querySelector('.g-recaptcha') ? 'Centang captcha dulu, ya.' : 'Siap, silakan tekan Masuk.'));
});

/* reCAPTCHA callbacks */
function thumbsBurst() {
    const badge = $('[data-thumb-badge]');
    if (badge) gsap.fromTo(badge, { scale: 0, rotation: -40 }, { scale: 1, rotation: 0, duration: .8, ease: 'elastic.out(1, .45)' });
    const burst = $('[data-burst]');
    if (!burst || reduced) return;
    for (let i = 0; i < 14; i++) {
        const dot = document.createElement('span');
        const thumb = i % 3 === 0;
        dot.className = thumb ? 'absolute -ml-4 -mt-4 grid place-items-center w-8 h-8 rounded-full bg-gold-500 text-maroon-950 shadow-lg' : `absolute w-2.5 h-2.5 rounded-full ${['bg-ruby-500', 'bg-gold-400', 'bg-sky-400', 'bg-rose-400'][i % 4]}`;
        if (thumb) dot.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>';
        burst.appendChild(dot);
        const a = (Math.PI * 2 * i) / 14;
        gsap.fromTo(dot, { x: 0, y: 0, scale: 0, opacity: 1 }, { x: Math.cos(a) * gsap.utils.random(60, 120), y: Math.sin(a) * gsap.utils.random(50, 100) - 30, scale: 1, opacity: 0, duration: 1.3, ease: 'power3.out', onComplete: () => dot.remove() });
    }
}
window.onCaptchaOk = () => {
    captchaOk = true;
    if (!pwFocused) pose('thumb');
    say('Mantap! Kamu bukan robot.');
    thumbsBurst();
};
window.onCaptchaExpired = () => {
    captchaOk = false;
    gsap.to('[data-thumb-badge]', { scale: 0, duration: .3 });
    if (!pwFocused) pose('typing');
    say('Captcha kedaluwarsa, centang lagi ya.');
};

/* Misc */
const pw = $('[data-toggle-password]');
pw?.addEventListener('click', () => {
    pass.type = pass.type === 'password' ? 'text' : 'password';
    pw.setAttribute('aria-pressed', pass.type === 'text');
    if (pass.type === 'text') { pose('cover'); say('Password terlihat… saya tetap tutup mata!'); }
});
$('#login-form')?.addEventListener('submit', (e) => {
    const b = e.target.querySelector('button[type=submit]');
    b.disabled = true;
    b.querySelector('[data-label]').textContent = 'Memverifikasi…';
});
