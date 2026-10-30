document.querySelectorAll('[data-live]').forEach((element) => { const refresh = () => fetch(element.dataset.live, { headers: { Accept: 'text/html' } }).then((response) => response.ok ? response.text() : Promise.reject(response.status)).then((html) => { const parsed = new DOMParser().parseFromString(html, 'text/html'); const nextBody = parsed.querySelector('tbody'); const currentBody = element.querySelector('tbody'); if (nextBody && currentBody && nextBody.innerHTML !== currentBody.innerHTML) currentBody.replaceChildren(...nextBody.content ? [...nextBody.content.childNodes] : [...nextBody.childNodes]); }).catch(() => {}); refresh(); setInterval(refresh, 15000); });
function showToast(message, type = 'success') { const toast = document.createElement('div'); toast.className = `toast ${type}`; toast.setAttribute('role', type === 'error' ? 'alert' : 'status'); toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite'); toast.textContent = message; document.body.appendChild(toast); window.setTimeout(() => toast.remove(), 5000); }
function openModal(modal, trigger) { if (!modal) return; if (trigger && !trigger.id) trigger.id = `modal-trigger-${Date.now()}`; modal.hidden = false; modal.classList.add('open'); modal.dataset.modalTrigger = trigger?.id || ''; modal.dataset.modalPreviousFocus = document.activeElement?.id || ''; const focusable = modal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])'); (focusable[0] || modal).focus(); }
function closeModal(modal) { if (!modal) return; const trigger = modal.dataset.modalTrigger ? document.getElementById(modal.dataset.modalTrigger) : (modal.dataset.modalPreviousFocus ? document.getElementById(modal.dataset.modalPreviousFocus) : null); modal.hidden = true; modal.classList.remove('open'); delete modal.dataset.modalTrigger; delete modal.dataset.modalPreviousFocus; trigger?.focus(); }
document.querySelectorAll('[data-modal]').forEach((modal) => { modal.addEventListener('click', (event) => { if (event.target === modal || event.target.closest('[data-modal-close]')) closeModal(modal); }); });
document.addEventListener('keydown', (event) => { const modal = document.querySelector('[data-modal].open'); if (!modal) return; if (event.key === 'Escape') closeModal(modal); if (event.key === 'Tab') { const focusable = [...modal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')]; if (!focusable.length) return; const first = focusable[0]; const last = focusable[focusable.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } } });
document.querySelectorAll('[data-filter]').forEach((input) => input.addEventListener('input', () => { const query = input.value.trim().toLowerCase(); document.querySelectorAll(`${input.dataset.filter} tbody tr`).forEach((row) => { row.hidden = !row.textContent.toLowerCase().includes(query); }); }));
document.querySelectorAll('[data-menu-toggle]').forEach((button) => button.addEventListener('click', () => { const sidebar = document.querySelector('#sidebar'); const open = sidebar?.classList.toggle('open'); button.setAttribute('aria-expanded', open ? 'true' : 'false'); }));
const drawer = document.querySelector('[data-notification-drawer]'); const notificationButtons = document.querySelectorAll('[data-notifications]'); notificationButtons.forEach((button) => button.addEventListener('click', () => { const open = drawer?.classList.toggle('open'); notificationButtons.forEach((item) => item.setAttribute('aria-expanded', open ? 'true' : 'false')); if (drawer?.classList.contains('open')) loadNotifications(); })); document.addEventListener('click', (event) => { if (drawer?.classList.contains('open') && !drawer.contains(event.target) && ![...notificationButtons].some((button) => button.contains(event.target))) { drawer.classList.remove('open'); notificationButtons.forEach((button) => button.setAttribute('aria-expanded', 'false')); } }); document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && drawer?.classList.contains('open')) { drawer.classList.remove('open'); notificationButtons.forEach((button) => button.setAttribute('aria-expanded', 'false')); } });
function loadNotifications(){const list=document.querySelector('[data-notification-list]');if(!list)return;fetch('api/notifications.php').then(response=>response.json()).then(data=>{list.innerHTML=data.notifications.length?data.notifications.map(item=>`<a class="notification-item" href="${item.program_id?`program.php?id=${item.program_id}`:'#'}" data-notification-id="${item.id}" data-csrf="${data.csrf}"><strong>${escapeHtml(item.message)}</strong><small>${escapeHtml(item.created_at)}</small></a>`).join(''):'<p class="muted">No new notifications.</p>';list.querySelectorAll('[data-notification-id]').forEach(item=>item.addEventListener('click',()=>{const form=new URLSearchParams({id:item.dataset.notificationId,csrf:item.dataset.csrf});fetch('api/notifications_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form}).then(response=>response.json()).then(result=>{if(!result.success)throw new Error('Notification could not be marked as read.');showToast('Notification marked as read.');}).catch(()=>showToast('Notification could not be marked as read.','error'))}))}).catch(()=>{list.innerHTML='<p class="muted">Notifications are unavailable.</p>';showToast('Notifications are unavailable.','error')})}
function escapeHtml(value){const element=document.createElement('div');element.textContent=value;return element.innerHTML}
function updateNotificationIndicator(count){const button=document.querySelector('.notification-button');const summary=document.querySelector('[data-notification-count]');if(!button)return;let badge=button.querySelector('b');const unread=Number(count)||0;button.classList.toggle('has-unread',unread>0);button.setAttribute('aria-label',unread?`${unread} unread notifications`:'No unread notifications');if(unread){if(!badge){badge=document.createElement('b');button.appendChild(badge);}badge.textContent=unread;}else{badge?.remove();}if(summary)summary.textContent=unread?`${unread} unread`:'All caught up';}
document.addEventListener('click',(event)=>{const item=event.target.closest('[data-notification-id]');if(!item)return;event.preventDefault();event.stopPropagation();const form=new URLSearchParams({id:item.dataset.notificationId,csrf:item.dataset.csrf});fetch('api/notifications_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form}).then(response=>response.json()).then(result=>{if(!result.success)throw new Error('Notification could not be marked as read.');item.remove();const remaining=document.querySelectorAll('[data-notification-id]').length;updateNotificationIndicator(remaining);const list=document.querySelector('[data-notification-list]');if(list&&!remaining)list.innerHTML='<p class="muted">No new notifications. You are all caught up.</p>';if(item.getAttribute('href')&&item.getAttribute('href')!=='#')window.location.href=item.href;}).catch(()=>showToast('Notification could not be marked as read.','error'));},true);
document.addEventListener('click',(event)=>{const item=event.target.closest('[data-notification-id]');if(!item)return;event.preventDefault();event.stopPropagation();const targetHref=item.getAttribute('href');const form=new URLSearchParams({id:item.dataset.notificationId,csrf:item.dataset.csrf});fetch('api/notifications_read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form}).then(response=>response.json()).then(result=>{if(!result.success)throw new Error('Notification could not be marked as read.');item.remove();refreshNotificationCount();const list=document.querySelector('[data-notification-list]');if(list&&!list.querySelector('[data-notification-id]'))list.innerHTML='<p class="muted">No new notifications. You are all caught up.</p>';if(targetHref&&targetHref!=='#')window.location.href=targetHref;}).catch(()=>showToast('Notification could not be marked as read.','error'));},true);
function refreshNotificationCount(){fetch('api/notifications.php').then(response=>response.json()).then(data=>updateNotificationIndicator(data.count)).catch(()=>{});} setInterval(refreshNotificationCount,30000);
const globalSearch = document.querySelector('[data-global-search]');
const searchResults = document.querySelector('[data-search-results]');
let searchTimer;
globalSearch?.addEventListener('input', () => { clearTimeout(searchTimer); const query = globalSearch.value.trim(); if (query.length < 2) { searchResults.hidden = true; return; } searchTimer = setTimeout(() => fetch(`api/programs/search.php?q=${encodeURIComponent(query)}`).then((response) => response.json()).then((data) => { searchResults.innerHTML = (data.data?.results || []).map((item) => item.type === 'staff' ? `<div class="search-result"><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.subtitle || '')}</small></div>` : `<a class="search-result" href="program.php?id=${Number(item.id)}"><strong>${escapeHtml(item.program_code)} - ${escapeHtml(item.title)}</strong><small>${escapeHtml(item.stage_name || item.current_status || '')}</small></a>`).join('') || '<p class="muted">No matches found.</p>'; searchResults.hidden = false; }).catch(() => { searchResults.hidden = true; }), 250); });
document.addEventListener('click', (event) => { if (searchResults && !searchResults.contains(event.target) && event.target !== globalSearch) searchResults.hidden = true; });
document.querySelectorAll('[data-toast]').forEach((toast) => setTimeout(() => toast.remove(), 5000));
document.addEventListener('click',(event)=>{const item=event.target.closest('[data-notification-id]');if(!item)return;window.setTimeout(()=>{item.remove();const badge=document.querySelector('.notification-button b');if(badge){const count=Math.max(0,Number(badge.textContent)-1);if(count===0)badge.remove();else badge.textContent=count;}const list=document.querySelector('[data-notification-list]');if(list&&!list.querySelector('[data-notification-id]'))list.innerHTML='<p class="muted">No new notifications.</p>';},120);});

document.addEventListener('submit', (event) => {
	const form = event.target.closest('[data-transition-form]');
	if (!form || !window.fetch) return;
	event.preventDefault();
	const submit = form.querySelector('button[type="submit"]');
	if (submit) submit.disabled = true;
	fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) })
		.then((response) => response.json().then((data) => ({ response, data })))
		.then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.error || 'The program could not be moved.'); closeModal(form.closest('[data-modal]')); showToast('Workflow transition saved.'); window.setTimeout(() => { window.location.href = data.data.redirect; }, 350); })
		.catch((error) => { if (submit) submit.disabled = false; showToast(error.message, 'error'); });
});

const passwordToggle = document.querySelector('[data-password-toggle]');
const loginPassword = document.querySelector('#login-password');
if (passwordToggle && loginPassword) {
	passwordToggle.addEventListener('click', () => {
		const visible = loginPassword.type === 'text';
		loginPassword.type = visible ? 'password' : 'text';
		passwordToggle.textContent = visible ? 'Show' : 'Hide';
		passwordToggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
	});
}

const loginForm = document.querySelector('[data-login-form]');
if (loginForm) {
	loginForm.addEventListener('submit', (event) => {
		if (!window.fetch) return;
		event.preventDefault();
		const submit = loginForm.querySelector('[data-login-submit]');
		const error = loginForm.querySelector('[data-login-error]');
		const username = loginForm.elements.username.value.trim();
		const password = loginForm.elements.password.value;
		if (!username || !password) { error.textContent = 'Username and password are required.'; error.hidden = false; return; }
		submit.disabled = true;
		submit.textContent = 'Signing in...';
		error.hidden = true;
		fetch(loginForm.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(loginForm) })
			.then((response) => response.json().then((data) => ({ response, data })))
			.then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.error || 'Incorrect username or password.'); window.location.href = data.data.redirect; })
			.catch((reason) => { error.textContent = reason.message; error.hidden = false; submit.disabled = false; submit.textContent = 'Login'; });
	});
}

const resetModal = document.querySelector('[data-reset-modal]');
const signupModal = document.querySelector('[data-signup-modal]');
document.querySelector('[data-signup-open]')?.addEventListener('click', (event) => openModal(signupModal, event.currentTarget));
document.querySelector('[data-signup-login]')?.addEventListener('click', () => closeModal(signupModal));
document.querySelector('[data-signup-form]')?.addEventListener('submit', (event) => {
	if (!window.fetch) return;
	event.preventDefault();
	const form = event.currentTarget;
	const error = form.querySelector('[data-signup-error]');
	const submit = form.querySelector('[data-signup-submit]');
	form.querySelectorAll('[data-signup-field]').forEach((field) => { field.textContent = ''; });
	error.hidden = true;
	if (form.elements.password.value.length < 8 || form.elements.password.value !== form.elements.password_confirmation.value) {
		error.textContent = 'Use an 8-character password and confirm it correctly.';
		error.hidden = false;
		return;
	}
	submit.disabled = true;
	submit.textContent = 'Creating account...';
	fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) })
		.then((response) => response.json().then((data) => ({ response, data })))
		.then(({ response, data }) => {
			if (!response.ok || !data.success) {
				Object.entries(data.data?.fields || {}).forEach(([key, value]) => {
					const field = form.querySelector(`[data-signup-field="${key}"]`);
					if (field) field.textContent = value;
				});
				throw new Error(data.error || 'The account could not be created.');
			}
			window.location.href = data.data.redirect;
		})
		.catch((reason) => {
			error.textContent = reason.message;
			error.hidden = false;
			submit.disabled = false;
			submit.textContent = 'Create account';
		});
});
document.querySelector('[data-password-reset]')?.addEventListener('click', (event) => { event.preventDefault(); openModal(resetModal, event.currentTarget); });
document.querySelector('[data-reset-form]')?.addEventListener('submit', (event) => { if (!window.fetch) return; event.preventDefault(); const form = event.currentTarget; const message = form.querySelector('[data-reset-message]'); fetch('api/password-reset/request.php', { method: 'POST', body: new FormData(form) }).then((response) => response.json().then((data) => ({ response, data }))).then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.error || 'The request could not be sent.'); message.textContent = data.error || data.data?.message || 'Request sent.'; form.reset(); showToast(message.textContent); closeModal(resetModal); }).catch((reason) => { message.textContent = reason.message; showToast(reason.message, 'error'); }); });

