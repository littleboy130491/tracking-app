document.querySelectorAll('.clickable-row[data-href]').forEach((row) => {
    const navigate = () => {
        window.location = row.dataset.href;
    };

    row.addEventListener('click', navigate);
    row.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            navigate();
        }
    });
});
