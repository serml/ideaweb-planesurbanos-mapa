<?php
/**
 * Template Name: Mapa Problemas Urbanos
 *
 * Full-width map page template for the Problemas Urbanos section.
 *
 * @package Neve_Child
 */

defined('ABSPATH') || exit;

add_action('body_class', function ($class) {
    $class[] = 'nv-template mapa-urbanos-template';
    return $class;
});

get_header();
?>

<div class="mapa-urbanos-container">

  <div id="map"></div>

  <div class="hud-header">
    <div class="brand-pill" id="brandPill" onclick="toggleDropdown()">
      <div class="brand-dot">M</div>
      <span class="brand-name">Madrid</span>
      <span class="brand-count"><strong id="totalCount">0</strong> actuaciones</span>
      <span class="brand-expand">▼</span>
    </div>
  </div>

  <div class="dropdown-panel" id="dropdownPanel">
    <div class="dropdown-search">
      <div class="dropdown-search-wrap">
        <input type="text" placeholder="Buscar intervenciones..." id="dropdownSearch">
      </div>
    </div>
    <div class="dropdown-filters">
      <div class="dropdown-filter-label">Categorías</div>
      <div class="dropdown-filter-chips" id="dropdownFilters">
        <button class="dropdown-chip active" data-filter="all">Todo</button>
        <button class="dropdown-chip" data-filter="urbano"><i style="background:var(--c-urbano)"></i>Urbano</button>
        <button class="dropdown-chip" data-filter="objeto"><i style="background:var(--c-objeto)"></i>Objeto</button>
        <button class="dropdown-chip" data-filter="general"><i style="background:var(--c-general)"></i>General</button>
      </div>
    </div>
    <div class="dropdown-list" id="dropdownList"></div>
    <div class="dropdown-stats">
      <div class="dropdown-stat en-curso">
        <small>En curso</small>
        <strong id="statEnCurso">0</strong>
      </div>
      <div class="dropdown-stat terminado">
        <small>Terminados</small>
        <strong id="statTerminados">0</strong>
      </div>
      <div class="dropdown-stat pendiente">
        <small>Pendientes</small>
        <strong id="statPendientes">0</strong>
      </div>
    </div>
  </div>

  <div class="hud-filters" id="hudFilters">
    <div class="filter-dot active" data-filter="all">Todo</div>
    <div class="filter-dot" data-filter="urbano">Urbano</div>
    <div class="filter-dot" data-filter="objeto">Objeto</div>
    <div class="filter-dot" data-filter="general">General</div>
  </div>

  <div class="hud-legend">
    <h4>Categorías</h4>
    <div class="legend-item"><span class="legend-dot" style="background:var(--c-urbano)"></span>Urbano</div>
    <div class="legend-item"><span class="legend-dot" style="background:var(--c-objeto)"></span>Objeto</div>
    <div class="legend-item"><span class="legend-dot" style="background:var(--c-general)"></span>General</div>
  </div>

  <div class="map-layer-toggle">
    <button class="layer-btn active" id="layerMapa" onclick="setMapLayer('mapa')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
      Mapa
    </button>
    <button class="layer-btn" id="layerSatelite" onclick="setMapLayer('satelite')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M14.31 8l5.74 9.94M9.69 8h11.48M7.38 12l5.74-9.94M9.69 16L3.95 6.06M14.31 16H2.83M16.62 12l-5.74 9.94"/></svg>
      Satélite
    </button>
  </div>

  <div class="ficha-overlay" id="fichaOverlay" onclick="closeFicha(event)">
    <div class="ficha-panel">
      <button class="ficha-close" onclick="closeFicha()">✕</button>
      <div class="ficha-hero" id="fichaHero"></div>
      <div class="ficha-body" id="fichaBody"></div>
    </div>
  </div>

</div>

<?php get_footer(); ?>