const profileForm = document.querySelector('[data-profile-form]');
if (profileForm) {
	const bio = profileForm.querySelector('[data-bio-input]');
	const updateBioCount = () => { profileForm.querySelector('[data-bio-count]').textContent = bio.value.length; };
	bio?.addEventListener('input', updateBioCount); updateBioCount();
	profileForm.querySelector('[data-avatar-input]')?.addEventListener('change', (event) => { const file = event.target.files[0]; const preview = profileForm.querySelector('[data-avatar-preview]'); if (file) { preview.src = URL.createObjectURL(file); preview.hidden = false; } });
	profileForm.addEventListener('submit', (event) => { if (!window.fetch) return; event.preventDefault(); const message = profileForm.querySelector('[data-profile-message]') || profileForm.querySelector('[data-profile-error]'); profileForm.querySelectorAll('[data-error-for]').forEach((field) => { field.textContent = ''; }); fetch(profileForm.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(profileForm) }).then((response) => response.json().then((data) => ({ response, data }))).then(({ response, data }) => { if (!response.ok || !data.success) { Object.entries(data.data?.fields || {}).forEach(([key, value]) => { const field = profileForm.querySelector(`[data-error-for="${key}"]`); if (field) field.textContent = value; }); throw new Error(data.error || 'Please correct the form.'); } showToast(data.data?.message || 'Profile saved.'); window.setTimeout(() => { window.location.href = data.data.redirect; }, 350); }).catch((reason) => { message.textContent = reason.message; message.hidden = false; showToast(reason.message, 'error'); });
	});
}

