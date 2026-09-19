/**
 * Skill Network — Cytoscape.js graph + its own page-specific controls.
 * Responsibilities: Cytoscape initialization/styling, node/edge selection
 * and neighbor highlighting, the toolbar's search + category filter +
 * zoom/fit/reset controls, and the Selected Skill panel's content.
 *
 * Deliberately NOT here: global role switching, Header Search, the modal
 * engine, the global theme toggle — those already exist and are reused
 * as-is (this file only LISTENS for theme.js's 'ukn:themechange' event to
 * restyle the existing graph instance; it never re-initializes Cytoscape
 * for a theme change, a filter click, or a node selection).
 *
 * All node/edge/relationship data is server-authored frontend mock data
 * (see pages/network/skill-network.php's data-network-nodes/-edges JSON
 * attributes) — nothing here calculates a real similarity/ranking, and
 * nothing here talks to a backend.
 */
(function () {
  'use strict';

  var container = document.getElementById('skill-network-graph');
  if (!container || container.dataset.networkInitialized) {
    return;
  }

  var errorBox = document.querySelector('[data-network-error]');
  var legend = document.querySelector('.ukn-network-legend');

  function showFatalError() {
    container.hidden = true;
    if (legend) {
      legend.hidden = true;
    }
    if (errorBox) {
      errorBox.hidden = false;
    }
  }

  if (typeof cytoscape === 'undefined') {
    showFatalError();
    return;
  }

  function parseJsonAttr(el, name, fallback) {
    var raw = el.getAttribute(name);
    if (!raw) {
      return fallback;
    }
    try {
      return JSON.parse(raw);
    } catch (e) {
      return fallback;
    }
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
  }

  var nodesData = parseJsonAttr(container, 'data-network-nodes', []);
  var edgesData = parseJsonAttr(container, 'data-network-edges', []);
  var isMentor = container.getAttribute('data-is-mentor') === '1';

  if (!nodesData.length) {
    showFatalError();
    return;
  }

  var popValues = nodesData.map(function (n) { return n.mentors + n.learners; });
  var popMin = Math.min.apply(null, popValues);
  var popMax = Math.max.apply(null, popValues);

  function nodeSize(node) {
    if (popMax === popMin) {
      return 48;
    }
    var pop = node.mentors + node.learners;
    return Math.round(40 + ((pop - popMin) / (popMax - popMin)) * 20);
  }

  var elements = nodesData.map(function (n) {
    return {
      group: 'nodes',
      data: {
        id: 'skill-' + n.id,
        name: n.name,
        category: n.category,
        mentors: n.mentors,
        learners: n.learners,
        sessions: n.sessions,
        description: n.description,
        related: n.related,
        skillDetailsHref: n.skillDetailsHref,
        findMentorsHref: n.findMentorsHref,
        size: nodeSize(n),
        isCurrent: !!n.isCurrent,
      },
    };
  }).concat(edgesData.map(function (e, i) {
    return {
      group: 'edges',
      data: {
        id: 'edge-' + i,
        source: 'skill-' + e.source,
        target: 'skill-' + e.target,
        strength: e.strength,
        reason: e.reason,
      },
    };
  }));

  function themeColors() {
    var styles = getComputedStyle(document.documentElement);
    var read = function (name) {
      return styles.getPropertyValue(name).trim();
    };
    return {
      ink: read('--ukn-ink'),
      inkMuted: read('--ukn-ink-muted'),
      accent: read('--ukn-accent'),
      accentInk: read('--ukn-accent-ink'),
      surface: read('--ukn-surface'),
      surface2: read('--ukn-surface-2'),
      border: read('--ukn-border'),
    };
  }

  function buildStyle(colors) {
    return [
      { selector: 'node', style: {
          'background-color': colors.surface,
          'border-width': 2,
          'border-color': colors.ink,
          'label': 'data(name)',
          'color': colors.ink,
          'font-size': 11,
          'text-valign': 'bottom',
          'text-halign': 'center',
          'text-margin-y': 6,
          'text-wrap': 'wrap',
          'text-max-width': '84px',
          'width': 'data(size)',
          'height': 'data(size)',
      } },
      { selector: 'node[?isCurrent]', style: {
          'border-color': colors.accent,
          'border-width': 3,
      } },
      { selector: 'edge[strength = "Strong"]', style: { 'width': 3, 'line-color': colors.ink, 'line-style': 'solid' } },
      { selector: 'edge[strength = "Medium"]', style: { 'width': 2, 'line-color': colors.inkMuted, 'line-style': 'solid' } },
      { selector: 'edge[strength = "Related"]', style: { 'width': 1.5, 'line-color': colors.inkMuted, 'line-style': 'dashed' } },
      { selector: 'edge', style: { 'curve-style': 'bezier', 'target-arrow-shape': 'none' } },
      { selector: 'node.dimmed, edge.dimmed', style: { 'opacity': 0.25 } },
      { selector: 'node.neighbor', style: { 'border-color': colors.accent, 'border-width': 3 } },
      { selector: 'node.selected', style: {
          'background-color': colors.accent,
          'border-color': colors.accent,
          'color': colors.accentInk,
      } },
      { selector: 'edge.selected-edge', style: { 'line-color': colors.accent, 'width': 4, 'opacity': 1 } },
    ];
  }

  var cy;
  try {
    cy = cytoscape({
      container: container,
      elements: elements,
      style: buildStyle(themeColors()),
      layout: { name: 'cose', animate: false, fit: true, padding: 30, randomize: true, nodeRepulsion: 9000, idealEdgeLength: 90 },
      minZoom: 0.4,
      maxZoom: 2.5,
      wheelSensitivity: 0.2,
      boxSelectionEnabled: false,
    });
  } catch (e) {
    showFatalError();
    return;
  }

  container.dataset.networkInitialized = 'true';

  /* ---- Selected Skill panel ---- */

  var panelEmpty = document.querySelector('[data-network-panel-empty]');
  var panelContent = document.querySelector('[data-network-panel-content]');

  function showEmptyPanel() {
    if (panelEmpty) { panelEmpty.hidden = false; }
    if (panelContent) { panelContent.hidden = true; panelContent.innerHTML = ''; }
  }

  function renderSkillPanel(data) {
    if (!panelContent || !panelEmpty) {
      return;
    }
    panelEmpty.hidden = true;
    panelContent.hidden = false;

    var isCurrentLabel = isMentor ? 'Teaching' : 'Learning';
    var toggleKind = isMentor ? 'teaching' : 'learning';
    var toggleState = data.isCurrent ? 'added' : 'add';
    var toggleIcon = data.isCurrent ? 'check' : 'add';
    var toggleText = data.isCurrent ? isCurrentLabel : ('Add to ' + isCurrentLabel);

    var relatedHtml = (data.related && data.related.length)
      ? data.related.map(function (name) {
          return '<button type="button" class="ukn-tag-neutral" style="cursor:pointer" data-network-related-skill="' + escapeHtml(name) + '">' + escapeHtml(name) + '</button>';
        }).join(' ')
      : '<span class="ukn-body-sm ukn-text-muted">No related skills in this network yet.</span>';

    panelContent.innerHTML =
      '<div class="d-flex align-items-center gap-2 flex-wrap">' +
        '<span style="font-size:1.1rem;font-weight:700">' + escapeHtml(data.name) + '</span>' +
        (data.isCurrent ? '<span class="ukn-status ukn-status-accent">' + escapeHtml(isCurrentLabel) + '</span>' : '') +
      '</div>' +
      '<div class="ukn-body-sm ukn-text-muted mb-3">Skill &middot; ' + escapeHtml(data.category) + '</div>' +
      '<p class="ukn-body-sm">' + escapeHtml(data.description) + '</p>' +
      '<div class="ukn-body-sm mb-3">' +
        'Mentors: <strong>' + data.mentors + '</strong><br>' +
        'Learners: <strong>' + data.learners + '</strong><br>' +
        'Connections: <strong>' + (data.related ? data.related.length : 0) + '</strong><br>' +
        'Sessions held: <strong>' + data.sessions + '</strong>' +
      '</div>' +
      '<div class="ukn-eyebrow mb-2">Related Skills</div>' +
      '<div class="d-flex flex-wrap gap-2 mb-3">' + relatedHtml + '</div>' +
      '<div class="d-flex flex-column gap-2">' +
        '<a href="' + escapeHtml(data.skillDetailsHref) + '" class="btn btn-primary btn-sm">View Skill Details</a>' +
        '<button type="button" class="btn btn-outline-primary btn-sm" data-skill-toggle="' + toggleKind + '" data-state="' + toggleState + '">' +
          '<span class="ms" aria-hidden="true">' + toggleIcon + '</span> ' +
          '<span data-skill-toggle-label>' + escapeHtml(toggleText) + '</span>' +
        '</button>' +
        (!isMentor ? '<a href="' + escapeHtml(data.findMentorsHref) + '" class="btn btn-outline-secondary btn-sm">Find Mentors</a>' : '') +
        '<button type="button" class="btn btn-link btn-sm p-0 text-start" data-network-clear-selection>Clear Selection</button>' +
      '</div>';
  }

  function renderEdgePanel(edge) {
    if (!panelContent || !panelEmpty) {
      return;
    }
    panelEmpty.hidden = true;
    panelContent.hidden = false;

    var sourceName = edge.source().data('name');
    var targetName = edge.target().data('name');
    var data = edge.data();

    panelContent.innerHTML =
      '<div style="font-size:1.05rem;font-weight:700">' + escapeHtml(sourceName) + ' &harr; ' + escapeHtml(targetName) + '</div>' +
      '<div class="ukn-body-sm ukn-text-muted mb-3">Relationship: ' + escapeHtml(data.strength) + '</div>' +
      '<p class="ukn-body-sm">' + escapeHtml(data.reason) + '</p>' +
      '<button type="button" class="btn btn-link btn-sm p-0 text-start" data-network-clear-selection>Clear Selection</button>';
  }

  /* ---- Selection state (node selection and category filter are the two
     visual modes; node selection takes priority while active) ---- */

  var activeCategory = '';
  var categorySelect = document.querySelector('[data-network-category]');

  function applyView() {
    cy.elements().removeClass('dimmed');
    if (!activeCategory) {
      return;
    }
    cy.nodes().forEach(function (n) {
      n.toggleClass('dimmed', n.data('category') !== activeCategory);
    });
    cy.edges().forEach(function (e) {
      var match = e.source().data('category') === activeCategory || e.target().data('category') === activeCategory;
      e.toggleClass('dimmed', !match);
    });
  }

  function clearSelectionClasses() {
    cy.elements().removeClass('selected neighbor selected-edge');
  }

  function selectNode(node) {
    clearSelectionClasses();
    activeCategory = '';
    if (categorySelect) { categorySelect.value = ''; }
    applyView();
    applyCategoryToList();

    node.addClass('selected');
    var neighborhood = node.closedNeighborhood();
    cy.elements().difference(neighborhood).addClass('dimmed');
    neighborhood.nodes().difference(node).addClass('neighbor');
    renderSkillPanel(node.data());
  }

  function selectEdge(edge) {
    clearSelectionClasses();
    activeCategory = '';
    if (categorySelect) { categorySelect.value = ''; }
    applyView();
    applyCategoryToList();

    edge.addClass('selected-edge');
    var related = edge.connectedNodes().union(edge);
    cy.elements().difference(related).addClass('dimmed');
    renderEdgePanel(edge);
  }

  function clearSelection() {
    clearSelectionClasses();
    applyView();
    showEmptyPanel();
  }

  cy.on('tap', 'node', function (evt) { selectNode(evt.target); });
  cy.on('tap', 'edge', function (evt) { selectEdge(evt.target); });
  cy.on('tap', function (evt) {
    if (evt.target === cy) {
      clearSelection();
    }
  });

  if (panelContent) {
    panelContent.addEventListener('click', function (event) {
      if (event.target.closest('[data-network-clear-selection]')) {
        clearSelection();
        return;
      }
      var relatedBtn = event.target.closest('[data-network-related-skill]');
      if (relatedBtn) {
        var name = relatedBtn.getAttribute('data-network-related-skill');
        var match = cy.nodes().filter(function (n) { return n.data('name') === name; })[0];
        if (match) {
          selectNode(match);
          cy.animate({ center: { eles: match } }, { duration: 200 });
        }
      }
    });
  }

  /* ---- Toolbar: search ---- */

  var searchInput = document.querySelector('[data-network-search]');
  var searchFeedback = document.querySelector('[data-network-search-feedback]');
  var searchTimer = null;

  function runSearch() {
    var query = searchInput.value.trim().toLowerCase();
    if (searchFeedback) { searchFeedback.hidden = true; }
    if (!query) {
      return;
    }
    var match = cy.nodes().filter(function (n) {
      return n.data('name').toLowerCase().indexOf(query) !== -1;
    })[0];

    if (!match) {
      if (searchFeedback) {
        searchFeedback.textContent = 'No matching skill in this network.';
        searchFeedback.hidden = false;
      }
      return;
    }
    selectNode(match);
    cy.animate({ center: { eles: match }, zoom: Math.max(cy.zoom(), 1) }, { duration: 250 });
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(runSearch, 300);
    });
  }

  /* ---- Toolbar: category filter (also filters the semantic fallback
     list below the graph, so both stay in sync) ---- */

  function applyCategoryToList() {
    var value = categorySelect ? categorySelect.value : '';
    document.querySelectorAll('[data-network-list-item]').forEach(function (item) {
      item.hidden = !!value && item.getAttribute('data-list-node-category') !== value;
    });
  }

  if (categorySelect) {
    categorySelect.addEventListener('change', function () {
      clearSelectionClasses();
      showEmptyPanel();
      activeCategory = categorySelect.value;
      applyView();
      applyCategoryToList();
    });
  }

  /* ---- Toolbar: clear filters ---- */

  var clearFiltersBtn = document.querySelector('[data-network-clear-filters]');
  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', function () {
      if (searchInput) { searchInput.value = ''; }
      if (categorySelect) { categorySelect.value = ''; }
      if (searchFeedback) { searchFeedback.hidden = true; }
      activeCategory = '';
      clearSelectionClasses();
      applyView();
      applyCategoryToList();
      showEmptyPanel();
      cy.fit(undefined, 30);
    });
  }

  /* ---- Toolbar: zoom / fit / reset ---- */

  function zoomBy(factor) {
    var center = { x: cy.width() / 2, y: cy.height() / 2 };
    var next = Math.max(cy.minZoom(), Math.min(cy.maxZoom(), cy.zoom() * factor));
    cy.zoom({ level: next, renderedPosition: center });
  }

  var zoomInBtn = document.querySelector('[data-network-zoom-in]');
  var zoomOutBtn = document.querySelector('[data-network-zoom-out]');
  var fitBtn = document.querySelector('[data-network-fit]');
  var resetBtn = document.querySelector('[data-network-reset]');

  if (zoomInBtn) { zoomInBtn.addEventListener('click', function () { zoomBy(1.2); }); }
  if (zoomOutBtn) { zoomOutBtn.addEventListener('click', function () { zoomBy(1 / 1.2); }); }
  if (fitBtn) { fitBtn.addEventListener('click', function () { cy.fit(undefined, 30); }); }
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (searchInput) { searchInput.value = ''; }
      if (categorySelect) { categorySelect.value = ''; }
      if (searchFeedback) { searchFeedback.hidden = true; }
      activeCategory = '';
      clearSelectionClasses();
      applyView();
      applyCategoryToList();
      showEmptyPanel();
      cy.fit(undefined, 30);
    });
  }

  /* ---- Window resize (debounced; resize only, never re-layout) ---- */

  var resizeTimer = null;
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      cy.resize();
    }, 200);
  });

  /* ---- Theme change: restyle the existing instance, never re-create it ---- */

  document.addEventListener('ukn:themechange', function () {
    cy.style(buildStyle(themeColors())).update();
  });
})();
