document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row[data-href]').forEach((row) => {
        const navigate = () => {
            window.location = row.dataset.href;
        };

        row.addEventListener('click', (event) => {
            if (event.target.closest('.copyable-bl')) {
                return;
            }

            navigate();
        });

        row.addEventListener('keydown', (event) => {
            if (event.target.closest('.copyable-bl')) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigate();
            }
        });
    });

    function fallbackCopy(value) {
        const input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.top = '0';
        input.style.left = '0';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.focus();
        input.select();
        input.setSelectionRange(0, value.length);

        const copied = document.execCommand('copy');
        document.body.removeChild(input);

        if (!copied) {
            throw new Error('Copy command failed');
        }
    }

    async function copyText(value) {
        if (window.isSecureContext && navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(value);

                return;
            } catch (error) {
                // Fall through to legacy copy for non-HTTPS / denied permission.
            }
        }

        fallbackCopy(value);
    }

    document.querySelectorAll('.copyable-bl[data-copy]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const value = button.dataset.copy;

            if (!value) {
                return;
            }

            try {
                await copyText(value);
                button.classList.add('is-copied');
                button.setAttribute('aria-label', `Copied ${value}`);
                window.setTimeout(() => {
                    button.classList.remove('is-copied');
                    button.setAttribute('aria-label', `Copy BL number ${value}`);
                }, 1400);
            } catch (error) {
                console.error('Unable to copy BL number', error);
                window.prompt('Copy BL number:', value);
            }
        });
    });
});