const programForm = document.querySelector('[data-program-form]');
programForm?.addEventListener('submit', (event) => { if (!window.fetch) return; event.preventDefault(); const submit = programForm.querySelector('button[type="submit"]'); submit.disabled = true; fetch(programForm.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(programForm) }).then((response) => response.json().then((data) => ({ response, data }))).then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.error || 'The program could not be registered.'); showToast('Program registered.'); window.location.href = data.data.redirect; }).catch((reason) => { submit.disabled = false; showToast(reason.message, 'error'); }); });

const trackingForm = document.querySelector('[data-tracking-form]');
const trackingSearch = document.querySelector('[data-tracking-search]');
let trackingTimer;
function refreshTracking() { if (!window.fetch || !trackingForm || !trackingSearch || trackingSearch.value.trim().length < 2) return; const url = `${trackingForm.action || window.location.pathname}?q=${encodeURIComponent(trackingSearch.value.trim())}`; fetch(url, { headers: { Accept: 'text/html' } }).then((response) => response.ok ? response.text() : Promise.reject(response.status)).then((html) => { const parsed = new DOMParser().parseFromString(html, 'text/html'); const next = parsed.querySelector('.main-content'); const current = document.querySelector('.main-content'); if (next && current) { current.innerHTML = next.innerHTML; bindTrackingSearch(); } }).catch(() => {}); }
function bindTrackingSearch() { const form = document.querySelector('[data-tracking-form]'); const input = document.querySelector('[data-tracking-search]'); if (!form || !input || input.dataset.bound) return; input.dataset.bound = 'true'; input.addEventListener('input', () => { clearTimeout(trackingTimer); trackingTimer = window.setTimeout(refreshTracking, 350); }); form.addEventListener('submit', () => { clearTimeout(trackingTimer); }); }
bindTrackingSearch();

