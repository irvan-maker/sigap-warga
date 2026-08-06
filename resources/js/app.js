import 'bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('main');
    if (mainContent && !document.getElementById('main-content')) mainContent.id = 'main-content';

    document.querySelectorAll('tr[data-row-url]').forEach((row) => {
        if (!row.hasAttribute('tabindex')) row.tabIndex = 0;
        if (!row.hasAttribute('role')) row.setAttribute('role', 'link');
        if (!row.hasAttribute('aria-label')) row.setAttribute('aria-label', 'Buka detail data');

        const openRow = () => window.location.assign(row.dataset.rowUrl);
        row.addEventListener('click', (event) => {
            const interactive = event.target instanceof Element && event.target.closest('a, button, input, select, textarea, form, label');
            if (event.button === 0 && !interactive) openRow();
        });
        row.addEventListener('keydown', (event) => {
            if (event.target === row && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                openRow();
            }
        });
    });
});
