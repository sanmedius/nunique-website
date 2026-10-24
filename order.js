var formStartedAt = document.getElementById('formStartedAt');
if (formStartedAt && !formStartedAt.value) { formStartedAt.value = Math.floor(Date.now() / 1000); }

// ── SINGLE-SELECT TILES (anlass, form) ───────────────────────────────────────
document.querySelectorAll('.opt-tiles:not(.multi) .opt-tile, .shape-tile').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var group = this.dataset.group;
        document.querySelectorAll('[data-group="' + group + '"]').forEach(function(b) { b.classList.remove('sel'); });
        this.classList.add('sel');
        var hid = document.getElementById(group + '-hidden') || document.getElementById(group.replace('form','form') + '-hidden');
        if (group === 'form') hid = document.getElementById('form-hidden');
        if (hid) hid.value = this.dataset.val;
        if (group === 'anlass') {
            var w = document.getElementById('anlass-other-field');
            if (w) w.style.display = this.dataset.val === 'Sonstiges' ? 'block' : 'none';
        }
    });
});

// ── MULTI-SELECT TILES (allergie) ─────────────────────────────────────────────
document.querySelectorAll('.opt-tiles.multi .opt-tile').forEach(function(btn) {
    btn.addEventListener('click', function() {
        this.classList.toggle('sel');
        var group = this.dataset.group;
        var selected = Array.from(document.querySelectorAll('.opt-tile.sel[data-group="' + group + '"]')).map(function(b) { return b.dataset.val; });
        var hid = document.getElementById(group + 'ien-hidden');
        if (hid) hid.value = selected.join(', ');
        if (group === 'allergie') {
            var w = document.getElementById('allergy-other-wrap');
            if (w) w.style.display = selected.indexOf('Sonstiges') !== -1 ? 'block' : 'none';
        }
    });
});

// ── SIZE TILES ────────────────────────────────────────────────────────────────
function selectSize(btn) {
    document.querySelectorAll('.size-tile').forEach(function(b) { b.classList.remove('sel'); });
    btn.classList.add('sel');
    var val = btn.dataset.val;
    var hid = document.getElementById('groesse-hidden');
    var wrap = document.getElementById('groesse-custom-wrap');
    var ci = document.getElementById('groesse-custom-input');
    if (val === 'custom') {
        if (hid) hid.value = ci ? ci.value.trim() : '';
        if (wrap) wrap.style.display = 'block';
        if (ci) ci.required = true;
    } else {
        if (hid) hid.value = val;
        if (wrap) wrap.style.display = 'none';
        if (ci) ci.required = false;
    }
}
document.querySelectorAll('.size-tile').forEach(function(btn) {
    btn.addEventListener('click', function() { selectSize(this); });
});
var customSizeInput = document.getElementById('groesse-custom-input');
if (customSizeInput) {
    customSizeInput.addEventListener('input', function() {
        var selectedSize = document.querySelector('.size-tile.sel');
        var hiddenSize = document.getElementById('groesse-hidden');
        if (selectedSize && selectedSize.dataset.val === 'custom' && hiddenSize) {
            hiddenSize.value = this.value.trim();
        }
    });
}

// ── FLAVOR TILES (single selection) ──────────────────────────────────────────
var selectedFlavor = '';
document.querySelectorAll('.flavor-tile').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var val = this.dataset.val;
        if (selectedFlavor === val) {
            selectedFlavor = '';
            this.classList.remove('sel');
        } else {
            document.querySelectorAll('.flavor-tile.sel').forEach(function(tile) {
                tile.classList.remove('sel');
            });
            selectedFlavor = val;
            this.classList.add('sel');
        }
        document.getElementById('geschmack-1').value = selectedFlavor;
    });
});

// Preselect a flavour when arriving from flavours.html.
var requestedFlavor = new URLSearchParams(window.location.search).get('flavour');
if (requestedFlavor) {
    var requestedFlavorTile = Array.from(document.querySelectorAll('.flavor-tile')).find(function(tile) {
        return tile.dataset.val === requestedFlavor;
    });
    if (requestedFlavorTile) {
        selectedFlavor = requestedFlavor;
        requestedFlavorTile.classList.add('sel');
        requestedFlavorTile.setAttribute('aria-pressed', 'true');
        document.getElementById('geschmack-1').value = requestedFlavor;
    }
}

