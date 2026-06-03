window.addEventListener('DOMContentLoaded', (event) => {
    let registerUrlElt = document.getElementById('register-url');
    let inputAnonymous = document.querySelector('input[name="anonymous"]');

    if (registerUrlElt && inputAnonymous)
    {
        const updateUrl = () => {
            let href = registerUrlElt.getAttribute('href');
            if (inputAnonymous.checked)
            {
                if (!href.includes('anonymous'))
                {
                    href += (href.includes('?') ? '&' : '?') + 'anonymous=1';
                    registerUrlElt.setAttribute('href', href);
                }
            }
            else
            {
                href = href.replace(/[?&]anonymous=1/, '');
                registerUrlElt.setAttribute('href', href);
            }
        };

        updateUrl();
        inputAnonymous.addEventListener('change', updateUrl);
    }
});