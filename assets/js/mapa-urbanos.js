/**
 * Mapa Urbanos - WordPress integrated map
 * Loads data from WP REST API (custom post types: proyecto_urbano, articulo_urbano)
 */
(function () {
  'use strict';

  // ── Configuration from wp_localize_script ──
  const CFG = window.mapaConfig || {};
  const REST = CFG.restUrl || '/wp-json/wp/v2/';
  const NONCE = CFG.nonce || '';

  // ── Category / status lookups ──
  const TYPES = {
    paisaje:       { color:'#4d7c5f', bg:'#e8f2ed', text:'#2d5a3d', pulse:'rgba(77,124,95,.3)' },
    patrimonio:    { color:'#a96238', bg:'#f5ebe0', text:'#7a3d1a', pulse:'rgba(169,98,56,.3)' },
    movilidad:     { color:'#3b6fc9', bg:'#e4ebf5', text:'#1d4a8a', pulse:'rgba(59,111,201,.3)' },
    urbano:        { color:'#3b6fc9', bg:'#e4ebf5', text:'#1d4a8a', pulse:'rgba(59,111,201,.3)' },
    objeto:        { color:'#a96238', bg:'#f5ebe0', text:'#7a3d1a', pulse:'rgba(169,98,56,.3)' },
    general:       { color:'#78716c', bg:'#e9e7e4', text:'#57534e', pulse:'rgba(120,113,108,.3)' },
    medioambiente: { color:'#5a8a4d', bg:'#e8f0e0', text:'#3a5a2d', pulse:'rgba(90,138,77,.3)' }
  };

  const LABELS = {
    paisaje: 'Paisaje', patrimonio: 'Patrimonio',
    movilidad: 'Movilidad',
    urbano: 'Urbano', objeto: 'Objeto', general: 'General',
    medioambiente: 'Medio ambiente'
  };

  const STATUS_COLORS = {
    en_curso:  { dot:'#3b6fc9', bg:'#dce4f5', text:'#273f96' },
    terminado: { dot:'#16a34a', bg:'#d3ece0', text:'#1c5e32' },
    pendiente: { dot:'#78716c', bg:'#e9e7e4', text:'#57534e' }
  };

  const STATUS_LABELS = {
    en_curso: 'En curso', terminado: 'Terminado', pendiente: 'Pendiente'
  };

  // Map legacy values (riesgo/alerta/estudio/activo) onto the three current states.
  const STATUS_ALIAS = {
    riesgo: 'en_curso', alerta: 'en_curso', estudio: 'en_curso', activo: 'en_curso',
    en_curso: 'en_curso', terminado: 'terminado', pendiente: 'pendiente'
  };

  function normalizeStatus(value) {
    return STATUS_ALIAS[value] || 'en_curso';
  }

  // ── State ──
  let PROJECTS = [];
  let currentFilter = 'all';
  let dropdownOpen = false;

  const markersLayer = L.layerGroup();
  const polygonsLayer = L.layerGroup();

  // ── Map init ──
  const map = L.map('map', {
    center: CFG.center || [40.4168, -3.7038],
    zoom: CFG.zoom || 12,
    zoomControl: false
  });
  L.control.zoom({ position: 'bottomright' }).addTo(map);

  // ── Fit container to the space below the site header ──
  function fitContainer() {
    const container = document.querySelector('.mapa-urbanos-container');
    if (!container) return;
    const top = container.getBoundingClientRect().top;
    const height = Math.max(320, window.innerHeight - top);
    container.style.height = height + 'px';
    map.invalidateSize();
  }
  fitContainer();
  window.addEventListener('resize', fitContainer);
  window.addEventListener('load', fitContainer);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(fitContainer);
  }

  const baseLayers = {
    mapa: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors', subdomains: 'abc', minZoom: 9, maxZoom: 19
    }),
    satelite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: '© Esri', minZoom: 9, maxZoom: 19
    })
  };

  let currentBaseLayer = 'mapa';
  baseLayers.mapa.addTo(map);

  window.setMapLayer = function (layer) {
    if (!baseLayers[layer] || layer === currentBaseLayer) return;
    map.removeLayer(baseLayers[currentBaseLayer]);
    baseLayers[layer].addTo(map);
    currentBaseLayer = layer;
    var mapaBtn = document.getElementById('layerMapa');
    var sateliteBtn = document.getElementById('layerSatelite');
    if (mapaBtn) mapaBtn.classList.toggle('active', layer === 'mapa');
    if (sateliteBtn) sateliteBtn.classList.toggle('active', layer === 'satelite');
  };

  // ── Fetch data from WP REST API ──
  function fetchProjects() {
    return fetch(CFG.projectsUrl || REST + 'proyecto_urbano?per_page=100&_embed', {
      headers: NONCE ? { 'X-WP-Nonce': NONCE } : {}
    })
    .then(function (r) {
      if (!r.ok) throw new Error('Projects endpoint returned HTTP ' + r.status);
      return r.json();
    })
    .then(function (posts) {
      return posts.map(function (post) {
        // The custom endpoint returns these fields directly. Keep the WP REST
        // fallback for older cached versions of the endpoint/script.
        if (Array.isArray(post.latlng) || post.latlng === null) {
          var customStatus = normalizeStatus(post.status);
          return {
            id: post.id,
            type: post.type || 'paisaje',
            code: post.code || '',
            emoji: post.emoji || '📍',
            title: post.title || '',
            district: post.district || '',
            status: customStatus,
            statusLabel: STATUS_LABELS[customStatus],
            desc: post.desc || '',
            latlng: post.latlng,
            area: Array.isArray(post.area) ? post.area : []
          };
        }

        var meta = post.meta || {};
        var wpStatus = normalizeStatus(meta._proyecto_status || post._proyecto_status);
        var cat = (post.categoria_proyecto && post.categoria_proyecto.length > 0)
          ? post.categoria_proyecto[0]
          : null;

        // Try to get taxonomy slug from _embedded
        var catSlug = 'paisaje'; // default
        if (cat && post._embedded && post._embedded['wp:term']) {
          var terms = post._embedded['wp:term'];
          for (var i = 0; i < terms.length; i++) {
            for (var j = 0; j < terms[i].length; j++) {
              if (terms[i][j].taxonomy === 'categoria_proyecto' && terms[i][j].id === cat) {
                catSlug = terms[i][j].slug;
                break;
              }
            }
          }
        }

        var lat = parseFloat(meta._proyecto_lat || post._proyecto_lat);
        var lng = parseFloat(meta._proyecto_lng || post._proyecto_lng);
        var area = [];
        try {
          area = JSON.parse(meta._proyecto_area || post._proyecto_area || '[]');
        } catch (e) {
          area = [];
        }

        return {
          id: post.id,
          type: catSlug,
          code: meta._proyecto_code || post._proyecto_code || '',
          emoji: meta._proyecto_emoji || post._proyecto_emoji || '📍',
          title: post.title.rendered,
          district: meta._proyecto_district || post._proyecto_district || '',
          status: wpStatus,
          statusLabel: STATUS_LABELS[wpStatus],
          desc: (post.excerpt && post.excerpt.rendered.replace(/<[^>]+>/g, '').trim()) ||
                (post.content && post.content.rendered.replace(/<[^>]+>/g, '').trim()) || '',
          latlng: Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : null,
          area: area
        };
      });
    });
  }

  // ── Dropdown toggle ──
  window.toggleDropdown = function () {
    dropdownOpen = !dropdownOpen;
    document.getElementById('dropdownPanel').classList.toggle('open', dropdownOpen);
    document.getElementById('brandPill').classList.toggle('active', dropdownOpen);
  };

  function closeDropdown() {
    dropdownOpen = false;
    document.getElementById('dropdownPanel').classList.remove('open');
    document.getElementById('brandPill').classList.remove('active');
  }

  // ── Map rendering ──
  function renderMap() {
    markersLayer.clearLayers();
    polygonsLayer.clearLayers();

    PROJECTS.forEach(function (p) {
      if (currentFilter !== 'all' && p.type !== currentFilter) return;
      if (!p.latlng) return;
      var t = TYPES[p.type] || TYPES.paisaje;

      if (p.area.length >= 3) {
        var poly = L.polygon(p.area, {
          color: t.color, fillColor: t.color, fillOpacity: .22,
          weight: 2.4, opacity: .7, smoothFactor: 1.4
        });
        poly.on('mouseover', function () { poly.setStyle({ fillOpacity: .38, weight: 3 }); });
        poly.on('mouseout', function () { poly.setStyle({ fillOpacity: .22, weight: 2.4 }); });
        poly.bindPopup(buildPopup(p), { maxWidth: 280, className: '' });
        poly.addTo(polygonsLayer);
      }

      var icon = L.divIcon({
        className: '',
        html: '<div class="marker-wrap' + (p.code ? ' marker-code' : '') + '" style="background:' + t.color + '">' +
              '<div class="marker-pulse" style="background:' + t.pulse + '"></div>' +
              (p.code || p.emoji) + '</div>',
        iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -20]
      });
      L.marker(p.latlng, { icon: icon }).bindPopup(buildPopup(p), { maxWidth: 280, className: '' }).addTo(markersLayer);
    });
    markersLayer.addTo(map);
    polygonsLayer.addTo(map);
  }

  function buildPopup(p) {
    var t = TYPES[p.type] || TYPES.paisaje;
    return '<div class="pop">' +
      '<div class="pop-header">' +
        '<span class="pop-tag" style="background:' + t.bg + ';color:' + t.text + '">' + (p.code ? '' : p.emoji + ' ') + (LABELS[p.type] || p.type) + (p.code ? ' · ' + p.code : '') + '</span>' +
        '<h2>' + p.title + '</h2>' +
        '<div class="pop-district">' + p.district + '</div>' +
      '</div>' +
      '<p>' + p.desc + '</p>' +
      '<div class="pop-meta">' +
        '<div class="pop-item"><small>Ámbito</small><strong>' + p.district + '</strong></div>' +
        '<div class="pop-item"><small>Categoría</small><strong>' + (LABELS[p.type] || p.type) + '</strong></div>' +
      '</div>' +
    '</div>' +
    '<div class="pop-footer">' +
      '<span class="status ' + p.status + '">' + p.statusLabel + '</span>' +
      '<button class="pop-cta" data-ficha-id="' + p.id + '">Ver más →</button>' +
    '</div>';
  }

  // ── Dropdown list ──
  function renderDropdownList() {
    var list = document.getElementById('dropdownList');
    var filtered = PROJECTS.filter(function (p) {
      return currentFilter === 'all' || p.type === currentFilter;
    });

    list.innerHTML = filtered.map(function (p) {
      var t = TYPES[p.type] || TYPES.paisaje;
      return '<div class="dropdown-project" data-id="' + p.id + '">' +
        '<div class="dropdown-project-header">' +
          '<span class="dropdown-project-emoji">' + (p.code || p.emoji) + '</span>' +
          '<span class="dropdown-project-title">' + p.title + '</span>' +
          '<span class="dropdown-project-status ' + p.status + '">' + p.statusLabel + '</span>' +
        '</div>' +
        '<div class="dropdown-project-meta">' +
          (p.code ? '<span>' + p.code + '</span>' : '') +
          '<span>' + p.district + '</span>' +
          '<span>' + (LABELS[p.type] || p.type) + '</span>' +
        '</div>' +
        '<div class="dropdown-project-desc">' + p.desc + '</div>' +
      '</div>';
    }).join('');

    list.querySelectorAll('.dropdown-project').forEach(function (el) {
      el.addEventListener('click', function () {
        var id = parseInt(el.dataset.id);
        var p = PROJECTS.find(function (x) { return x.id === id; });
        if (p) {
          map.flyTo(p.latlng, 15, { duration: 1 });
          closeDropdown();
          setTimeout(function () { openFicha(p); }, 300);
        }
      });
    });
  }

  // ── Stats ──
  function updateStats() {
    var filtered = PROJECTS.filter(function (p) {
      return currentFilter === 'all' || p.type === currentFilter;
    });
    document.getElementById('statEnCurso').textContent = filtered.filter(function (p) { return p.status === 'en_curso'; }).length;
    document.getElementById('statTerminados').textContent = filtered.filter(function (p) { return p.status === 'terminado'; }).length;
    document.getElementById('statPendientes').textContent = filtered.filter(function (p) { return p.status === 'pendiente'; }).length;
    document.getElementById('totalCount').textContent = filtered.length;
  }

  // ── Filters ──
  function setFilter(f) {
    currentFilter = f;
    document.querySelectorAll('.dropdown-chip').forEach(function (c) {
      c.classList.toggle('active', c.dataset.filter === f);
    });
    document.querySelectorAll('.filter-dot').forEach(function (d) {
      d.classList.toggle('active', d.dataset.filter === f);
    });
    renderMap();
    renderDropdownList();
    updateStats();
  }

  document.querySelectorAll('.dropdown-chip').forEach(function (chip) {
    chip.addEventListener('click', function () { setFilter(chip.dataset.filter); });
  });

  document.querySelectorAll('.filter-dot').forEach(function (dot) {
    dot.addEventListener('click', function () { setFilter(dot.dataset.filter); });
  });

  // ── Search ──
  document.getElementById('dropdownSearch').addEventListener('input', function (e) {
    var q = e.target.value.toLowerCase();
    var list = document.getElementById('dropdownList');
    if (q.length < 2) { renderDropdownList(); return; }
    var filtered = PROJECTS.filter(function (p) {
      var matchFilter = currentFilter === 'all' || p.type === currentFilter;
      var matchSearch = p.title.toLowerCase().indexOf(q) !== -1 ||
                        p.district.toLowerCase().indexOf(q) !== -1 ||
                        p.desc.toLowerCase().indexOf(q) !== -1;
      return matchFilter && matchSearch;
    });
    list.innerHTML = filtered.map(function (p) {
      var t = TYPES[p.type] || TYPES.paisaje;
      return '<div class="dropdown-project" data-id="' + p.id + '">' +
        '<div class="dropdown-project-header">' +
          '<span class="dropdown-project-emoji">' + (p.code || p.emoji) + '</span>' +
          '<span class="dropdown-project-title">' + p.title + '</span>' +
          '<span class="dropdown-project-status ' + p.status + '">' + p.statusLabel + '</span>' +
        '</div>' +
          '<div class="dropdown-project-meta">' +
            (p.code ? '<span>' + p.code + '</span>' : '') +
            '<span>' + p.district + '</span>' +
          '<span>' + (LABELS[p.type] || p.type) + '</span>' +
        '</div>' +
        '<div class="dropdown-project-desc">' + p.desc + '</div>' +
      '</div>';
    }).join('');
    list.querySelectorAll('.dropdown-project').forEach(function (el) {
      el.addEventListener('click', function () {
        var id = parseInt(el.dataset.id);
        var p = PROJECTS.find(function (x) { return x.id === id; });
        if (p) {
          map.flyTo(p.latlng, 15, { duration: 1 });
          closeDropdown();
          setTimeout(function () { openFicha(p); }, 300);
        }
      });
    });
  });

  // ── Popup ficha button ──
  map.on('popupopen', function (e) {
    var popupLocation = e.popup.getLatLng();
    if (popupLocation && map.getZoom() < 15) {
      map.flyTo(popupLocation, 15, { duration: .7 });
    }
    var btn = e.popup.getElement().querySelector('[data-ficha-id]');
    if (!btn) return;
    btn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      var id = parseInt(btn.dataset.fichaId, 10);
      var p = PROJECTS.find(function (x) { return x.id === id; });
      if (p) { map.closePopup(); openFicha(p); }
    });
  });

  // ── Ficha panel ──
  function openFicha(p) {
    var t = TYPES[p.type] || TYPES.paisaje;
    var sc = STATUS_COLORS[p.status] || STATUS_COLORS.en_curso;
    var categoryLabel = LABELS[p.type] || p.type;

    document.getElementById('fichaHero').innerHTML =
      '<div class="ficha-header-line">' +
        '<span class="ficha-tag" style="background:' + t.bg + ';color:' + t.text + '">' + categoryLabel + '</span>' +
        (p.code ? '<span class="ficha-code-badge">' + p.code + '</span>' : '') +
      '</div>' +
      '<h2>' + p.title + '</h2>' +
      (p.district ? '<div class="ficha-district">' + p.district + '</div>' : '');

    document.getElementById('fichaBody').innerHTML =
      (p.desc ? '<div class="ficha-section">' +
        '<div class="ficha-section-title">Descripción</div>' +
        '<p class="ficha-desc">' + p.desc + '</p>' +
      '</div>' : '') +
      '<div class="ficha-status-card">' +
        '<span class="ficha-status-dot" style="background:' + sc.dot + '"></span>' +
        '<div><small>Estado</small><strong>' + p.statusLabel + '</strong></div>' +
      '</div>' +
      '<div class="ficha-section">' +
        '<div class="ficha-section-title">Acciones</div>' +
        '<div class="ficha-cta-row">' +
          (p.latlng ? '<button class="ficha-btn ficha-btn-primary" data-ficha-center>Centrar en mapa</button>' : '') +
          '<button class="ficha-btn ficha-btn-secondary" onclick="closeFicha()">' +
            'Cerrar' +
          '</button>' +
        '</div>' +
      '</div>';

    var centerButton = document.querySelector('[data-ficha-center]');
    if (centerButton && p.latlng) {
      centerButton.addEventListener('click', function () {
        map.flyTo(p.latlng, 16, { duration: 1.2 });
        closeFicha();
      });
    }

    document.getElementById('fichaOverlay').classList.add('open');
  }

  window.closeFicha = function (e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('fichaOverlay').classList.remove('open');
  };

  // ── Keyboard / click outside ──
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeFicha(); closeDropdown(); }
  });

  document.addEventListener('click', function (e) {
    if (dropdownOpen && !e.target.closest('.dropdown-panel') && !e.target.closest('.brand-pill')) {
      closeDropdown();
    }
  });

  // ── Initialize: load data then render ──
  fetchProjects()
    .then(function (projects) {
      PROJECTS = projects;
      renderMap();
      renderDropdownList();
      updateStats();
    })
    .catch(function (err) {
      console.error('Mapa Urbanos: Error loading data from REST API', err);
      // Fallback: render empty state
      renderMap();
      renderDropdownList();
      updateStats();
    });

})();
