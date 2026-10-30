const fs = require('fs');
let code = fs.readFileSync('assets/js/app.js', 'utf8');
code += `
document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-accept-form]');
    if (!form || !window.fetch) return;
    event.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    if (submit) { submit.disabled = true; submit.textContent = 'Accepting...'; }
    fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) })
        .then((response) => response.json().then((data) => ({ response, data })))
        .then(({ response, data }) => { 
            if (!response.ok || !data.success) throw new Error(data.error || 'Could not accept program.');
            showToast('Program accepted successfully.');
            setTimeout(() => window.location.reload(), 500);
        })
        .catch((error) => { 
            if (submit) { submit.disabled = false; submit.textContent = 'Accept Program'; }
            showToast(error.message, 'error'); 
        });
});
`;
fs.writeFileSync('assets/js/app.js', code, 'utf8');
console.log('Appended accept logic');

