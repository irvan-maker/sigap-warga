import 'bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.querySelector('main');
    if (mainContent && !document.getElementById('main-content')) mainContent.id = 'main-content';

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const targetId = button.dataset.copyTarget;
            const target = targetId ? document.getElementById(targetId) : null;
            const status = document.querySelector('[data-copy-status]');
            const value = target?.textContent?.trim();

            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
                if (status) status.textContent = 'Berhasil disalin.';
            } catch {
                if (status) status.textContent = 'Gagal menyalin. Pilih dan salin alamat secara manual.';
            }
        });
    });

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

    const census = document.querySelector('[data-household-census]');
    if (census) {
        const members = census.querySelector('[data-members]');
        const template = census.querySelector('[data-member-template]');
        const empty = census.querySelector('[data-members-empty]');
        let nextIndex = members.querySelectorAll('[data-member]').length;

        const summary = (key, value) => {
            const output = census.querySelector(`[data-summary="${key}"]`);
            if (output) output.textContent = value || '—';
        };
        const updateSummary = () => {
            const memberCount = members.querySelectorAll('[data-member]').length;
            summary('family-number', census.querySelector('[data-summary-family-number]')?.value.trim());
            summary('head-name', census.querySelector('[data-summary-head-name]')?.value.trim());
            summary('address', census.querySelector('[data-summary-address]')?.value.trim());
            summary('member-count', String(memberCount));
            summary('citizen-count', String(memberCount + 1));
        };
        const renumber = () => {
            members.querySelectorAll('[data-member]').forEach((member, index) => {
                member.querySelectorAll('[data-member-number]').forEach((number) => { number.textContent = String(index + 1); });
                member.querySelector('[data-remove-member]')?.setAttribute('aria-label', `Hapus anggota ${index + 1}`);
            });
            empty.classList.toggle('d-none', members.querySelector('[data-member]') !== null);
            updateSummary();
        };

        census.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            if (event.target.closest('[data-add-member]')) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                const member = wrapper.firstElementChild;
                members.append(member);
                renumber();
                member.querySelector('input, select')?.focus();
                return;
            }
            const button = event.target.closest('[data-remove-member]');
            if (button) {
                const member = button.closest('[data-member]');
                const nextFocus = member.nextElementSibling?.querySelector('input, select') ?? member.previousElementSibling?.querySelector('input, select') ?? census.querySelector('[data-add-member]');
                member.remove();
                renumber();
                nextFocus?.focus();
            }
        });
        census.addEventListener('input', updateSummary);

        const firstInvalid = census.querySelector('.is-invalid');
        if (firstInvalid) requestAnimationFrame(() => {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus({ preventScroll: true });
        });
        renumber();
    }
});
