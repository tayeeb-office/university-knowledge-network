/**
 * Admin Dashboard — Chart.js initialization only. Mirrors the same
 * data-chart-labels/data-chart-values JSON-on-the-canvas convention
 * assets/js/pages/dashboard.js already uses for the user-facing
 * dashboards (see admin/dashboard.php's own docblock) — a separate small
 * implementation rather than sharing that file, since this one lives in
 * the locked assets/js/admin/ directory and initializes a different
 * data-admin-chart attribute (doughnut/bar) instead of that file's
 * line-only convention.
 *
 * Deliberately NOT here: the theme engine itself (this only reads the
 * same --ukn-* custom properties theme.js already keeps up to date and
 * listens for its existing 'ukn:themechange' event), any user/role
 * logic, any moderation logic.
 *
 * Frontend-only mock data — every number charted here is whatever
 * admin/dashboard.php already rendered into the canvas's own data
 * attributes; nothing here calculates a real statistic.
 */
(function () {
  'use strict';

  var canvases = document.querySelectorAll('[data-admin-chart]');
  if (!canvases.length) {
    return; // not the Admin Dashboard page — nothing to do
  }

  if (typeof Chart === 'undefined') {
    canvases.forEach(function (canvas) {
      var wrap = canvas.closest('.ukn-admin-chart-wrap');
      if (wrap) {
        wrap.innerHTML = '<p class="ukn-body-sm ukn-text-muted mb-0">Chart could not be loaded.</p>';
      }
    });
    return;
  }

  var charts = [];

  function themeColors() {
    var styles = getComputedStyle(document.documentElement);
    var read = function (name) {
      return styles.getPropertyValue(name).trim();
    };
    return {
      accent: read('--ukn-accent'),
      ink: read('--ukn-ink'),
      inkMuted: read('--ukn-ink-muted'),
      border: read('--ukn-border'),
      surface: read('--ukn-surface'),
      font: read('--ukn-font-serif') || undefined,
    };
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

  // A handful of theme-derived, brand-consistent slice colors for the
  // doughnut only — the bar/line charts stay single-series accent, same
  // as every other Chart.js usage in this project.
  function doughnutPalette(colors) {
    return [colors.accent, colors.ink, colors.inkMuted];
  }

  function initChart(canvas) {
    if (canvas.dataset.adminChartInitialized) {
      return;
    }
    var type = canvas.getAttribute('data-admin-chart');
    var labels = parseJsonAttr(canvas, 'data-chart-labels', []);
    var values = parseJsonAttr(canvas, 'data-chart-values', []);
    var datasetLabel = canvas.getAttribute('data-chart-dataset-label') || 'Value';
    var colors = themeColors();

    var config;
    if (type === 'doughnut') {
      config = {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: values,
            backgroundColor: doughnutPalette(colors),
            borderColor: colors.surface,
            borderWidth: 2,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { color: colors.ink, font: { family: colors.font } } },
          },
        },
      };
    } else {
      config = {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: datasetLabel,
            data: values,
            backgroundColor: colors.accent,
            borderRadius: 4,
            maxBarThickness: 36,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: colors.inkMuted, font: { family: colors.font } },
            },
            y: {
              beginAtZero: true,
              grid: { color: colors.border },
              ticks: { color: colors.inkMuted, font: { family: colors.font }, precision: 0 },
            },
          },
        },
      };
    }

    var chart = new Chart(canvas, config);
    canvas.dataset.adminChartInitialized = 'true';
    charts.push({ chart: chart, type: type });
  }

  canvases.forEach(initChart);

  function restyleCharts() {
    if (!charts.length) {
      return;
    }
    var colors = themeColors();
    charts.forEach(function (entry) {
      var chart = entry.chart;
      if (entry.type === 'doughnut') {
        chart.data.datasets[0].backgroundColor = doughnutPalette(colors);
        chart.data.datasets[0].borderColor = colors.surface;
        if (chart.options.plugins && chart.options.plugins.legend) {
          chart.options.plugins.legend.labels.color = colors.ink;
        }
      } else {
        chart.data.datasets[0].backgroundColor = colors.accent;
        chart.options.scales.x.ticks.color = colors.inkMuted;
        chart.options.scales.y.ticks.color = colors.inkMuted;
        chart.options.scales.y.grid.color = colors.border;
      }
      chart.update();
    });
  }

  // theme.js (loaded on every admin page too) dispatches this on every
  // light/dark toggle — reuse it instead of a second theme mechanism,
  // and always restyle the EXISTING chart instances, never recreate them.
  document.addEventListener('ukn:themechange', restyleCharts);
})();
