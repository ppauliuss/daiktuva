<?php
/**
 * Plugin Name: Daiktuva pardavėjo registracijos UX
 * Description: Registraciją parodo pirmą, prisijungimą palieka kaip antrinį veiksmą ir pagerina formos aiškumą nekeisdamas Dokan registracijos logikos.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dk_is_vendor_onboarding_page(): bool {
	return ! is_user_logged_in() && is_page( 'vendor-onboarding' );
}

add_action( 'wp_head', static function () {
	if ( ! dk_is_vendor_onboarding_page() ) {
		return;
	}
	?>
<style id="dk-onboarding-ux-css">
body.dk-onboarding-enhanced .dokan-onboarding-container{
	display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.65fr);gap:24px;align-items:start;margin-top:32px!important
}
body.dk-onboarding-enhanced .dokan-registration-section,
body.dk-onboarding-enhanced .dokan-login-section{
	background:#fff;border:1px solid var(--dk-border,#e7e9ee);border-radius:14px;box-shadow:0 8px 24px rgba(31,36,48,.05);box-sizing:border-box;min-width:0;width:100%
}
body.dk-onboarding-enhanced .dokan-registration-section{padding:24px}
body.dk-onboarding-enhanced .dokan-registration-section>h2{margin:0 0 6px;font-size:24px;line-height:1.25}
body.dk-onboarding-enhanced .dk-registration-lead{color:var(--dk-muted,#697386);font-size:15px;line-height:1.55;margin:0 0 20px}
body.dk-onboarding-enhanced .dk-google-lead{margin:14px 0 8px;color:#1f2430;font-size:14px;font-weight:800}
body.dk-onboarding-enhanced .dokan-registration-section>.dk-registration-google{margin:0 0 14px!important;text-align:left!important;width:100%}
body.dk-onboarding-enhanced .dokan-registration-section>.dk-registration-google>div:first-child:not(.nsl-container){display:none!important}
body.dk-onboarding-enhanced .dokan-registration-section>.dk-registration-google .nsl-container-buttons{width:100%}
body.dk-onboarding-enhanced .dokan-registration-section>.dk-registration-google .nsl-container-buttons a{display:block;max-width:none!important;width:100%}
body.dk-onboarding-enhanced .dokan-registration-section>.dk-registration-google .nsl-button{box-sizing:border-box;justify-content:center;min-height:48px;width:100%}
body.dk-onboarding-enhanced .dk-email-divider{align-items:center;color:#697386;display:flex;font-size:13px;gap:10px;margin:0 0 18px;text-align:center}
body.dk-onboarding-enhanced .dk-email-divider:before,body.dk-onboarding-enhanced .dk-email-divider:after{background:#e2e8f0;content:'';flex:1;height:1px}
body.dk-onboarding-enhanced .dk-onboarding-benefits{
	display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:0 0 22px;padding:0;list-style:none
}
body.dk-onboarding-enhanced .dk-onboarding-benefits li{
	align-items:center;background:#fff;border:1px solid var(--dk-border,#e7e9ee);border-radius:11px;color:var(--dk-ink,#1f2430);display:flex;font-size:14px;font-weight:700;gap:9px;line-height:1.35;padding:11px 13px
}
body.dk-onboarding-enhanced .dk-onboarding-benefits li:before{
	align-items:center;background:var(--dk-accent-soft,#eaf2fd);border-radius:50%;color:var(--dk-accent-dark,#0f4c81);content:'✓';display:inline-flex;flex:0 0 24px;font-size:14px;font-weight:900;height:24px;justify-content:center
}
body.dk-onboarding-enhanced .dokan-registration-section .form-row{margin-bottom:15px}
body.dk-onboarding-enhanced .dokan-registration-section label,
body.dk-onboarding-enhanced .dokan-login-section label{font-weight:700}
body.dk-onboarding-enhanced .dokan-registration-section input[type=text],
body.dk-onboarding-enhanced .dokan-registration-section input[type=email],
body.dk-onboarding-enhanced .dokan-registration-section input[type=tel],
body.dk-onboarding-enhanced .dokan-login-section input[type=text],
body.dk-onboarding-enhanced .dokan-login-section input[type=password]{min-height:46px}
body.dk-onboarding-enhanced .dokan-registration-section input[name=register]{
	background:#0f4c81!important;border-color:#0f4c81!important;border-radius:9px!important;color:#fff!important;font-weight:800!important;min-height:48px;padding:10px 18px!important;width:100%
}
body.dk-onboarding-enhanced .dokan-registration-section input[name=register]:hover{background:#0b3760!important;border-color:#0b3760!important}
body.dk-onboarding-enhanced .dk-field-help{color:var(--dk-muted,#697386);display:block;font-size:13px;line-height:1.45;margin-top:5px}
body.dk-onboarding-enhanced .dk-privacy-help{align-items:flex-start;background:#f0f7ff;border-radius:8px;color:#334155;display:flex;font-size:13px;gap:7px;line-height:1.45;margin:-3px 0 15px;padding:9px 11px}
body.dk-onboarding-enhanced .dk-privacy-help:before{color:#0f4c81;content:'🔒';flex:none}
body.dk-onboarding-enhanced .dk-registration-legal{color:#64748b;font-size:12.5px;line-height:1.5;margin:4px 0 13px}
body.dk-onboarding-enhanced .dk-registration-legal a{font-weight:700}
body.dk-onboarding-enhanced .dokan-login-section{padding:0}
body.dk-onboarding-enhanced .dk-login-details>summary{
	color:#0f4c81;cursor:pointer;font-size:16px;font-weight:800;list-style:none;padding:18px 46px 18px 20px;position:relative
}
body.dk-onboarding-enhanced .dk-login-details>summary::-webkit-details-marker{display:none}
body.dk-onboarding-enhanced .dk-login-details>summary:after{content:'+';font-size:24px;font-weight:500;line-height:1;position:absolute;right:20px;top:16px}
body.dk-onboarding-enhanced .dk-login-details[open]>summary:after{content:'–'}
body.dk-onboarding-enhanced .dk-login-details[open]>summary{border-bottom:1px solid var(--dk-border,#e7e9ee)}
body.dk-onboarding-enhanced .dk-login-details form{padding:18px 20px 20px}
body.dk-onboarding-enhanced .dk-login-details .woocommerce-form-login__submit{min-height:44px}
body.dk-onboarding-enhanced .dk-login-details .lost_password{margin-bottom:0}
body.dk-onboarding-enhanced .dk-visually-hidden{clip:rect(0 0 0 0);clip-path:inset(50%);height:1px;overflow:hidden;position:absolute;white-space:nowrap;width:1px}
body.dk-onboarding-enhanced .dk-social-login a{min-height:44px}
@media (max-width:760px){
	body.dk-onboarding-enhanced .dokan-onboarding-container{grid-template-columns:1fr;gap:16px}
	body.dk-onboarding-enhanced .dokan-onboarding-container{margin-top:24px!important}
	body.dk-onboarding-enhanced .dokan-registration-section{padding:18px}
	body.dk-onboarding-enhanced .dokan-registration-section>h2{font-size:21px}
	body.dk-onboarding-enhanced .dk-onboarding-benefits{grid-template-columns:1fr;gap:8px}
	body.dk-onboarding-enhanced .dk-onboarding-benefits li{padding:9px 11px}
	body.dk-onboarding-enhanced .dokan-registration-section .split-row{display:block}
}
</style>
	<?php
}, 40 );

add_action( 'wp_footer', static function () {
	if ( ! dk_is_vendor_onboarding_page() ) {
		return;
	}
	?>
<script id="dk-onboarding-ux-js">
(function () {
	'use strict';
	var container = document.querySelector('.dokan-onboarding-container');
	var registration = container && container.querySelector('.dokan-registration-section');
	var login = container && container.querySelector('.dokan-login-section');
	var form = registration && registration.querySelector('form.dokan-vendor-register');
	if (!container || !registration || !login || !form) { return; }

	// Tik po sėkmingo DOM paruošimo aktyvuojami stiliai; be JS lieka originali Dokan forma.
	document.body.classList.add('dk-onboarding-enhanced');
	container.insertBefore(registration, login);

	var title = document.querySelector('h1');
	if (title && !document.querySelector('.dk-onboarding-benefits')) {
		var benefits = document.createElement('ul');
		benefits.className = 'dk-onboarding-benefits';
		benefits.setAttribute('aria-label', 'Kodėl verta registruotis');
		['Registracija nemokama', 'Skelbimą įdėsite per kelias minutes', 'Pirkėjai susisieks tiesiogiai'].forEach(function (text) {
			var item = document.createElement('li'); item.textContent = text; benefits.appendChild(item);
		});
		title.insertAdjacentElement('afterend', benefits);
	}

	var registrationHeading = registration.querySelector('h2');
	if (registrationHeading) {
		registrationHeading.textContent = 'Sukurti pardavėjo paskyrą';
		var lead = document.createElement('p');
		lead.className = 'dk-registration-lead';
		lead.textContent = 'Užsiregistruokite ir iškart pereikite prie pirmo skelbimo.';
		registrationHeading.insertAdjacentElement('afterend', lead);
	}

	/* Nextend po puslapio užkrovimo pakeičia pradinį apvalkalą, todėl perkeliame jo galutinį konteinerį. */
	var googleLead = document.createElement('p');
	googleLead.className = 'dk-google-lead';
	googleLead.textContent = 'Greičiausia registracija';
	var emailDivider = document.createElement('div');
	emailDivider.className = 'dk-email-divider';
	emailDivider.textContent = 'arba registruokitės el. paštu';
	function placeRegistrationGoogle() {
		var googleLink = form.querySelector('a[data-provider="google"]');
		if (!googleLink) { return; }
		var googleHost = googleLink.closest('[id^="nsl-custom-login-form-"]') || googleLink.closest('.dk-social-login');
		if (!googleHost) { return; }
		googleHost.classList.add('dk-registration-google');
		registration.insertBefore(googleLead, form);
		registration.insertBefore(googleHost, form);
		registration.insertBefore(emailDivider, form);
	}
	/* Perkeliame tik Nextend užbaigus savo DOMContentLoaded inicializaciją. */
	if (document.readyState === 'complete') {
		placeRegistrationGoogle();
	} else {
		window.addEventListener('load', placeRegistrationGoogle, { once: true });
	}

	var firstName = form.querySelector('#first-name');
	var lastName = form.querySelector('#last-name');
	var email = form.querySelector('#reg_email');
	var phone = form.querySelector('#shop-phone');
	if (firstName) { firstName.autocomplete = 'given-name'; }
	if (lastName) { lastName.autocomplete = 'family-name'; }
	var nameRow = firstName && firstName.closest('.split-row');
	if (nameRow && !form.querySelector('.dk-privacy-help')) {
		var privacyHelp = document.createElement('p');
		privacyHelp.className = 'dk-privacy-help';
		privacyHelp.textContent = 'Jūsų vardas ir pavardė viešai nerodomi. Pirkėjai matys tik pardavėjo pavadinimą arba anoniminį profilį.';
		nameRow.insertAdjacentElement('afterend', privacyHelp);
	}
	if (email) { email.autocomplete = 'email'; email.inputMode = 'email'; }
	if (phone) {
		phone.autocomplete = 'tel'; phone.inputMode = 'tel'; phone.placeholder = '+370 6XX XXXXX';
		var phoneHelp = document.createElement('small');
		phoneHelp.id = 'dk-phone-help'; phoneHelp.className = 'dk-field-help';
		phoneHelp.textContent = 'Numeris bus rodomas tik jūsų paskelbtuose skelbimuose; jį galėsite pakeisti kiekviename skelbime.';
		phone.insertAdjacentElement('afterend', phoneHelp);
		phone.setAttribute('aria-describedby', phoneHelp.id);
	}
	var emailHelp = email && email.closest('.form-row') && email.closest('.form-row').querySelector('small');
	if (emailHelp) {
		emailHelp.textContent = 'Po registracijos būsite prijungti automatiškai ir galėsite kurti skelbimą. Slaptažodžio nustatymo nuorodą atsiųsime el. paštu.';
		emailHelp.classList.add('dk-field-help'); emailHelp.id = 'dk-email-help';
		email.setAttribute('aria-describedby', emailHelp.id);
	}
	var registerButton = form.querySelector('input[name=register], button[name=register]');
	if (registerButton) {
		registerButton.value = 'Registruotis ir įdėti skelbimą';
		registerButton.textContent = 'Registruotis ir įdėti skelbimą';
		registerButton.setAttribute('aria-label', 'Registruotis ir pereiti prie pirmo skelbimo');
		var registerRow = registerButton.closest('.form-row') || registerButton.parentNode;
		if (registerRow && !form.querySelector('.dk-registration-legal')) {
			var legal = document.createElement('p');
			legal.className = 'dk-registration-legal';
			legal.innerHTML = 'Registruodamiesi patvirtinate, kad susipažinote su <a href="/taisykles/">Taisyklėmis</a> ir <a href="/privatumo-politika/">Privatumo politika</a>.';
			registerRow.parentNode.insertBefore(legal, registerRow);
		}
	}

	/* GA4 registracijos piltuvėlis — be vardų, el. pašto ar telefono. */
	var pendingEvents = [];
	function track(name, params) {
		if (window.dkGaLoaded && typeof window.gtag === 'function') {
			window.gtag('event', name, params || {}); return;
		}
		pendingEvents.push([name, params || {}]);
	}
	function flushEvents() {
		if (!window.dkGaLoaded || typeof window.gtag !== 'function') { return; }
		while (pendingEvents.length) { var evt = pendingEvents.shift(); window.gtag('event', evt[0], evt[1]); }
	}
	track('registration_view', { form_type: 'seller' });
	var registrationStarted = false;
	form.addEventListener('input', function (event) {
		if (!registrationStarted && event.target.matches('input:not([type=hidden])')) {
			registrationStarted = true; track('registration_start', { form_type: 'seller' });
		}
	}, true);
	form.addEventListener('invalid', function () {
		track('registration_error', { error_type: 'client_validation' });
	}, { capture: true, once: true });
	form.addEventListener('submit', function () { track('registration_submit', { method: 'email' }); });
	registration.addEventListener('click', function (event) {
		if (event.target.closest('a[data-provider="google"]')) { track('google_signup_click', { form_type: 'seller' }); }
	}, true);
	if (registration.querySelector('.woocommerce-error,.dokan-alert-danger')) {
		track('registration_error', { error_type: 'server_validation' });
	}
	var consentAccept = document.querySelector('.dk-c-accept');
	if (consentAccept) { consentAccept.addEventListener('click', function () { window.setTimeout(flushEvents, 800); }); }
	var flushTimer = window.setInterval(flushEvents, 500);
	window.setTimeout(function () { window.clearInterval(flushTimer); flushEvents(); }, 15000);

	var loginHeading = login.querySelector('h2');
	var loginForm = login.querySelector('form.woocommerce-form-login');
	if (loginForm && !login.querySelector('.dk-login-details')) {
		var details = document.createElement('details'); details.className = 'dk-login-details';
		var summary = document.createElement('summary'); summary.textContent = 'Jau turite paskyrą? Prisijungti';
		details.appendChild(summary);
		if (loginHeading) { loginHeading.classList.add('dk-visually-hidden'); details.appendChild(loginHeading); }
		details.appendChild(loginForm);
		if (loginForm.querySelector('#username') && loginForm.querySelector('#username').value) { details.open = true; }
		login.appendChild(details);
	}

	function fixGoogleLabels() {
		document.querySelectorAll('a[data-provider="google"]').forEach(function (link) {
			if (link.getAttribute('aria-label') !== 'Tęsti su Google') {
				link.setAttribute('aria-label', 'Tęsti su Google');
			}
		});
	}
	fixGoogleLabels();
	window.addEventListener('load', fixGoogleLabels, { once: true });
	window.setTimeout(fixGoogleLabels, 1500);
	var googleObserver = new MutationObserver(fixGoogleLabels);
	googleObserver.observe(container, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-label'] });
	window.setTimeout(function () { googleObserver.disconnect(); }, 10000);
})();
</script>
	<?php
}, 40 );
