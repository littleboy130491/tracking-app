document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row[data-href]').forEach((row) => {
        const navigate = () => {
            if (row.dataset.target === '_blank') {
                window.open(row.dataset.href, '_blank', 'noopener');
                return;
            }

            window.location = row.dataset.href;
        };

        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select')) {
                return;
            }

            navigate();
        });

        row.addEventListener('keydown', (event) => {
            if (event.target.closest('a, button, input, select')) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigate();
            }
        });
    });
});
