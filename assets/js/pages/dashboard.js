/**
 * Learner / Mentor Dashboard — page-specific frontend behavior only.
 * The only dashboard-specific behavior is Chart.js initialization; stat
 * cards, goal cards, session cards, mentor/learner cards all reuse their
 * existing generic components and JS (vote/save/follow/modals), none of
 * which belongs here.
 *
 * Generic: any <canvas data-chart="line" data-chart-labels="[...]"
 * data-chart-values="[...]" data-chart-label="..."> on the page is
 * initialized the same way — both dashboards use this one code path
 * instead of two hardcoded chart setups.
 *
 * Frontend-only mock data — the values are whatever the page already
 * rendered into the canvas's data attributes; nothing here calculates a
 * real statistic.
 */
(function () {
  'use strict';

  var charts = [];

  function chartColors() {
    var styles = getComputedStyle(document.documentElement);
    var read = function (name) {
      return styles.getPropertyValue(name).trim();
    };
    return {
      accent: read('--ukn-accent'),
      accentSoft: read('--ukn-accent-soft'),
      inkMuted: read('--ukn-ink-muted'),
      border: read('--ukn-border'),
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

  function initChart(canvas) {
    if (typeof Chart === 'undefined' || canvas.dataset.chartInitialized) {
      return;
    }

    var labels = parseJsonAttr(canvas, 'data-chart-labels', []);
    var values = parseJsonAttr(canvas, 'data-chart-values', []);
    var label = canvas.getAttribute('data-chart-label') || 'Value';
    var colors = chartColors();

    var chart = new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: values,
          borderColor: colors.accent,
          backgroundColor: colors.accentSoft,
          pointBackgroundColor: colors.accent,
          pointBorderColor: colors.accent,
          borderWidth: 2,
          tension: 0.35,
          fill: true,
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
    });

    canvas.dataset.chartInitialized = 'true';
    charts.push(chart);
  }

  function restyleCharts() {
    if (!charts.length) {
      return;
    }
    var colors = chartColors();
    charts.forEach(function (chart) {
      chart.data.datasets.forEach(function (ds) {
        ds.borderColor = colors.accent;
        ds.backgroundColor = colors.accentSoft;
        ds.pointBackgroundColor = colors.accent;
        ds.pointBorderColor = colors.accent;
      });
      chart.options.scales.x.ticks.color = colors.inkMuted;
      chart.options.scales.y.ticks.color = colors.inkMuted;
      chart.options.scales.y.grid.color = colors.border;
      chart.update();
    });
  }

  document.querySelectorAll('[data-chart="line"]').forEach(initChart);

  // theme.js dispatches this on every light/dark toggle — reuse it
  // instead of building a second theme-change mechanism.
  document.addEventListener('ukn:themechange', restyleCharts);
})();