const rolePasswordSelect = document.querySelector('select[name="role"][data-role-passwords]');
const userPassword = document.querySelector('#user-password');
if (rolePasswordSelect && userPassword) {
	const rolePasswords = JSON.parse(rolePasswordSelect.dataset.rolePasswords || '{}');
	let previousDefault = rolePasswords[rolePasswordSelect.value] || '';
	const setRolePassword = () => {
		const nextDefault = rolePasswords[rolePasswordSelect.value] || '';
		if (userPassword.value === '' || userPassword.value === previousDefault) {
			userPassword.value = nextDefault;
		}
		previousDefault = nextDefault;
	};
	rolePasswordSelect.addEventListener('change', setRolePassword);
	setRolePassword();
}

function ensureActionModal(name, title) {
	let modal = document.querySelector(`[data-action-modal="${name}"]`);
	if (modal) return modal;
	modal = document.createElement('div');
	modal.className = 'modal';
	modal.hidden = true;
	modal.dataset.modal = '';
	modal.dataset.actionModal = name;
	modal.innerHTML = `<div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="${name}-title"><button type="button" class="drawer-close" aria-label="Close" data-modal-close>&times;</button><h2 id="${name}-title">${title}</h2><div data-action-modal-content><p class="muted">Loading...</p></div></div>`;
	document.body.appendChild(modal);
	modal.addEventListener('click', (event) => { if (event.target === modal || event.target.closest('[data-modal-close]')) closeModal(modal); });
	return modal;
}

