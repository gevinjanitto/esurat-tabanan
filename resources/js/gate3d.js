import * as THREE from 'three';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';

// Pura Tanah Lot (karang laut + meru bertingkat + ombak), dibangun prosedural dengan material PBR.
const rnd = (a, b) => a + Math.random() * (b - a);
const mulberry = (s) => () => { s = (s + 0x6d2b79f5) | 0; let t = Math.imul(s ^ (s >>> 15), 1 | s); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
const srand = mulberry(20240611);
const sr = (a, b) => a + srand() * (b - a);
const fract = (v) => v - Math.floor(v);
const hash = (x, y, z) => fract(Math.sin(x * 127.1 + y * 311.7 + z * 74.7) * 43758.5453);
const lerp = (a, b, t) => a + (b - a) * t;
const smooth = (e0, e1, x) => { const t = Math.min(1, Math.max(0, (x - e0) / (e1 - e0))); return t * t * (3 - 2 * t); };
const SX = 1.18, SZ = 0.88;

function noise3(x, y, z) {
    const ix = Math.floor(x), iy = Math.floor(y), iz = Math.floor(z);
    const fx = x - ix, fy = y - iy, fz = z - iz;
    const u = fx * fx * (3 - 2 * fx), v = fy * fy * (3 - 2 * fy), w = fz * fz * (3 - 2 * fz);
    const h = (a, b, c) => hash(ix + a, iy + b, iz + c);
    return lerp(lerp(lerp(h(0, 0, 0), h(1, 0, 0), u), lerp(h(0, 1, 0), h(1, 1, 0), u), v),
        lerp(lerp(h(0, 0, 1), h(1, 0, 1), u), lerp(h(0, 1, 1), h(1, 1, 1), u), v), w);
}
function fbm(x, y, z, oct = 4) {
    let s = 0, a = 0.5, f = 1;
    for (let i = 0; i < oct; i++) { s += a * (noise3(x * f, y * f, z * f) * 2 - 1); f *= 2.07; a *= 0.5; }
    return s;
}

function canvasTex(size, draw, srgb = true, repeat = [1, 1]) {
    const c = document.createElement('canvas');
    c.width = c.height = size;
    draw(c.getContext('2d'), size);
    const t = new THREE.CanvasTexture(c);
    t.wrapS = t.wrapT = THREE.RepeatWrapping;
    t.repeat.set(...repeat);
    t.anisotropy = 8;
    if (srgb) t.colorSpace = THREE.SRGBColorSpace;
    return t;
}

function textures() {
    const rockBump = canvasTex(512, (g, s) => {
        g.fillStyle = '#808080'; g.fillRect(0, 0, s, s);
        for (let i = 0; i < 70; i++) { g.fillStyle = `rgba(${Math.random() < 0.5 ? '0,0,0' : '255,255,255'},${rnd(0.05, 0.2)})`; g.fillRect(0, rnd(0, s), s, rnd(1, 6)); }
        for (let i = 0; i < 16000; i++) { g.fillStyle = `rgba(${Math.random() < 0.5 ? '0,0,0' : '255,255,255'},${rnd(0.06, 0.3)})`; g.beginPath(); g.arc(rnd(0, s), rnd(0, s), rnd(0.5, 3), 0, 7); g.fill(); }
    }, false, [9, 4]);
    const thatch = canvasTex(256, (g, s) => {
        g.fillStyle = '#211a16'; g.fillRect(0, 0, s, s);
        for (let i = 0; i < 2200; i++) { const l = rnd(14, 52) | 0; g.strokeStyle = `rgba(${l + 6},${l},${l - 4},${rnd(0.4, 0.9)})`; g.lineWidth = rnd(0.6, 1.6); const x = rnd(0, s), y = rnd(0, s); g.beginPath(); g.moveTo(x, y); g.lineTo(x + rnd(-2, 2), y + rnd(10, 34)); g.stroke(); }
    }, true, [3, 2]);
    const stone = canvasTex(256, (g, s) => {
        g.fillStyle = '#9a9086'; g.fillRect(0, 0, s, s);
        for (let i = 0; i < 12000; i++) { g.fillStyle = `rgba(${Math.random() < 0.5 ? '60,55,50' : '235,228,215'},${rnd(0.05, 0.16)})`; g.beginPath(); g.arc(rnd(0, s), rnd(0, s), rnd(0.5, 2.2), 0, 7); g.fill(); }
        for (let i = 0; i < 30; i++) { g.fillStyle = `rgba(60,80,45,${rnd(0.04, 0.1)})`; g.beginPath(); g.arc(rnd(0, s), rnd(0, s), rnd(8, 40), 0, 7); g.fill(); }
    });
    const brick = canvasTex(256, (g, s) => {
        g.fillStyle = '#5a3a2c'; g.fillRect(0, 0, s, s);
        const rows = 16, bw = 32, bh = s / rows;
        for (let r = 0; r < rows; r++) for (let x = -bw; x < s + bw; x += bw) { g.fillStyle = `hsl(${rnd(10, 20)}, ${rnd(35, 48)}%, ${rnd(30, 40)}%)`; g.fillRect(x + (r % 2 ? bw / 2 : 0) + 1, r * bh + 1, bw - 2, bh - 2); }
        for (let i = 0; i < 4000; i++) { g.fillStyle = `rgba(0,0,0,${rnd(0.03, 0.1)})`; g.fillRect(rnd(0, s), rnd(0, s), 2, 2); }
    }, true, [2, 2]);
    const leaf = canvasTex(256, (g, s) => {
        g.fillStyle = '#909090'; g.fillRect(0, 0, s, s);
        for (let i = 0; i < 1400; i++) { g.fillStyle = `rgba(${Math.random() < 0.5 ? '0,0,0' : '255,255,255'},${rnd(0.15, 0.45)})`; g.beginPath(); g.ellipse(rnd(0, s), rnd(0, s), rnd(2, 6), rnd(1, 3), rnd(0, 3), 0, 7); g.fill(); }
    }, false, [2, 2]);
    const frond = canvasTex(256, (g, s) => {
        g.strokeStyle = '#fff'; g.lineCap = 'round';
        g.lineWidth = 5; g.beginPath(); g.moveTo(0, s / 2); g.lineTo(s, s / 2); g.stroke();
        for (let x = 6; x < s - 4; x += 7) {
            const len = (s / 2 - 8) * Math.sin(Math.PI * (0.15 + 0.85 * x / s)) ;
            g.lineWidth = 3.2;
            for (const d of [-1, 1]) { g.beginPath(); g.moveTo(x, s / 2); g.lineTo(x + len * 0.45, s / 2 + d * len); g.stroke(); }
        }
    }, false);
    const foam = canvasTex(512, (g, s) => {
        const c = s / 2;
        for (let i = 0; i < 3200; i++) {
            const a = rnd(0, Math.PI * 2), q = Math.pow(Math.random(), 1.7), r = c * (0.6 + q * 0.38);
            g.fillStyle = `rgba(255,255,255,${rnd(0.05, 0.32) * (1.25 - q)})`;
            g.beginPath(); g.ellipse(c + Math.cos(a) * r, c + Math.sin(a) * r, rnd(1.5, 7), rnd(1, 3), a + Math.PI / 2, 0, 7); g.fill();
        }
    });
    foam.wrapS = foam.wrapT = THREE.ClampToEdgeWrapping;
    foam.center.set(0.5, 0.5);
    const seaAlpha = canvasTex(256, (g, s) => {
        const grd = g.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
        grd.addColorStop(0, '#fff'); grd.addColorStop(0.55, '#fff'); grd.addColorStop(0.8, '#000'); grd.addColorStop(1, '#000');
        g.fillStyle = grd; g.fillRect(0, 0, s, s);
    }, false);
    return { rockBump, thatch, stone, brick, leaf, frond, foam, seaAlpha };
}

function rockGeometry() {
    const prof = [[3.3, -0.45], [3.42, -0.2], [3.25, 0.35], [3.2, 1], [3.1, 1.7], [2.95, 2.35], [2.72, 2.85], [2.38, 3.18], [1.7, 3.36], [0.8, 3.44], [0.001, 3.47]];
    const pts = new THREE.SplineCurve(prof.map(([r, y]) => new THREE.Vector2(r, y))).getPoints(90);
    const geo = new THREE.LatheGeometry(pts, 200);
    const p = geo.attributes.position, v = new THREE.Vector3();
    for (let i = 0; i < p.count; i++) {
        v.fromBufferAttribute(p, i);
        const r = Math.hypot(v.x, v.z), ux = r > 1e-4 ? v.x / r : 0, uz = r > 1e-4 ? v.z / r : 0;
        const n = fbm(v.x * 0.42 + 3, v.y * 0.42, v.z * 0.42, 5);
        const strata = Math.sin(v.y * 7 + fbm(v.x * 0.7, v.y * 0.2, v.z * 0.7, 3) * 4) * 0.05;
        const notch = -0.45 * Math.exp(-Math.pow((v.y - 0.15) / 0.45, 2)) * (0.6 + 0.4 * noise3(v.x * 0.8, 0, v.z * 0.8));
        const ridge = (1 - Math.abs(fbm(v.x * 1.1 + 9, v.y * 0.9, v.z * 1.1, 4))) * 0.32 - 0.2;
        const groove = fbm(v.x * 1.9, v.y * 0.22, v.z * 1.9, 3) * 0.34;
        const nr = Math.max(0, r + (n * 0.85 + ridge + groove + strata + notch) * smooth(0.3, 1.8, r));
        const y = v.y + (v.y > 2.6 ? fbm(v.x * 0.9, 7, v.z * 0.9, 3) * 0.22 * smooth(2.6, 3.3, v.y) : 0);
        p.setXYZ(i, ux * nr * SX, y, uz * nr * SZ);
    }
    geo.computeVertexNormals();
    const nrm = geo.attributes.normal, cols = new Float32Array(p.count * 3), c = new THREE.Color();
    const A = new THREE.Color('#5f5045'), B = new THREE.Color('#382e28'), L = new THREE.Color('#8e7d68'), W = new THREE.Color('#211b17'), M = new THREE.Color('#566f2d'), G = new THREE.Color('#4a7426');
    for (let i = 0; i < p.count; i++) {
        const x = p.getX(i), y = p.getY(i), z = p.getZ(i), ny = nrm.getY(i);
        c.copy(A).lerp(B, noise3(x * 1.3, y * 1.3, z * 1.3) * 0.7 + smooth(0.1, -0.5, nrm.getX(i) * 0.5 + nrm.getZ(i) * 0.5) * 0.3);
        c.lerp(L, (0.5 + 0.5 * Math.sin(y * 9 + fbm(x, y, z, 2) * 3)) * 0.2);
        c.lerp(W, smooth(0.9, -0.3, y) * 0.85);
        c.lerp(W, smooth(0.05, -0.25, fbm(x * 1.9 / SX, y * 0.22, z * 1.9 / SZ, 3)) * 0.55);
        c.lerp(M, smooth(1.3, 2.4, y) * smooth(0.05, 0.3, fbm(x * 0.9, y * 0.35, z * 0.9, 3)) * 0.85);
        c.lerp(G, smooth(0.35, 0.7, ny) * smooth(1.6, 2.7, y));
        cols.set([c.r, c.g, c.b], i * 3);
    }
    geo.setAttribute('color', new THREE.BufferAttribute(cols, 3));
    return geo;
}

function blobGeo(off, detail = 3) {
    const g = new THREE.IcosahedronGeometry(1, detail), p = g.attributes.position, v = new THREE.Vector3();
    for (let i = 0; i < p.count; i++) {
        v.fromBufferAttribute(p, i);
        const k = 1 + fbm(v.x * 1.6 + off, v.y * 1.6, v.z * 1.6, 3) * 0.5;
        p.setXYZ(i, v.x * k, v.y * k * 0.8, v.z * k);
    }
    g.computeVertexNormals();
    return g;
}

function frondGeo() {
    const g = new THREE.PlaneGeometry(1.55, 0.62, 12, 2).translate(0.775, 0, 0).rotateX(-Math.PI / 2);
    const p = g.attributes.position;
    for (let i = 0; i < p.count; i++) { const x = p.getX(i), z = p.getZ(i); p.setY(i, -x * x * 0.32 - Math.abs(z) * 0.35); }
    g.computeVertexNormals();
    return g;
}

function buildTanahLot(tex) {
    const root = new THREE.Group();
    const parts = [];
    const M = {
        rock: new THREE.MeshStandardMaterial({ vertexColors: true, bumpMap: tex.rockBump, bumpScale: 3, roughness: 0.94, envMapIntensity: 0.6 }),
        wetRock: new THREE.MeshStandardMaterial({ color: '#3a3029', bumpMap: tex.rockBump, bumpScale: 2, roughness: 0.55, envMapIntensity: 0.9 }),
        paras: new THREE.MeshStandardMaterial({ map: tex.stone, roughness: 0.88, envMapIntensity: 0.7 }),
        parasDark: new THREE.MeshStandardMaterial({ color: '#5e5852', roughness: 0.9 }),
        brick: new THREE.MeshStandardMaterial({ map: tex.brick, roughness: 0.9, envMapIntensity: 0.6 }),
        thatch: new THREE.MeshStandardMaterial({ map: tex.thatch, bumpMap: tex.thatch, bumpScale: 2, roughness: 1, flatShading: true }),
        wood: new THREE.MeshStandardMaterial({ color: '#5b2a1c', roughness: 0.55, metalness: 0.1 }),
        gold: new THREE.MeshStandardMaterial({ color: '#c99a45', roughness: 0.35, metalness: 0.75 }),
        trunk: new THREE.MeshStandardMaterial({ color: '#6b5642', roughness: 0.95 }),
        frond: new THREE.MeshStandardMaterial({ color: '#4e7f2a', alphaMap: tex.frond, alphaTest: 0.5, side: THREE.DoubleSide, roughness: 0.75 }),
        leaves: ['#3f6b25', '#4f7d2b', '#2f5520', '#5f8a35'].map((c) => new THREE.MeshStandardMaterial({ color: c, bumpMap: tex.leaf, bumpScale: 2, roughness: 0.85 })),
    };
    const track = (parent, m, x, y, z, delay, shadow = true) => {
        m.position.set(x, y, z);
        m.castShadow = shadow; m.receiveShadow = true;
        m.userData.delay = delay;
        m.scale.setScalar(0.001);
        parent.add(m); parts.push(m);
        return m;
    };
    const add = (parent, geo, mat, x, y, z, delay, shadow) => track(parent, new THREE.Mesh(geo, mat), x, y, z, delay, shadow);
    const box = (w, h, d) => new THREE.BoxGeometry(w, h, d);

    const rockGeo = rockGeometry();
    const probe = new THREE.Mesh(rockGeo, new THREE.MeshBasicMaterial({ side: THREE.DoubleSide }));
    probe.updateMatrixWorld();
    const ray = new THREE.Raycaster();
    const down = new THREE.Vector3(0, -1, 0);
    const surfY = (x, z) => { ray.set(new THREE.Vector3(x, 12, z), down); const h = ray.intersectObject(probe)[0]; return h ? h.point.y : 3.3; };
    const topY = (x, z, r) => Math.max(...[[0, 0], [r, r], [-r, r], [r, -r], [-r, -r]].map(([dx, dz]) => surfY(x + dx, z + dz)));
    track(root, new THREE.Mesh(rockGeo, M.rock), 0, 0, 0, 0);

    [[4.5, 1.7, 0.75], [-4.7, 1.0, 0.6], [3.9, -2.3, 0.55], [-3.1, 2.9, 0.42], [1.9, 3.35, 0.32], [-4.1, -1.9, 0.5]].forEach(([x, z, s], i) => {
        const m = add(root, blobGeo(i * 3.1, 4), M.wetRock, x, -0.05, z, 0.15 + i * 0.05);
        m.userData.s = [s * 1.2, s * 0.9, s];
    });

    const bushGeos = [0, 1, 2].map((k) => blobGeo(10 + k * 4.3, 3));
    const bush = (parent, x, y, z, s, delay, sy = 0.8) => {
        const m = add(parent, bushGeos[(srand() * 3) | 0], M.leaves[(srand() * 4) | 0], x, y, z, delay);
        m.userData.s = [s, s * sy, s];
        m.rotation.y = sr(0, 6.3);
        return m;
    };

    const inTemple = (x, z) => x > -1.75 && x < 1.85 && z > -1.65 && z < 1.05;
    for (let i = 0, n = 0; i < 600 && n < 80; i++) {
        const a = sr(0, Math.PI * 2), r = Math.sqrt(srand()) * 2.55, x = Math.cos(a) * r * SX, z = Math.sin(a) * r * SZ;
        if (inTemple(x, z)) continue;
        const y = surfY(x, z);
        if (y < 2.85) continue;
        bush(root, x, y + 0.05, z, sr(0.2, 0.46), 0.55 + n * 0.008); n++;
    }
    const normal = new THREE.Vector3();
    for (let i = 0; i < 130; i++) {
        const a = sr(-0.3, Math.PI + 0.3) + (i % 3 === 0 ? Math.PI : 0), y = sr(1.5, 3.05);
        ray.set(new THREE.Vector3(Math.cos(a) * 9, y, Math.sin(a) * 9), new THREE.Vector3(-Math.cos(a), 0, -Math.sin(a)));
        const h = ray.intersectObject(probe)[0];
        if (!h) continue;
        normal.copy(h.face.normal);
        if (normal.y < -0.2) continue;
        const s = sr(0.16, 0.42) * smooth(1.2, 2.5, y);
        bush(root, h.point.x - normal.x * s * 0.3, h.point.y, h.point.z - normal.z * s * 0.3, Math.max(0.08, s), 0.45 + i * 0.004, sr(1.1, 1.9));
    }

    const ornate = tex.panelMat;
    const meru = (x, z, tiers, s, delay) => {
        const g = new THREE.Group();
        g.position.set(x, topY(x, z, 0.72 * s), z); g.scale.setScalar(s);
        root.add(g);
        add(g, box(1.5, 0.72, 1.5), M.paras, 0, -0.2, 0, delay);
        add(g, box(1.6, 0.08, 1.6), M.parasDark, 0, 0.18, 0, delay + 0.03);
        add(g, box(0.46, 0.2, 0.36), M.paras, 0, 0.06, 0.88, delay + 0.05);
        add(g, box(1.05, 0.6, 1.05), M.brick, 0, 0.52, 0, delay + 0.08);
        add(g, box(0.34, 0.44, 0.04), ornate, 0, 0.48, 0.54, delay + 0.14, false);
        for (const [sx, sz] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) add(g, box(0.13, 0.62, 0.13), M.paras, sx * 0.52, 0.52, sz * 0.52, delay + 0.1);
        add(g, box(1.2, 0.07, 1.2), M.paras, 0, 0.855, 0, delay + 0.16);
        let y = 0.89;
        for (let i = 0; i < tiers; i++) {
            const f = tiers > 1 ? i / (tiers - 1) : 0, w = lerp(1.8, 0.62, f), rh = 0.2, dl = delay + 0.2 + i * 0.09;
            add(g, box(w * 0.4, 0.12, w * 0.4), M.wood, 0, y + 0.06, 0, dl);
            y += 0.12;
            add(g, new THREE.CylinderGeometry(w * 0.34, w / Math.SQRT2, rh, 4, 1).rotateY(Math.PI / 4), M.thatch, 0, y + rh / 2, 0, dl + 0.03);
            y += rh;
        }
        add(g, new THREE.CylinderGeometry(0.05, 0.12, 0.12, 8), M.paras, 0, y + 0.06, 0, delay + 0.3 + tiers * 0.09);
        add(g, new THREE.ConeGeometry(0.06, 0.38, 10), M.gold, 0, y + 0.31, 0, delay + 0.34 + tiers * 0.09);
        return g;
    };
    meru(-0.55, -0.6, 5, 1, 0.85);
    meru(0.98, -0.72, 3, 0.78, 1.0);

    const bale = (x, z, delay) => {
        const g = new THREE.Group();
        g.position.set(x, topY(x, z, 0.4), z);
        root.add(g);
        add(g, box(0.95, 0.55, 0.72), M.paras, 0, -0.1, 0, delay);
        for (const [sx, sz] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) add(g, new THREE.CylinderGeometry(0.03, 0.03, 0.45, 8), M.wood, sx * 0.38, 0.4, sz * 0.26, delay + 0.06);
        add(g, new THREE.CylinderGeometry(0.22, 0.82, 0.32, 4, 1).rotateY(Math.PI / 4).scale(1, 1, 0.8), M.thatch, 0, 0.78, 0, delay + 0.12);
    };
    bale(1.0, 0.42, 1.1);

    const wallPiece = (x, z, len, rotY, delay) => {
        const y = surfY(x, z);
        const a = add(root, box(len, 0.6, 0.15), M.brick, x, y, z, delay); a.rotation.y = rotY;
        const b = add(root, box(len + 0.02, 0.06, 0.21), M.paras, x, y + 0.32, z, delay + 0.03); b.rotation.y = rotY;
    };
    const X0 = -1.7, X1 = 1.8, Z0 = -1.6, Z1 = 1.0, seg = 0.44;
    for (let x = X0 + seg / 2; x < X1; x += seg) {
        wallPiece(x, Z0, seg, 0, 0.7 + (x - X0) * 0.03);
        if (Math.abs(x) > 0.36) wallPiece(x, Z1, seg, 0, 0.7 + (x - X0) * 0.03);
    }
    for (let z = Z0 + seg / 2; z < Z1; z += seg) { wallPiece(X0, z, seg, Math.PI / 2, 0.75 + (z - Z0) * 0.03); wallPiece(X1, z, seg, Math.PI / 2, 0.75 + (z - Z0) * 0.03); }

    const tree = (x, z, h, delay) => {
        const y = surfY(x, z);
        const t = add(root, new THREE.CylinderGeometry(0.06, 0.11, h, 8).translate(0, h / 2, 0), M.trunk, x, y - 0.05, z, delay);
        t.rotation.z = sr(-0.12, 0.12);
        for (let k = 0; k < 5; k++) bush(root, x + sr(-0.45, 0.45), y + h + sr(-0.25, 0.25), z + sr(-0.4, 0.4), sr(0.38, 0.6), delay + 0.1 + k * 0.03, 0.75);
    };
    const fGeo = frondGeo();
    const palm = (x, z, h, lean, dir, delay) => {
        const g = new THREE.Group();
        g.position.set(x, surfY(x, z) - 0.05, z); g.rotation.y = dir;
        root.add(g);
        const curve = new THREE.CatmullRomCurve3([new THREE.Vector3(0, 0, 0), new THREE.Vector3(lean * 0.2, h * 0.4, 0), new THREE.Vector3(lean * 0.65, h * 0.78, 0), new THREE.Vector3(lean, h, 0)]);
        add(g, new THREE.TubeGeometry(curve, 24, 0.055, 8), M.trunk, 0, 0, 0, delay);
        const crown = new THREE.Group();
        crown.position.set(lean, h, 0);
        g.add(crown);
        for (let k = 0; k < 10; k++) {
            const f = add(crown, fGeo, M.frond, 0, 0, 0, delay + 0.12 + k * 0.025);
            f.rotation.order = 'YZX';
            f.rotation.set(sr(-0.3, 0.3), (k / 10) * Math.PI * 2 + sr(-0.2, 0.2), sr(-0.2, 0.45));
        }
    };
    tree(-2.45, 0.35, 1.25, 1.05);
    tree(2.5, -0.35, 1.05, 1.1);
    tree(-1.55, -1.95, 1.5, 1.15);
    tree(1.75, -1.85, 1.2, 1.2);
    tree(-2.15, 1.35, 0.85, 1.25);
    palm(2.3, 1.05, 1.9, 0.55, -0.5, 1.15);
    palm(-2.75, -0.6, 2.2, 0.7, 2.7, 1.25);
    palm(0.55, -1.95, 2.4, 0.35, 1.6, 1.3);

    return { root, parts };
}

function buildSea(tex, segs) {
    const geo = new THREE.PlaneGeometry(16, 16, segs, segs).rotateX(-Math.PI / 2);
    const n = geo.attributes.position.count;
    geo.setAttribute('color', new THREE.BufferAttribute(new Float32Array(n * 3), 3));
    const base = Float32Array.from(geo.attributes.position.array);
    const mesh = new THREE.Mesh(geo, new THREE.MeshStandardMaterial({ vertexColors: true, roughness: 0.14, metalness: 0.05, transparent: true, alphaMap: tex.seaAlpha, envMapIntensity: 1.4, depthWrite: false }));
    mesh.receiveShadow = true;
    const deep = new THREE.Color('#0a4d72'), shallow = new THREE.Color('#167f8e'), foam = new THREE.Color('#f3fbfa'), c = new THREE.Color();
    const update = (t) => {
        const p = geo.attributes.position.array, col = geo.attributes.color.array;
        for (let i = 0; i < n; i++) {
            const x = base[i * 3], z = base[i * 3 + 2], d = Math.hypot(x / SX, z / SZ);
            const h = 0.07 * Math.sin(x * 0.8 + t * 1.1) + 0.05 * Math.sin(z * 1.25 - t * 1.5 + x * 0.35) + 0.03 * Math.sin((x - z) * 2.2 + t * 2.4);
            const surge = smooth(4.6, 3.3, d);
            p[i * 3 + 1] = h * (1 - surge * 0.5) + surge * 0.06 * Math.sin(t * 1.8 - d * 3);
            const f = Math.min(1, surge * (0.2 + 0.6 * Math.max(0, Math.sin(d * 4.5 - t * 2.2))) + Math.max(0, h - 0.08) * 5);
            c.copy(deep).lerp(shallow, smooth(7, 3.6, d) * 0.8).lerp(foam, f * 0.7);
            col[i * 3] = c.r; col[i * 3 + 1] = c.g; col[i * 3 + 2] = c.b;
        }
        geo.attributes.position.needsUpdate = true;
        geo.attributes.color.needsUpdate = true;
        geo.computeVertexNormals();
    };
    return { mesh, update };
}

function dust(count) {
    const g = new THREE.BufferGeometry();
    const p = new Float32Array(count * 3), c = new Float32Array(count * 3);
    const cols = ['#ff5a74', '#ffc35c', '#ffffff'].map((x) => new THREE.Color(x));
    for (let i = 0; i < count; i++) {
        p.set([rnd(-11, 11), rnd(0, 10), rnd(-6, 6)], i * 3);
        const col = cols[i % 3]; c.set([col.r, col.g, col.b], i * 3);
    }
    g.setAttribute('position', new THREE.BufferAttribute(p, 3));
    g.setAttribute('color', new THREE.BufferAttribute(c, 3));
    return new THREE.Points(g, new THREE.PointsMaterial({ size: 0.045, vertexColors: true, transparent: true, opacity: 0.6, depthWrite: false }));
}

const easeOutBack = (t) => 1 + 2.2 * Math.pow(t - 1, 3) + 1.2 * Math.pow(t - 1, 2);

export function mountGate(stage, { ornament, reduceMotion = false } = {}) {
    const canvas = stage.querySelector('canvas');
    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
    } catch {
        return;
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.75));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFShadowMap;

    const scene = new THREE.Scene();
    const pmrem = new THREE.PMREMGenerator(renderer);
    scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
    scene.environmentIntensity = 0.55;

    const ornTex = new THREE.TextureLoader().load(ornament);
    ornTex.colorSpace = THREE.SRGBColorSpace;
    ornTex.anisotropy = 8;
    ornTex.repeat.set(0.22, 0.5); ornTex.offset.set(0.39, 0.1);
    const panelMat = new THREE.MeshStandardMaterial({ map: ornTex, roughness: 0.5, metalness: 0.2, emissive: '#ffffff', emissiveMap: ornTex, emissiveIntensity: 0.12 });
    const tex = textures();
    tex.panelMat = panelMat;
    const { root, parts } = buildTanahLot(tex);
    root.scale.setScalar(1.05);
    root.position.y = 1.05;
    scene.add(root);

    const sea = buildSea(tex, window.innerWidth < 768 ? 64 : 96);
    root.add(sea.mesh);
    sea.update(0);
    const foams = [0.06, 0.1].map((y, i) => {
        const map = tex.foam.clone();
        const m = new THREE.Mesh(new THREE.CircleGeometry(4.9, 96).rotateX(-Math.PI / 2), new THREE.MeshBasicMaterial({ map, transparent: true, opacity: 0.5 - i * 0.2, depthWrite: false }));
        m.scale.set(SX * (1 + i * 0.06), 1, SZ * (1 + i * 0.06)); m.position.y = y;
        root.add(m);
        return m;
    });

    const ring = new THREE.Mesh(new THREE.RingGeometry(5.9, 5.95, 160), new THREE.MeshBasicMaterial({ color: '#ec2f55', transparent: true, opacity: 0.5 }));
    ring.rotation.x = -Math.PI / 2; ring.position.y = root.position.y + 0.012;
    scene.add(ring);

    scene.add(new THREE.HemisphereLight('#fff3ea', '#b7a29c', 0.9));
    const sun = new THREE.DirectionalLight('#ffe1c2', 3.1);
    sun.position.set(6, 11, 8);
    sun.castShadow = true;
    sun.shadow.mapSize.set(2048, 2048);
    Object.assign(sun.shadow.camera, { left: -11, right: 11, top: 11, bottom: -11, near: 1, far: 40 });
    sun.shadow.bias = -0.0004; sun.shadow.normalBias = 0.02; sun.shadow.radius = 4;
    scene.add(sun);
    const rim = new THREE.DirectionalLight('#ffc2cc', 1.1);
    rim.position.set(-8, 6, -9);
    scene.add(rim);
    const glow = new THREE.PointLight('#ff3d63', 10, 16, 1.6);
    glow.position.set(0, 4.5, 5);
    scene.add(glow);
    const motes = dust(window.innerWidth < 768 ? 140 : 320);
    scene.add(motes);

    const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 100);
    const resize = () => {
        const w = stage.clientWidth || 1, h = stage.clientHeight || 1;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        const vf = THREE.MathUtils.degToRad(camera.fov) / 2, hf = Math.atan(Math.tan(vf) * camera.aspect);
        const dist = Math.max(7 / Math.tan(hf), 4.9 / Math.tan(vf));
        camera.position.set(0, 4.3, dist);
        camera.lookAt(0, 3.7, 0);
        camera.updateProjectionMatrix();
    };
    resize();
    new ResizeObserver(resize).observe(stage);

    const ptr = { x: 0, y: 0, tx: 0, ty: 0 };
    window.addEventListener('pointermove', (e) => { ptr.tx = (e.clientX / window.innerWidth) * 2 - 1; ptr.ty = (e.clientY / window.innerHeight) * 2 - 1; }, { passive: true });
    window.addEventListener('deviceorientation', (e) => { if (e.gamma != null) { ptr.tx = Math.max(-1, Math.min(1, e.gamma / 30)); ptr.ty = Math.max(-1, Math.min(1, (e.beta - 45) / 30)); } });

    let visible = true, t0 = performance.now();
    new IntersectionObserver(([en]) => { visible = en.isIntersecting; }, { threshold: 0.05 }).observe(stage);

    const tick = (now) => {
        requestAnimationFrame(tick);
        if (!visible || document.hidden) return;
        const t = (now - t0) / 1000;
        ptr.x += (ptr.tx - ptr.x) * 0.05; ptr.y += (ptr.ty - ptr.y) * 0.05;
        parts.forEach((m) => {
            const k = reduceMotion ? 1 : Math.min(1, Math.max(0, (t - 0.2 - m.userData.delay) / 0.7));
            const e = k < 1 ? Math.max(0.001, easeOutBack(k)) : 1, s = m.userData.s;
            if (s) m.scale.set(s[0] * e, s[1] * e, s[2] * e); else m.scale.setScalar(e);
        });
        root.rotation.y = ptr.x * 0.38 + (reduceMotion ? 0 : Math.sin(t * 0.3) * 0.07);
        root.rotation.x = ptr.y * 0.04;
        glow.position.x += (ptr.x * 7 - glow.position.x) * 0.08;
        glow.position.y += (5 - ptr.y * 3 - glow.position.y) * 0.08;
        panelMat.emissiveIntensity = 0.1 + Math.max(0, 0.22 - Math.hypot(ptr.x, ptr.y + 0.3) * 0.2);
        ring.material.opacity = 0.3 + (Math.sin(t * 1.5) + 1) * 0.12;
        if (!reduceMotion) {
            sea.update(t);
            foams.forEach((f, i) => {
                f.material.map.rotation = t * (i ? -0.025 : 0.018);
                const p = 1 + Math.sin(t * 1.6 + i * 1.7) * 0.025;
                f.scale.set(SX * (1 + i * 0.06) * p, 1, SZ * (1 + i * 0.06) * p);
                f.material.opacity = (0.5 - i * 0.2) * (0.75 + 0.25 * Math.sin(t * 1.6 + i * 1.7));
            });
            const a = motes.geometry.attributes.position.array;
            for (let i = 1; i < a.length; i += 3) { a[i] += 0.004; if (a[i] > 10) a[i] = 0; }
            motes.geometry.attributes.position.needsUpdate = true;
            motes.rotation.y = t * 0.02;
        }
        renderer.render(scene, camera);
    };
    requestAnimationFrame(tick);
    stage.classList.add('is-ready');
}
