// bg-3d.js — full-page 3D background: particle field + floating shapes
// that follow the mouse anywhere on the page (global pointer tracking).
// Lightweight by design: capped DPR, ~220 particles, pauses when tab hidden.
// Disabled (canvas hidden) when prefers-reduced-motion is set or WebGL fails.
(function () {
  'use strict';
  var canvas = document.querySelector('[data-bg3d]');
  if (!canvas) return;

  // Accessibility: no ambient 3D motion for reduced-motion users.
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.style.display = 'none';
    return;
  }

  function loadThree(cb) {
    if (window.THREE) return cb();
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js';
    s.defer = true;
    s.onload = cb;
    s.onerror = function () { canvas.style.display = 'none'; };
    document.head.appendChild(s);
  }

  loadThree(function () {
    try { init(); } catch (e) { canvas.style.display = 'none'; }
  });

  function init() {
    var renderer;
    try {
      renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
    } catch (e) { canvas.style.display = 'none'; return; }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.setClearColor(0x000000, 0); // transparent — CSS gradient shows through

    var scene = new THREE.Scene();
    // Fog softens distant particles (matches dark + light themes).
    scene.fog = new THREE.FogExp2(0x0b1020, 0.045);

    var camera = new THREE.PerspectiveCamera(60, 1, 0.1, 100);
    camera.position.set(0, 0, 9);

    scene.add(new THREE.AmbientLight(0xffffff, 0.7));
    var key = new THREE.DirectionalLight(0x8b5cf6, 1.2);
    key.position.set(5, 8, 6); scene.add(key);
    var rim = new THREE.DirectionalLight(0x22d3ee, 1.0);
    rim.position.set(-6, -2, 3); scene.add(rim);

    // ---- particle starfield ----
    var N = 220, pos = new Float32Array(N * 3), seed = new Float32Array(N);
    for (var i = 0; i < N; i++) {
      pos[i * 3] = (Math.random() - 0.5) * 26;
      pos[i * 3 + 1] = (Math.random() - 0.5) * 16;
      pos[i * 3 + 2] = (Math.random() - 0.5) * 10 - 2;
      seed[i] = Math.random() * Math.PI * 2;
    }
    var pGeo = new THREE.BufferGeometry();
    pGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    var isLight = document.documentElement.getAttribute('data-theme') === 'light';
    var pts = new THREE.Points(pGeo, new THREE.PointsMaterial({
      color: isLight ? 0x6c5ce7 : 0x22d3ee,
      size: 0.09, transparent: true, opacity: isLight ? 0.5 : 0.75
    }));
    scene.add(pts);

    // ---- a few slow floating shapes (depth, not clutter) ----
    var shapes = new THREE.Group();
    scene.add(shapes);
    var matV = new THREE.MeshStandardMaterial({ color: 0x6c5ce7, roughness: 0.4, metalness: 0.5, transparent: true, opacity: 0.55 });
    var matC = new THREE.MeshStandardMaterial({ color: 0x22d3ee, roughness: 0.3, metalness: 0.5, transparent: true, opacity: 0.45, wireframe: true });
    var torus = new THREE.Mesh(new THREE.TorusGeometry(1.4, 0.12, 12, 48), matV);
    torus.position.set(-6.5, 2.2, -4); shapes.add(torus);
    var ico = new THREE.Mesh(new THREE.IcosahedronGeometry(0.9, 0), matC);
    ico.position.set(6.8, -2.4, -3); shapes.add(ico);
    var ring2 = new THREE.Mesh(new THREE.TorusGeometry(0.8, 0.07, 12, 40), matC);
    ring2.position.set(5.8, 2.8, -5); shapes.add(ring2);

    // ---- global mouse follow (whole page, not just canvas) ----
    var mx = 0, my = 0, tx = 0, ty = 0;
    window.addEventListener('pointermove', function (ev) {
      tx = (ev.clientX / window.innerWidth - 0.5) * 2;   // -1 … 1
      ty = (ev.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    // Gentle scroll drift: background sinks slightly as you scroll.
    var scrollK = 0;
    window.addEventListener('scroll', function () {
      var max = Math.max(1, document.body.scrollHeight - window.innerHeight);
      scrollK = Math.min(1, (window.scrollY || 0) / max);
    }, { passive: true });

    function resize() {
      var w = window.innerWidth, h = window.innerHeight;
      renderer.setSize(w, h, false);
      camera.aspect = w / h;
      camera.updateProjectionMatrix();
    }
    window.addEventListener('resize', resize);
    resize();

    var running = true;
    document.addEventListener('visibilitychange', function () {
      running = !document.hidden;
      if (running) { clock.getDelta(); frame(); }
    });

    var clock = new THREE.Clock();
    var t = 0;
    function frame() {
      if (!running) return;
      requestAnimationFrame(frame);
      var dt = Math.min(clock.getDelta(), 0.05); t += dt;
      // ease camera toward mouse → whole background follows cursor
      mx += (tx - mx) * 0.04;
      my += (ty - my) * 0.04;
      camera.position.x = mx * 1.6;
      camera.position.y = -my * 1.1 - scrollK * 1.2;
      camera.lookAt(0, -scrollK * 1.2, 0);

      // ambient drift (kept subtle so text stays readable)
      pts.rotation.y = t * 0.02 + mx * 0.08;
      pts.position.y = Math.sin(t * 0.25) * 0.15 - scrollK * 0.8;
      torus.rotation.x = t * 0.12; torus.rotation.y = t * 0.18;
      ico.rotation.x = t * 0.2; ico.rotation.y = t * 0.25;
      ico.position.y = -2.4 + Math.sin(t * 0.5) * 0.3;
      ring2.rotation.z = t * 0.3;

      // twinkle: bob a few particles via position attribute offset
      var arr = pGeo.attributes.position.array;
      for (var k = 0; k < N; k += 7) {
        arr[k * 3 + 1] += Math.sin(t * 0.8 + seed[k]) * 0.0009;
      }
      pGeo.attributes.position.needsUpdate = true;

      renderer.render(scene, camera);
    }
    frame();
  }
})();
