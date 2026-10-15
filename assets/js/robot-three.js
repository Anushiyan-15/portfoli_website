// robot-three.js — friendly procedural robot (no external model = fast + offline-safe).
// Behaviours: idle bob/spin, follows pointer, blinks, click = wave + speech,
// scroll = gentle parallax. Disabled entirely when WebGL missing or
// prefers-reduced-motion is set (static accessible fallback message shown).
(function () {
  'use strict';
  var canvas = document.querySelector('[data-robot]');
  if (!canvas) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hint = document.querySelector('[data-stage-hint]');

  function say(msg) { if (window.pfSay) window.pfSay(msg); else alert(msg); }

  // Accessibility: do not autoplay 3D motion for reduced-motion users.
  if (reduce) {
    var ctx2d = canvas.getContext('2d');
    if (ctx2d) {
      ctx2d.fillStyle = '#141d3a'; ctx2d.fillRect(0, 0, canvas.width || 600, 380);
      ctx2d.fillStyle = '#eef1ff'; ctx2d.font = '28px system-ui';
      ctx2d.fillText('🤖 Hi! Motion is paused', 30, 80);
      ctx2d.font = '16px system-ui'; ctx2d.fillStyle = '#a7b0d0';
      ctx2d.fillText('Reduced-motion is on, so the 3D robot rests here.', 30, 120);
    }
    if (hint) hint.textContent = 'Motion reduced — 3D paused for accessibility';
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', 'Robot illustration (static because reduced motion is enabled)');
    return;
  }

  // Load Three.js on demand (CDN, pinned). If offline, show graceful fallback.
  function loadThree(cb) {
    if (window.THREE) return cb();
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js';
    s.defer = true;
    s.onload = cb;
    s.onerror = function () {
      if (hint) hint.textContent = '3D unavailable offline — content still fully usable';
      say('Hello! I appear when Three.js loads. Everything else works without me.');
    };
    document.head.appendChild(s);
  }

  loadThree(function () {
    try { init(); } catch (err) {
      console.warn('robot init failed', err);
      if (hint) hint.textContent = '3D unavailable on this device';
    }
  });

  function init() {
    var W = canvas.clientWidth || 600, H = canvas.clientHeight || 420;
    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    } catch (e) {
      if (hint) hint.textContent = 'WebGL unavailable — content still fully usable';
      return;
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(W, H, false);

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(42, W / H, 0.1, 100);
    camera.position.set(0, 1.4, 7.2);

    scene.add(new THREE.AmbientLight(0xffffff, 0.85));
    var key = new THREE.DirectionalLight(0x8b5cf6, 1.4); key.position.set(4, 6, 5); scene.add(key);
    var rim = new THREE.DirectionalLight(0x22d3ee, 1.1); rim.position.set(-5, 3, -3); scene.add(rim);

    var violet = new THREE.MeshStandardMaterial({ color: 0x6c5ce7, roughness: 0.35, metalness: 0.55 });
    var dark = new THREE.MeshStandardMaterial({ color: 0x1b2350, roughness: 0.5, metalness: 0.4 });
    var cyan = new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x22d3ee, emissiveIntensity: 0.9 });
    var white = new THREE.MeshStandardMaterial({ color: 0xf2f5ff, roughness: 0.3, metalness: 0.1 });

    var robot = new THREE.Group();
    scene.add(robot);

    // Body
    var body = new THREE.Mesh(new THREE.BoxGeometry(2.2, 2.4, 1.4), violet);
    body.position.y = 0.4; robot.add(body);
    var belly = new THREE.Mesh(new THREE.CylinderGeometry(0.5, 0.5, 0.15, 32), cyan);
    belly.rotation.x = Math.PI / 2; belly.position.set(0, 0.5, 0.75); robot.add(belly);

    // Head
    var headG = new THREE.Group(); headG.position.y = 2.35; robot.add(headG);
    var head = new THREE.Mesh(new THREE.BoxGeometry(1.9, 1.3, 1.4), dark);
    headG.add(head);
    var eyeGeo = new THREE.SphereGeometry(0.2, 20, 20);
    var eyeL = new THREE.Mesh(eyeGeo, cyan); eyeL.position.set(-0.42, 0.12, 0.72); headG.add(eyeL);
    var eyeR = new THREE.Mesh(eyeGeo, cyan); eyeR.position.set(0.42, 0.12, 0.72); headG.add(eyeR);
    var smile = new THREE.Mesh(new THREE.TorusGeometry(0.34, 0.05, 12, 24, Math.PI), white);
    smile.position.set(0, -0.3, 0.71); smile.rotation.z = Math.PI; headG.add(smile);
    var ant = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.05, 0.8, 12), white);
    ant.position.set(0.5, 1.0, 0); headG.add(ant);
    var bulb = new THREE.Mesh(new THREE.SphereGeometry(0.14, 16, 16), cyan);
    bulb.position.set(0.5, 1.45, 0); headG.add(bulb);

    // Arms (left waves on click)
    function arm(side) {
      var g = new THREE.Group();
      g.position.set(1.35 * side, 1.2, 0);
      var m = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.22, 1.5, 16), violet);
      m.position.y = -0.7; g.add(m);
      var hand = new THREE.Mesh(new THREE.SphereGeometry(0.3, 18, 18), cyan);
      hand.position.y = -1.5; g.add(hand);
      robot.add(g); return g;
    }
    var armL = arm(-1), armR = arm(1);

    // Legs + shadow
    [-0.55, 0.55].forEach(function (x) {
      var leg = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.3, 1.1, 16), dark);
      leg.position.set(x, -1.35, 0); robot.add(leg);
    });
    var shadow = new THREE.Mesh(new THREE.CircleGeometry(1.9, 40),
      new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.28 }));
    shadow.rotation.x = -Math.PI / 2; shadow.position.y = -1.95; scene.add(shadow);

    // Floating particles
    var pGeo = new THREE.BufferGeometry();
    var N = 130, pos = new Float32Array(N * 3);
    for (var i = 0; i < N; i++) {
      pos[i * 3] = (Math.random() - 0.5) * 12;
      pos[i * 3 + 1] = Math.random() * 6 - 2;
      pos[i * 3 + 2] = (Math.random() - 0.5) * 6 - 1;
    }
    pGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    var pts = new THREE.Points(pGeo, new THREE.PointsMaterial({ color: 0x22d3ee, size: 0.06, transparent: true, opacity: 0.8 }));
    scene.add(pts);

    robot.position.y = 0.4;

    // Interaction state
    var pointer = { x: 0, y: 0 }, wave = 0, t = 0;
    var lines = [
      'Hello! I’m Bolt — your build buddy. 🤖',
      'Click a project — I love good code!',
      'Try the theme toggle. I look great in light mode too.',
      'Psst… the admin can edit everything I say here.',
      'Accessible by design: keyboard + reduced-motion friendly.'
    ];
    var li = 0;

    window.addEventListener('pointermove', function (ev) {
      // Global tracking: hero robot follows the mouse anywhere on the page,
      // not just when hovering the canvas (full-page follow effect).
      pointer.x = (ev.clientX / window.innerWidth - 0.5) * 2;
      pointer.y = (ev.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    function doWave() {
      wave = 1; li = (li + 1) % lines.length; say(lines[li]);
      if (navigator.vibrate) { try { navigator.vibrate(20); } catch (e) {} }
    }
    canvas.addEventListener('click', doWave);
    canvas.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); doWave(); }
    });
    canvas.setAttribute('tabindex', '0');
    canvas.setAttribute('role', 'img');
    canvas.setAttribute('aria-label', 'Interactive 3D robot. Press Enter to make it wave and speak.');

    // Scroll parallax: robot drifts slightly as you scroll past hero.
    var scrollY = 0;
    window.addEventListener('scroll', function () {
      var r = canvas.getBoundingClientRect();
      scrollY = Math.max(-1, Math.min(1, -r.top / window.innerHeight));
    }, { passive: true });

    var blinkT = 0;
    var clock = new THREE.Clock();
    function frame() {
      requestAnimationFrame(frame);
      var dt = Math.min(clock.getDelta(), 0.05); t += dt;
      // idle float + lean toward pointer
      robot.position.y = 0.4 + Math.sin(t * 1.6) * 0.12;
      robot.rotation.y += ((pointer.x * 0.45 + scrollY * 0.3) - robot.rotation.y) * 0.06;
      headG.rotation.y += ((pointer.x * 0.5) - headG.rotation.y) * 0.1;
      headG.rotation.x += ((pointer.y * 0.25) - headG.rotation.x) * 0.1;
      bulb.material.emissiveIntensity = 0.7 + Math.sin(t * 4) * 0.3;
      // blink
      blinkT += dt;
      if (blinkT > 3.4) { eyeL.scale.y = 0.12; eyeR.scale.y = 0.12; if (blinkT > 3.55) { blinkT = 0; eyeL.scale.y = 1; eyeR.scale.y = 1; } }
      // wave animation decays
      if (wave > 0) {
        wave = Math.max(0, wave - dt * 1.6);
        armR.rotation.z = -2.2 + Math.sin(t * 14) * 0.45 * wave;
      } else {
        armR.rotation.z += (-0.18 + Math.sin(t * 1.6) * 0.06 - armR.rotation.z) * 0.08;
      }
      armL.rotation.z += (0.18 - armL.rotation.z) * 0.08;
      pts.rotation.y += dt * 0.03;
      renderer.render(scene, camera);
    }
    frame();

    function resize() {
      W = canvas.clientWidth || 600; H = canvas.clientHeight || 420;
      renderer.setSize(W, H, false);
      camera.aspect = W / H; camera.updateProjectionMatrix();
    }
    window.addEventListener('resize', resize);

    // Welcome line shortly after load (not for screen-reader spam: visual only).
    setTimeout(function () { say(lines[0]); }, 1200);
    if (hint) hint.textContent = 'Click the robot — it waves back';
  }
})();