// Keep the side progress indicator aligned with the section currently in view.
var progressLinks = Array.from(document.querySelectorAll('[data-progress-step]'));
var orderStepSections = Array.from(document.querySelectorAll('[data-order-step]'));
function updateOrderProgress() {
    if (!progressLinks.length || !orderStepSections.length) return;
    var marker = window.scrollY + Math.min(window.innerHeight * .42, 360);
    var currentStep = 1;
    orderStepSections.forEach(function(section) {
        var sectionTop = section.getBoundingClientRect().top + window.scrollY;
        if (sectionTop <= marker) currentStep = Number(section.dataset.orderStep) || currentStep;
    });
    progressLinks.forEach(function(link) {
        var step = Number(link.dataset.progressStep);
        link.classList.toggle('is-active', step === currentStep);
        link.classList.toggle('is-complete', step < currentStep);
        if (step === currentStep) link.setAttribute('aria-current', 'step');
        else link.removeAttribute('aria-current');
    });
}
var progressTicking = false;
window.addEventListener('scroll', function() {
    if (progressTicking) return;
    progressTicking = true;
    window.requestAnimationFrame(function() {
        updateOrderProgress();
        progressTicking = false;
    });
}, {passive: true});
window.addEventListener('resize', updateOrderProgress);
updateOrderProgress();

// ── CALENDAR ──────────────────────────────────────────────────────────────────
var calY = new Date().getFullYear(), calM = new Date().getMonth();
var MO = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
function renderCal() {
    var lbl = document.getElementById('calMonthLbl');
    if (lbl) lbl.textContent = MO[calM] + ' ' + calY;
    var grid = document.getElementById('calGrid');
    if (!grid) return;
    grid.querySelectorAll('.cal-day').forEach(function(d){ d.remove(); });
    var today = new Date(); today.setHours(0,0,0,0);
    var minDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
    var first = new Date(calY, calM, 1).getDay();
    var offset = first === 0 ? 6 : first - 1;
    var total = new Date(calY, calM+1, 0).getDate();
    for (var i=0; i<offset; i++) { var e=document.createElement('div'); e.className='cal-day empty'; grid.appendChild(e); }
    for (var d=1; d<=total; d++) {
        var el = document.createElement('div'); el.className='cal-day'; el.textContent=d;
        var dt = new Date(calY, calM, d);
        if (dt < minDate || dt.getDay() === 0) { el.classList.add('disabled'); }
        else {
            (function(day){ el.addEventListener('click', function(){
                grid.querySelectorAll('.cal-day.sel').forEach(function(x){ x.classList.remove('sel'); });
                this.classList.add('sel');
                var p = function(n){ return String(n).padStart(2,'0'); };
                document.getElementById('datum-input').value = calY+'-'+p(calM+1)+'-'+p(day);
            }.bind(el)); })(d);
        }
        grid.appendChild(el);
    }
}
var cpb = document.getElementById('calPrevBtn'), cnb = document.getElementById('calNextBtn');
if (cpb) cpb.addEventListener('click', function(){ if(calM===0){calM=11;calY--;}else{calM--;} renderCal(); });
if (cnb) cnb.addEventListener('click', function(){ if(calM===11){calM=0;calY++;}else{calM++;} renderCal(); });
renderCal();

// ── TIME CHIPS ────────────────────────────────────────────────────────────────
var tc = document.getElementById('timeChips');
if (tc) { ['10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'].forEach(function(t){
    var b=document.createElement('button'); b.type='button'; b.className='time-chip'; b.textContent=t;
    b.addEventListener('click',function(){ tc.querySelectorAll('.time-chip.sel').forEach(function(x){x.classList.remove('sel');}); this.classList.add('sel'); document.getElementById('uhrzeit-input').value=t; }.bind(b));
    tc.appendChild(b);
}); }

// ── LOCAL FORM SECURITY ──────────────────────────────────────────────────────
function currentOrderLang(){ return document.getElementById('langField') ? document.getElementById('langField').value : 'de'; }
function securityMessage(de, en){ return currentOrderLang()==='en' ? en : de; }
function showSecurityError(message){
    var error = document.getElementById('securityError');
    if(error) error.textContent = message;
}
function setSecurityToken(q){
    var token = document.getElementById('csrfToken');
    if(!token) return;
    token.value = q && q.token ? q.token : '';
    if(token.value) showSecurityError('');
}
function validateSecurityInline(){
    var error = document.getElementById('securityError');
    var csrf = document.getElementById('csrfToken');
    var ok = csrf && csrf.value !== '';
    if(!ok){
        if(error){ error.textContent = securityMessage('Das Formular ist noch nicht bereit. Bitte laden Sie die Seite neu.', 'The form is not ready yet. Please reload the page.'); }
        return false;
    }
    if(error) error.textContent = '';
    return true;
}
function validateTurnstileInline(){
    var response = document.querySelector('[name="cf-turnstile-response"]');
    if (!response || !response.value) {
        showSecurityError(securityMessage('Bitte schlie\u00dfen Sie die Sicherheitspr\u00fcfung ab.', 'Please complete the security check.'));
        return false;
    }
    return true;
}
fetch('backend/order-token.php', {credentials:'same-origin'})
    .then(function(response){ if(!response.ok) throw new Error('token'); return response.json(); })
    .then(setSecurityToken)
    .catch(function(){
        var error=document.getElementById('securityError');
        if(!error) return;
        error.textContent = location.protocol === 'file:'
            ? 'Das Formular benötigt die PHP-Vorschau. Starten Sie die Seite über http://localhost:8080/order.'
            : 'Das Formular konnte nicht vollständig geladen werden. Bitte laden Sie die Seite neu.';
    });

