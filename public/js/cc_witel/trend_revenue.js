(function() {
    const root = document.getElementById('trend-revenue');
    if (!root) return;

    const svg = document.getElementById('treg3-chart');
    const state = {
        year: +root.dataset.year,
        month: +root.dataset.month,
        division: root.dataset.division,
        source: root.dataset.source,
        // We could embed raw series via a <script type="application/json"> tag if needed.
    };

    // Fetch chart data from embedded JSON if you prefer:
    // const data = JSON.parse(document.getElementById('treg3-data').textContent);

    // For parity with Blade logic: read precomputed series from window var if emitted
    const pre = window.__TREG3__ || {};
    const chart = pre.chart || window.chart || null; // allow you to inline via <script>
    if (!chart) {
        // Optional: you can serialize $chart to a JSON tag and read here.
        // For now we compute from DOM via data attributes if you inject them.
    }

    // Axes constants
    const W = 900, H = 320, L = 60, R = 20, T = 20, B = 40;
    const PW = W - L - R, PH = H - T - B;

    function clear() {
        while (svg.firstChild) svg.removeChild(svg.firstChild);
    }

    function line(x1, y1, x2, y2, stroke) {
        const el = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        el.setAttribute('x1', x1); el.setAttribute('y1', y1);
        el.setAttribute('x2', x2); el.setAttribute('y2', y2);
        el.setAttribute('stroke', stroke || '#e5e7eb');
        svg.appendChild(el);
    }

    function text(x, y, txt, anchor = 'start', size = 11, fill = '#6b7280') {
        const el = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        el.setAttribute('x', x); el.setAttribute('y', y);
        el.setAttribute('font-size', size);
        el.setAttribute('fill', fill);
        el.setAttribute('text-anchor', anchor);
        el.textContent = txt;
        svg.appendChild(el);
    }

    function polyline(points, strokeWidth = 2) {
        const el = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        el.setAttribute('points', points.map(p => p.join(',')).join(' '));
        el.setAttribute('fill', 'none');
        el.setAttribute('stroke-width', strokeWidth);
        svg.appendChild(el);
    }

    function scaleX(i) { return L + i * (PW / 11); }
    function scaleY(v, maxY) { return T + (PH * (1 - (v / (maxY || 1)))); }

    function draw(chart) {
        clear();

        const dgs = chart.dgs || [];
        const dps = chart.dps || [];
        const dss = chart.dss || [];
        const months = chart.months || Array.from({ length: 12 }, (_, i) => i + 1);
        const maxY = Math.max(
            Math.max(...dgs, 0),
            Math.max(...dps, 0),
            Math.max(...dss, 0),
            1
        );

        // axes
        line(L, T, L, H - B, '#e5e7eb');
        line(L, H - B, W - R, H - B, '#e5e7eb');

        // grid + y labels (0..100%)
        for (let i = 0; i <= 4; i++) {
            const val = maxY * i / 4;
            const y = scaleY(val, maxY);
            line(L, y, W - R, y, '#f3f4f6');
            text(L - 8, y + 4, `${val.toFixed(2)} B`, 'end');
        }

        // x labels
        for (let i = 0; i < months.length; i++) {
            const x = scaleX(i);
            const d = new Date(2025, months[i] - 1, 1); // month name only
            text(x, H - B + 16, d.toLocaleString('en', { month: 'short' }), 'middle');
        }

        // lines (no explicit colors)
        polyline(dgs.map((v, i) => [scaleX(i), scaleY(v, maxY)]));
        polyline(dps.map((v, i) => [scaleX(i), scaleY(v, maxY)]));
        polyline(dss.map((v, i) => [scaleX(i), scaleY(v, maxY)]));

        // legend
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('transform', `translate(${L}, ${T})`);
        svg.appendChild(g);
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('width', 130); rect.setAttribute('height', 54);
        rect.setAttribute('fill', 'white'); rect.setAttribute('stroke', '#e5e7eb');
        rect.setAttribute('rx', 8);
        g.appendChild(rect);
        const t1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        t1.setAttribute('x', 10); t1.setAttribute('y', 16);
        t1.setAttribute('font-size', 12); t1.setAttribute('fill', '#111827');
        t1.textContent = 'Legend'; g.appendChild(t1);
        const t2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        t2.setAttribute('x', 10); t2.setAttribute('y', 34);
        t2.setAttribute('font-size', 11); t2.setAttribute('fill', '#374151');
        t2.textContent = 'DGS / DPS / DSS'; g.appendChild(t2);
        const t3 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        t3.setAttribute('x', 10); t3.setAttribute('y', 50);
        t3.setAttribute('font-size', 11); t3.setAttribute('fill', '#6b7280');
        t3.textContent = '(Billions)'; g.appendChild(t3);
    }

    // Bootstrap: you can safely inject chart JSON via a global set in the Blade view:
    // window.__TREG3__ = { chart: {!! json_encode($chart) !!} }
    // To avoid inline, you can also embed in a <script type="application/json" id="treg3-json"> tag.

    // If backend emits the chart object:
    if (window.__TREG3__ && window.__TREG3__.chart) {
        draw(window.__TREG3__.chart);
    }

    // Hook filters purely for parity (form submit == full reload)
    const form = document.getElementById('treg3-filters');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Don’t intercept: server computes and re-renders (MVC)
            // If you want SPA-ish, swap for fetch+draw here; you asked MVC, so reload is correct.
        });
    }
})();