document.querySelectorAll('[data-dashboard-view]').forEach((button) => button.addEventListener('click', () => {
		document.querySelectorAll('[data-dashboard-view]').forEach((item) => item.classList.toggle('is-active', item === button));
		document.querySelectorAll('[data-dashboard-panel]').forEach((panel) => { panel.hidden = panel.dataset.dashboardPanel !== button.dataset.dashboardView; });
}));
document.querySelectorAll('[data-queue-filter]').forEach((button) => button.addEventListener('click', () => {
	const filter = button.dataset.queueFilter;
	document.querySelectorAll('[data-queue-filter]').forEach((item) => { const active = item === button; item.classList.toggle('is-active', active); item.setAttribute('aria-pressed', active ? 'true' : 'false'); });
	document.querySelectorAll('[data-program-status]').forEach((item) => { item.hidden = filter !== 'ALL' && item.dataset.programStatus !== filter; });
}));
document.querySelectorAll('[data-dashboard-filter]').forEach((card) => card.addEventListener('click', (event) => {
		const label = card.dataset.dashboardFilter;
		if (label !== 'Delayed' && label !== 'In progress') return;
		event.preventDefault();
		const status = label === 'Delayed' ? 'DELAYED' : 'IN_PROGRESS';
		document.querySelectorAll('[data-program-status]').forEach((row) => { row.hidden = row.dataset.programStatus !== status; });
		document.querySelector('[data-dashboard-board]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}));
document.addEventListener('click', (event) => {
	const detailLink = event.target.closest('[data-detail-link]');
	if (detailLink && window.fetch) {
		event.preventDefault();
		const modal = ensureActionModal('program-detail', 'Program detail');
		openModal(modal, detailLink);
		fetch(`api/program_detail.php?id=${encodeURIComponent(new URL(detailLink.href).searchParams.get('id'))}`, { headers: { Accept: 'text/html' } }).then((response) => response.ok ? response.text() : Promise.reject(response.status)).then((html) => { modal.querySelector('[data-action-modal-content]').innerHTML = html; }).catch(() => { modal.querySelector('[data-action-modal-content]').innerHTML = '<p class="alert error">Program details are unavailable.</p>'; });
	}
	const transitionLink = event.target.closest('[data-transition-link]');
	if (transitionLink && window.fetch) {
		event.preventDefault();
		const modal = ensureActionModal('transition', 'Advance workflow');
		openModal(modal, transitionLink);
		const url = new URL(transitionLink.href, window.location.href); url.searchParams.set('fragment', '1');
		fetch(url, { headers: { Accept: 'text/html' } }).then((response) => response.ok ? response.text() : Promise.reject(response.status)).then((html) => { modal.querySelector('[data-action-modal-content]').innerHTML = html; }).catch(() => { window.location.href = transitionLink.href; });
	}
});
document.querySelectorAll('.kanban-card').forEach((card) => card.addEventListener('dragstart', (event) => { event.dataTransfer.setData('text/plain', card.dataset.programId); }));
document.querySelectorAll('.kanban-column').forEach((column) => column.addEventListener('dragover', (event) => event.preventDefault()));
document.querySelectorAll('.kanban-column').forEach((column) => column.addEventListener('drop', (event) => {
		event.preventDefault();
		const card = document.querySelector(`.kanban-card[data-program-id="${event.dataTransfer.getData('text/plain')}"]`);
		if (!card || Number(column.dataset.stageSequence) !== Number(card.dataset.sequence) + 1) { showToast('Only the next workflow stage is allowed.', 'error'); return; }
		const link = card.querySelector('[data-transition-link]');
		if (link) link.click();
}));

	document.addEventListener('click', (event) => {
		const edit = event.target.closest('[data-issue-edit]');
		if (edit) {
			const modal = ensureActionModal('issue-edit', 'Update issue');
			const status = edit.dataset.issueStatus;
			modal.querySelector('[data-action-modal-content]').innerHTML = `<form method="post" action="api/issue_update.php" data-issue-modal-form><input type="hidden" name="csrf" value="${escapeHtml(document.querySelector('input[name="csrf"]')?.value || '')}"><input type="hidden" name="id" value="${edit.dataset.issueId}"><p class="eyebrow">${escapeHtml(edit.dataset.issueCode)}</p><label>Status<select name="status"><option>OPEN</option><option>ASSIGNED</option><option>IN_PROGRESS</option><option>RESOLVED</option><option>CLOSED</option></select></label><label>Resolution note<textarea name="resolution" rows="4">${escapeHtml(edit.dataset.issueResolution || '')}</textarea></label><button class="button primary">Save update</button></form>`;
			modal.querySelector('select').value = status;
			openModal(modal, edit);
		}
	});
	document.addEventListener('submit', (event) => {
		const form = event.target.closest('[data-issue-modal-form]');
		if (!form || !window.fetch) return;
		event.preventDefault();
		const row = document.querySelector(`[data-issue-row="${form.elements.id.value}"]`);
		const badge = row?.querySelector('.badge');
		const edit = row?.querySelector('[data-issue-edit]');
		const submit = form.querySelector('button[type="submit"]');
		submit.disabled = true;
		fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) }).then((response) => response.json().then((data) => ({ response, data }))).then(({ response, data }) => { if (!response.ok || !data.success) throw new Error(data.error || 'Issue update failed.'); if (badge) { badge.textContent = data.data.status; badge.className = `badge ${data.data.status}`; } if (edit) { edit.dataset.issueStatus = data.data.status; edit.dataset.issueResolution = data.data.resolution; } closeModal(form.closest('[data-modal]')); showToast('Issue updated.'); }).catch((error) => { submit.disabled = false; showToast(error.message, 'error'); });
	});

	document.querySelectorAll('[data-report-preset]').forEach((button) => button.addEventListener('click', () => {
		const today = new Date();
		const from = document.querySelector('input[name="from"]');
		const to = document.querySelector('input[name="to"]');
		const format = (date) => date.toISOString().slice(0, 10);
		let start = new Date(today);
		if (button.dataset.reportPreset === 'week') start.setDate(today.getDate() - 6);
		if (button.dataset.reportPreset === 'month') start = new Date(today.getFullYear(), today.getMonth(), 1);
		from.value = format(start); to.value = format(today); button.closest('form').requestSubmit();
	}));
	
	function drawAllCharts() { 
        document.querySelectorAll('[data-report-chart]').forEach((canvas) => {
		    const context = canvas.getContext('2d');
		    if (!context) return;
		    const rows = JSON.parse(canvas.dataset.reportChart || '[]');
		    const max = Math.max(1, ...rows.map((row) => Number(row.total)));
		    const width = canvas.width = canvas.clientWidth || 420;
		    const height = canvas.height = 220;
		    context.clearRect(0, 0, width, height);
		    rows.forEach((row, index) => { 
                const barWidth = Math.max(12, width / Math.max(rows.length, 1) - 18); 
                const x = index * (width / Math.max(rows.length, 1)) + 9; 
                const barHeight = (Number(row.total) / max) * 155; 
                const y = 178 - barHeight; 
                context.fillStyle = index % 2 ? '#e92732' : '#087f8c'; 
                context.fillRect(x, y, barWidth, barHeight); 
                context.fillStyle = '#17313b'; 
                context.font = '12px sans-serif'; 
                context.fillText(String(row.total), x, y - 6); 
                context.save(); 
                context.translate(x + barWidth / 2, 198); 
                context.rotate(-0.35); 
                context.fillText(String(row.current_status).replaceAll('_', ' '), -35, 0); 
                context.restore(); 
            });
	    });
    }
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