// ── CONFIRMATION MODAL ────────────────────────────────────────────────────────
var submitBtn = document.getElementById('submitBtn');
var whatsappSubmitBtn = document.getElementById('whatsappSubmitBtn');
var whatsappUploadNote = document.getElementById('whatsappUploadNote');
var confirmModal = document.getElementById('confirmModal');
var modalYes = document.getElementById('modal-yes');
var modalNo = document.getElementById('modal-no');
var pendingSubmissionMode = 'email';
var emailButtonText = submitBtn.querySelector('span');
if (emailButtonText) {
    emailButtonText.setAttribute('data-de', 'Per E-Mail senden');
    emailButtonText.setAttribute('data-en', 'Send via email');
    emailButtonText.textContent = orderMessage('Per E-Mail senden', 'Send via email');
}

function showFormError(message, targetId){
    var box = document.getElementById('formValidationError');
    if (box) { box.textContent = message; }
    if (targetId) {
        var target = document.getElementById(targetId);
        if (target) {
            target.scrollIntoView({behavior:'smooth', block:'center'});
            target.classList.add('input-error');
            if (typeof target.focus === 'function') { target.focus(); }
        }
    }
}
function clearFormError(){
    var box = document.getElementById('formValidationError');
    if (box) { box.textContent = ''; }
}
function orderMessage(de, en){ return currentOrderLang()==='en' ? en : de; }
function setConfirmationMode(mode){
    pendingSubmissionMode = mode;
    var title = document.querySelector('#modal-title span');
    var text = document.querySelector('#modal-text span');
    var yes = document.querySelector('#modal-yes-text span');
    if (mode === 'whatsapp') {
        if (title) title.textContent = orderMessage('Anfrage in WhatsApp öffnen?', 'Open enquiry in WhatsApp?');
        if (text) text.textContent = orderMessage('Bitte prüfen Sie Ihre Angaben. WhatsApp wird mit der ausgefüllten Nachricht geöffnet; dort müssen Sie noch auf Senden tippen.', 'Please review your details. WhatsApp will open with the completed message; you still need to press Send there.');
        if (yes) yes.textContent = orderMessage('Ja, WhatsApp öffnen', 'Yes, open WhatsApp');
        return;
    }
    if (title) title.textContent = orderMessage('Alle Angaben gemacht?', 'All details filled in?');
    if (text) text.textContent = orderMessage('Bitte prüfen Sie Ihre Angaben noch einmal, bevor Sie die Anfrage absenden.', 'Please review your details once more before submitting the enquiry.');
    if (yes) yes.textContent = orderMessage('Ja, absenden', 'Yes, submit');
}
function formValue(name){
    var field = document.querySelector('[name="' + name + '"]');
    return field ? field.value.trim() : '';
}
function buildWhatsAppMessage(){
    var isEnglish = currentOrderLang() === 'en';
    var files = Array.prototype.slice.call(document.querySelectorAll('[name="inspiration[]"]'))
        .reduce(function(names, input){
            return names.concat(Array.prototype.map.call(input.files || [], function(file){ return file.name; }));
        }, []);
    var rows = isEnglish ? [
        ['Name', formValue('vorname') + ' ' + formValue('nachname')],
        ['Address', formValue('adresse')],
        ['Email', formValue('customer_email')],
        ['Phone', formValue('phone')],
        ['Occasion', formValue('anlass')],
        ['Other occasion', formValue('anlass_sonstig')],
        ['Pickup date', formValue('datum')],
        ['Pickup time', formValue('uhrzeit')],
        ['Flavour', formValue('geschmack_1')],
        ['Cake shape', formValue('form')],
        ['Size', formValue('groesse')],
        ['Custom size', formValue('groesse_custom')],
        ['Colours', formValue('farbe')],
        ['Cake inscription', formValue('schriftzug')],
        ['Decoration wishes', formValue('verzierung')],
        ['Allergies / intolerances', formValue('allergien_hidden')],
        ['Other allergy information', formValue('allergie_sonstig')],
        ['Additional notes', formValue('anmerkungen')],
        ['Selected image files', files.join(', ')]
    ] : [
        ['Name', formValue('vorname') + ' ' + formValue('nachname')],
        ['Adresse', formValue('adresse')],
        ['E-Mail', formValue('customer_email')],
        ['Telefon', formValue('phone')],
        ['Anlass', formValue('anlass')],
        ['Sonstiger Anlass', formValue('anlass_sonstig')],
        ['Abholdatum', formValue('datum')],
        ['Abholzeit', formValue('uhrzeit')],
        ['Geschmacksrichtung', formValue('geschmack_1')],
        ['Tortenform', formValue('form')],
        ['Größe', formValue('groesse')],
        ['Individuelle Größe', formValue('groesse_custom')],
        ['Farben', formValue('farbe')],
        ['Schriftzug', formValue('schriftzug')],
        ['Dekorationswünsche', formValue('verzierung')],
        ['Allergien / Unverträglichkeiten', formValue('allergien_hidden')],
        ['Weitere Allergieangaben', formValue('allergie_sonstig')],
        ['Zusätzliche Anmerkungen', formValue('anmerkungen')],
        ['Ausgewählte Bilddateien', files.join(', ')]
    ];
    var heading = isEnglish ? 'CAKE ENQUIRY' : 'TORTENANFRAGE';
    var imageReminder = isEnglish
        ? 'I will attach the selected images manually in WhatsApp.'
        : 'Die ausgewählten Bilder sende ich anschließend manuell in WhatsApp.';
    var details = rows.filter(function(row){ return row[1]; })
        .map(function(row){ return '*' + row[0] + ':* ' + row[1]; });
    if (files.length) details.push('', imageReminder);
    return heading + '\n\n' + details.join('\n');
}
function openWhatsAppOrder(){
    var url = 'https://wa.me/491704742351?text=' + encodeURIComponent(buildWhatsAppMessage());
    window.open(url, '_blank', 'noopener');
}
function validateOrderBeforeModal(){
    clearFormError();
    var form = document.getElementById('cakeForm');
    if (form && !form.checkValidity()) { form.reportValidity(); return false; }

    var dateInput = document.getElementById('datum-input');
    if (!dateInput || !dateInput.value) {
        showFormError(orderMessage('Bitte wählen Sie ein Abholdatum.', 'Please choose a pickup date.'), 'calGrid');
        return false;
    }

    var timeInput = document.getElementById('uhrzeit-input');
    if (!timeInput || !timeInput.value) {
        showFormError(orderMessage('Bitte wählen Sie eine Abholzeit.', 'Please choose a pickup time.'), 'timeChips');
        return false;
    }

    var flavour1 = document.getElementById('geschmack-1');
    if (!flavour1 || !flavour1.value) {
        showFormError(orderMessage('Bitte wählen Sie mindestens eine Geschmacksrichtung.', 'Please choose at least one flavour.'), 'geschmackTiles');
        return false;
    }

    var sizeInput = document.getElementById('groesse-hidden');
    var customSize = document.getElementById('groesse-custom-input');
    if (!sizeInput || (!sizeInput.value && (!customSize || !customSize.value.trim()))) {
        showFormError(orderMessage('Bitte wählen Sie eine Größe oder geben Sie eine individuelle Größe ein.', 'Please choose a size or enter a custom size.'), 'groesse-custom-input');
        return false;
    }

    if (!validateSecurityInline()) { return false; }
    if (!validateTurnstileInline()) { return false; }

    var agb = document.getElementById('agbCheck');
    if (agb && !agb.checked) {
        var wrap = agb.closest('.agb-checkbox');
        if (wrap) { wrap.style.outline = '2px solid var(--magenta)'; }
        showFormError(orderMessage('Bitte bestätigen Sie die AGB und Datenschutzhinweise.', 'Please confirm the terms and privacy information.'), 'agbCheck');
        return false;
    }
    return true;
}
if (submitBtn && confirmModal) {
    submitBtn.addEventListener('click', function() {
        if (!validateOrderBeforeModal()) { return; }
        setConfirmationMode('email');
        confirmModal.showModal();
        document.body.style.overflow = 'hidden';
    });
}
if (whatsappSubmitBtn && confirmModal) {
    whatsappSubmitBtn.addEventListener('click', function() {
        if (!validateOrderBeforeModal()) { return; }
        setConfirmationMode('whatsapp');
        confirmModal.showModal();
        document.body.style.overflow = 'hidden';
    });
}
if (modalYes) {
    modalYes.addEventListener('click', function() {
        confirmModal.close();
        document.body.style.overflow = '';
        if (!validateOrderBeforeModal()) { return; }
        if (pendingSubmissionMode === 'whatsapp') {
            openWhatsAppOrder();
            return;
        }
        document.getElementById('cakeForm').submit();
    });
}
if (modalNo) {
    modalNo.addEventListener('click', function() {
        confirmModal.close();
        document.body.style.overflow = '';
    });
}
if (confirmModal) {
    confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) { confirmModal.close(); document.body.style.overflow = ''; }
    });
    confirmModal.addEventListener('cancel', function() { document.body.style.overflow = ''; });
}
