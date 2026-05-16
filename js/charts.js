// ============================================================
// EqualVoice — Charts (Leadership Tracker doughnut)
// ============================================================

'use strict';

let leadershipChartInstance = null;

const GENDER_COLORS = {
  women:       '#ED8E89',
  men:         '#9BD6D9',
  nonbinary:   '#F3EBA5',
  transgender: '#F7B685',
  gay:         '#B4A8E0',
  lesbian:     '#94C691',
};

const GENDER_LABELS = {
  women:       'Women',
  men:         'Men',
  nonbinary:   'Non-Binary',
  transgender: 'Transgender',
  gay:         'Gay / Pride',
  lesbian:     'Lesbian',
};

function buildLeadershipChart(canvasId, data) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const keys   = Object.keys(GENDER_COLORS);
  const values = keys.map(k => parseFloat(data[k + '_pct']) || 0);
  const colors = keys.map(k => GENDER_COLORS[k]);
  const labels = keys.map(k => GENDER_LABELS[k]);

  if (leadershipChartInstance) {
    leadershipChartInstance.destroy();
    leadershipChartInstance = null;
  }

  leadershipChartInstance = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data:            values,
        backgroundColor: colors,
        borderColor:     colors.map(c => c + 'cc'),
        borderWidth:     2,
        hoverBorderWidth: 4,
        hoverOffset:     8,
      }],
    },
    options: {
      responsive:          true,
      maintainAspectRatio: true,
      cutout:              '70%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.parsed.toFixed(1)}%`,
          },
          backgroundColor: 'rgba(26,9,56,0.9)',
          titleColor:      '#ffffff',
          bodyColor:       '#e0e0ff',
          borderColor:     'rgba(255,255,255,0.1)',
          borderWidth:     1,
          padding:         12,
        },
      },
      animation: {
        animateScale:   true,
        animateRotate:  true,
        duration:       900,
        easing:         'easeOutQuart',
      },
    },
  });
}

function renderGenderLegend(containerId, data) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const keys = Object.keys(GENDER_COLORS);
  container.innerHTML = keys.map(k => {
    const pct   = parseFloat(data[k + '_pct']) || 0;
    const color = GENDER_COLORS[k];
    const label = GENDER_LABELS[k];
    // Trans gets pride trans-flag gradient styling
    const transGradient = 'linear-gradient(90deg,#9BD6D9,#F7B685,#ED8E89)';
    const barStyle = k === 'transgender'
      ? `background:${transGradient};`
      : `background:${color};`;

    return `
      <div class="legend-item legend-${k}">
        <div class="legend-color" style="${k==='transgender'?`background:${transGradient};`:`background:${color};`}"></div>
        <span class="legend-name">${label}</span>
        <div class="legend-bar-wrap">
          <div class="legend-bar-fill" style="width:${pct}%;${barStyle}"></div>
        </div>
        <span class="legend-pct">${pct.toFixed(1)}%</span>
      </div>`;
  }).join('');
}

function renderGenderStatCards(containerId, data) {
  const container = document.getElementById(containerId);
  if (!container) return;
  const keys = Object.keys(GENDER_COLORS);
  container.innerHTML = keys.map(k => {
    const pct   = parseFloat(data[k + '_pct']) || 0;
    const label = GENDER_LABELS[k];
    return `
      <div class="gender-stat-card gender-card-${k}">
        <div class="stat-pct">${pct.toFixed(1)}%</div>
        <div class="stat-name">${label}</div>
      </div>`;
  }).join('');
}
