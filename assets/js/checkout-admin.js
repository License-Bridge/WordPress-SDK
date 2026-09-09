(function ($) {
  'use strict';

  if (typeof lbCheckout === 'undefined') {
    return;
  }

  var paddleScriptPromise = null;
  var paddleInitializedToken = null;
  var stripeScriptPromise = null;
  var stripeInstance = null;
  var stripeCardElement = null;
  var priceInfoCache = null;
  var activeButton = null;

  var modal = document.getElementById('lb-checkout-modal');
  var form = document.getElementById('lb-checkout-form');
  var feedback = document.getElementById('lb-checkout-feedback');
  var stripePanel = document.getElementById('lb-checkout-stripe-panel');
  var stripeErrors = document.getElementById('lb-checkout-stripe-errors');
  var submitButton = document.getElementById('lb-checkout-submit');
  var planField = document.querySelector('.lb-checkout-field--plan');
  var planTypeField = document.querySelector('.lb-checkout-field--plan-type');
  var planSelect = document.getElementById('lb-checkout-plan');
  var planTypeSelect = document.getElementById('lb-checkout-plan-type');
  var summaryPanel = document.getElementById('lb-checkout-summary');
  var summaryPlan = document.getElementById('lb-checkout-summary-plan');
  var summaryCycle = document.getElementById('lb-checkout-summary-cycle');
  var summaryPrice = document.getElementById('lb-checkout-summary-price');

  function showError(message) {
    if (!feedback) {
      return;
    }

    feedback.hidden = false;
    feedback.textContent = message || lbCheckout.messages.errorGeneric;
  }

  function clearError() {
    if (feedback) {
      feedback.hidden = true;
      feedback.textContent = '';
    }

    if (stripeErrors) {
      stripeErrors.textContent = '';
    }
  }

  function setBusy(isBusy) {
    if (modal) {
      modal.classList.toggle('is-busy', !!isBusy);
    }

    if (submitButton) {
      submitButton.disabled = !!isBusy;
      submitButton.textContent = isBusy
        ? lbCheckout.messages.loading
        : submitLabel();
    }
  }

  function submitLabel() {
    return activeButton && activeButton.dataset.gateway === 'stripe'
      ? 'Pay now'
      : lbCheckout.messages.continue;
  }

  function openModal() {
    if (!modal) {
      return;
    }

    clearError();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) {
      return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    setBusy(false);
    activeButton = null;
  }

  function readButtonConfig(button) {
    return {
      productSlug: button.dataset.productSlug || lbCheckout.product,
      gateway: button.dataset.gateway || 'paddle',
      planSlug: button.dataset.planSlug || '',
      planType: button.dataset.planType || '',
      allowPlanSelection: button.dataset.allowPlanSelection === '1',
      showOrderSummary: button.dataset.showOrderSummary !== '0',
      callbackUrl: button.dataset.callbackUrl || '',
    };
  }

  function normalizePlans(payload) {
    if (!payload || !payload.plans) {
      return [];
    }

    if (Array.isArray(payload.plans)) {
      return payload.plans;
    }

    if (Array.isArray(payload.plans.data)) {
      return payload.plans.data;
    }

    return [];
  }

  function planTypesForPlan(plan) {
    var types = [];

    if (!plan) {
      return types;
    }

    if (plan.price_month !== null && plan.price_month !== undefined && plan.price_month !== '') {
      types.push('month');
    }

    if (plan.price_annual !== null && plan.price_annual !== undefined && plan.price_annual !== '') {
      types.push('annual');
    }

    if (plan.price_life !== null && plan.price_life !== undefined && plan.price_life !== '') {
      types.push('life');
    }

    return types;
  }

  function planTypeLabel(type) {
    if (type === 'month') {
      return 'Monthly';
    }

    if (type === 'annual') {
      return 'Annual';
    }

    if (type === 'life') {
      return 'Lifetime';
    }

    return type;
  }

  function populatePlanSelect(plans, selectedSlug) {
    if (!planSelect) {
      return;
    }

    planSelect.innerHTML = '';

    plans.forEach(function (plan) {
      var option = document.createElement('option');
      option.value = plan.slug;
      option.textContent = plan.plan_name || plan.slug;
      planSelect.appendChild(option);
    });

    if (selectedSlug) {
      planSelect.value = selectedSlug;
    }
  }

  function populatePlanTypeSelect(plan, selectedType) {
    if (!planTypeSelect) {
      return;
    }

    planTypeSelect.innerHTML = '';
    var types = planTypesForPlan(plan);

    types.forEach(function (type) {
      var option = document.createElement('option');
      option.value = type;
      option.textContent = planTypeLabel(type);
      planTypeSelect.appendChild(option);
    });

    if (selectedType && types.indexOf(selectedType) !== -1) {
      planTypeSelect.value = selectedType;
    } else if (types.length) {
      planTypeSelect.value = types[0];
    }
  }

  function selectedPlanFromUi(config) {
    var plans = normalizePlans(priceInfoCache);
    var slug = config.planSlug;

    if (config.allowPlanSelection && planSelect && planSelect.value) {
      slug = planSelect.value;
    }

    return plans.find(function (plan) {
      return plan.slug === slug;
    });
  }

  function selectedPlanType(config) {
    if (config.planType) {
      return config.planType;
    }

    if (planTypeSelect && planTypeSelect.value) {
      return planTypeSelect.value;
    }

    if (priceInfoCache && priceInfoCache.cheapestPlanType) {
      return priceInfoCache.cheapestPlanType;
    }

    return 'month';
  }

  function togglePlanFields(config) {
    var plans = normalizePlans(priceInfoCache);
    var showPlan = config.allowPlanSelection && plans.length > 1;
    var plan = selectedPlanFromUi(config);
    var types = planTypesForPlan(plan);
    var showPlanType = config.allowPlanSelection || (!config.planType && types.length > 1);

    if (planField) {
      planField.hidden = !showPlan;
    }

    if (planTypeField) {
      planTypeField.hidden = !showPlanType;
    }
  }

  function priceForPlan(plan, type) {
    if (!plan) {
      return null;
    }

    var key = type === 'month' ? 'price_month' : type === 'annual' ? 'price_annual' : 'price_life';
    var value = plan[key];

    if (value === null || value === undefined || value === '') {
      return null;
    }

    return value;
  }

  function trialDaysForPlan(plan, type) {
    if (!plan) {
      return 0;
    }

    var key = type === 'month' ? 'trial_days_month' : type === 'annual' ? 'trial_days_annual' : 'trial_days_life';
    var days = parseInt(plan[key], 10);

    return isNaN(days) ? 0 : days;
  }

  function formatPrice(amount, currencySymbol, currencyCode) {
    var numeric = parseFloat(amount);

    if (isNaN(numeric)) {
      return String(amount);
    }

    if (currencyCode && typeof Intl !== 'undefined') {
      try {
        return new Intl.NumberFormat(undefined, {
          style: 'currency',
          currency: currencyCode,
        }).format(numeric);
      } catch (error) {
        // Fall back to symbol prefix.
      }
    }

    return (currencySymbol || '') + numeric.toFixed(2);
  }

  function cycleSuffix(type, messages) {
    if (type === 'month') {
      return messages.perMonth || 'per month';
    }

    if (type === 'annual') {
      return messages.perAnnual || 'per year';
    }

    if (type === 'life') {
      return messages.lifetime || 'one-time (Lifetime)';
    }

    return planTypeLabel(type);
  }

  function updateOrderSummary(config) {
    if (!summaryPanel) {
      return;
    }

    if (!config.showOrderSummary || !priceInfoCache) {
      summaryPanel.hidden = true;
      return;
    }

    var plan = selectedPlanFromUi(config);
    var type = selectedPlanType(config);
    var price = priceForPlan(plan, type);
    var messages = lbCheckout.messages || {};
    var symbol = priceInfoCache.currencySymbol || '';
    var code = priceInfoCache.currencyCode || '';
    var trialDays = trialDaysForPlan(plan, type);

    if (!plan || price === null) {
      summaryPanel.hidden = true;
      return;
    }

    var formattedPrice = formatPrice(price, symbol, code);
    var suffix = cycleSuffix(type, messages);
    var priceLine = type === 'life' ? formattedPrice + ' — ' + suffix : formattedPrice + ' / ' + suffix;

    summaryPanel.hidden = false;

    if (summaryPlan) {
      summaryPlan.textContent = plan.plan_name || plan.slug;
    }

    if (summaryCycle) {
      summaryCycle.textContent = planTypeLabel(type);
    }

    if (summaryPrice) {
      if (trialDays > 0) {
        var trialTemplate = messages.trialThen || '%d-day free trial, then %s';
        summaryPrice.textContent = trialTemplate.replace('%d', String(trialDays)).replace('%s', priceLine);
      } else {
        summaryPrice.textContent = priceLine;
      }
    }
  }

  function ajaxPost(action, data) {
    return $.ajax({
      url: lbCheckout.ajaxUrl,
      method: 'POST',
      dataType: 'json',
      data: $.extend(
        {
          action: action,
          nonce: lbCheckout.nonce,
          apiUrl: lbCheckout.apiUrl,
        },
        data
      ),
    });
  }

  function fetchPlans(productSlug) {
    return ajaxPost('lb_checkout_plans', {
      product: productSlug,
    }).then(function (response) {
      if (!response || !response.success) {
        throw new Error((response && response.data && response.data.message) || lbCheckout.messages.errorGeneric);
      }

      priceInfoCache = response.data;
      return response.data;
    });
  }

  function serializeBillingForm(config) {
    var plan = selectedPlanFromUi(config);

    return {
      product: config.productSlug,
      gateway: config.gateway,
      planSlug: plan ? plan.slug : config.planSlug,
      planType: selectedPlanType(config),
      callbackUrl: config.callbackUrl,
      firstName: form.firstName.value.trim(),
      lastName: form.lastName.value.trim(),
      email: form.email.value.trim(),
      address: form.address.value.trim(),
      city: form.city.value.trim(),
      province: form.province.value.trim(),
      zip: form.zip.value.trim(),
      country: form.country.value.trim().toUpperCase(),
    };
  }

  function validateBillingForm() {
    if (!form || !form.checkValidity()) {
      form.reportValidity();
      return false;
    }

    return true;
  }

  function initCheckout(payload) {
    return ajaxPost('lb_checkout_init', payload).then(function (response) {
      if (!response || !response.success) {
        throw new Error((response && response.data && response.data.message) || lbCheckout.messages.errorGeneric);
      }

      return response.data;
    });
  }

  function loadPaddleScript() {
    if (window.Paddle) {
      return Promise.resolve(window.Paddle);
    }

    if (!paddleScriptPromise) {
      paddleScriptPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';
        script.async = true;
        script.onload = function () {
          resolve(window.Paddle);
        };
        script.onerror = function () {
          reject(new Error('Unable to load Paddle checkout.'));
        };
        document.body.appendChild(script);
      });
    }

    return paddleScriptPromise;
  }

  function withTransactionId(successUrl, transactionId) {
    if (!successUrl || !transactionId) {
      return successUrl;
    }

    try {
      var url = new URL(successUrl);

      if (!url.searchParams.get('_ptxn')) {
        url.searchParams.set('_ptxn', transactionId);
      }

      return url.toString();
    } catch (error) {
      var separator = successUrl.indexOf('?') !== -1 ? '&' : '?';
      return successUrl + separator + '_ptxn=' + encodeURIComponent(transactionId);
    }
  }

  function openPaddleCheckout(checkout) {
    return loadPaddleScript().then(function (Paddle) {
      var sandbox = !!checkout.sandbox;
      var clientSideToken = checkout.clientSideToken || '';
      var transactionId = checkout.transactionId || '';
      var successUrl = withTransactionId(checkout.successUrl, transactionId);
      var customer = checkout.customer || {};

      Paddle.Environment.set(sandbox ? 'sandbox' : 'production');

      if (paddleInitializedToken !== clientSideToken) {
        try {
          Paddle.Initialize({
            token: clientSideToken,
            eventCallback: function (event) {
              if (event && event.name === 'checkout.completed' && successUrl) {
                window.location.assign(successUrl);
              }
            },
          });
        } catch (error) {
          // Paddle allows Initialize only once per page.
        }

        paddleInitializedToken = clientSideToken;
      }

      var settings = {
        displayMode: 'overlay',
        allowLogout: false,
      };

      if (successUrl) {
        settings.successUrl = successUrl;
      }

      var payload = {
        transactionId: transactionId,
        settings: settings,
      };

      if (customer && Object.keys(customer).length > 0) {
        payload.customer = customer;
      }

      closeModal();
      Paddle.Checkout.open(payload);
    });
  }

  function loadStripeScript() {
    if (window.Stripe) {
      return Promise.resolve(window.Stripe);
    }

    if (!stripeScriptPromise) {
      stripeScriptPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.async = true;
        script.onload = function () {
          resolve(window.Stripe);
        };
        script.onerror = function () {
          reject(new Error('Unable to load Stripe.'));
        };
        document.body.appendChild(script);
      });
    }

    return stripeScriptPromise;
  }

  function mountStripeCard(publicKey) {
    if (!stripePanel) {
      return Promise.resolve();
    }

    stripePanel.hidden = false;

    return loadStripeScript().then(function (Stripe) {
      if (!publicKey) {
        throw new Error('Stripe is not configured for this product.');
      }

      if (!stripeInstance || stripeInstance._lbPublicKey !== publicKey) {
        stripeInstance = Stripe(publicKey);
        stripeInstance._lbPublicKey = publicKey;
        stripeCardElement = null;
      }

      if (stripeCardElement) {
        return;
      }

      var container = document.getElementById('lb-checkout-stripe-card');

      if (!container) {
        return;
      }

      container.innerHTML = '';
      var elements = stripeInstance.elements();
      stripeCardElement = elements.create('card', {
        style: {
          base: {
            fontSize: '14px',
            color: '#1d2327',
          },
        },
      });
      stripeCardElement.mount(container);
      stripeCardElement.on('change', function (event) {
        if (stripeErrors) {
          stripeErrors.textContent = event.error ? event.error.message : '';
        }
      });
    });
  }

  function createStripeToken() {
    if (!stripeInstance || !stripeCardElement) {
      return Promise.reject(new Error('Stripe is not ready.'));
    }

    return stripeInstance.createToken(stripeCardElement).then(function (result) {
      if (result.error) {
        throw new Error(result.error.message);
      }

      return result.token.id;
    });
  }

  function handleCheckoutResult(result) {
    if (result.redirectTo) {
      window.location.assign(result.redirectTo);
      return;
    }

    if (result.paddleCheckout) {
      return openPaddleCheckout(result.paddleCheckout);
    }

    if (result.success) {
      closeModal();
    }
  }

  function prepareModal(button) {
    activeButton = button;
    var config = readButtonConfig(button);

    clearError();
    submitButton.textContent = submitLabel();

    if (stripePanel) {
      stripePanel.hidden = config.gateway !== 'stripe';
    }

    return fetchPlans(config.productSlug)
      .then(function (info) {
        var plans = normalizePlans(info);
        populatePlanSelect(plans, config.planSlug || (plans[0] && plans[0].slug));
        populatePlanTypeSelect(selectedPlanFromUi(config), config.planType || info.cheapestPlanType);
        togglePlanFields(config);
        updateOrderSummary(config);

        if (config.gateway === 'stripe') {
          return mountStripeCard(info.stripePublicKey);
        }
      })
      .then(function () {
        openModal();
      });
  }

  function onSubmit() {
    if (!activeButton) {
      return;
    }

    var config = readButtonConfig(activeButton);

    if (!validateBillingForm()) {
      return;
    }

    clearError();
    setBusy(true);

    var payloadPromise = config.gateway === 'stripe'
      ? createStripeToken().then(function (token) {
          var payload = serializeBillingForm(config);
          payload.stripeToken = token;
          return payload;
        })
      : Promise.resolve(serializeBillingForm(config));

    payloadPromise
      .then(initCheckout)
      .then(handleCheckoutResult)
      .catch(function (error) {
        showError(error.message || lbCheckout.messages.errorGeneric);
      })
      .finally(function () {
        setBusy(false);
        if (submitButton) {
          submitButton.textContent = submitLabel();
        }
      });
  }

  $(document).on('click', '.lb-checkout-button', function (event) {
    event.preventDefault();
    prepareModal(this).catch(function (error) {
      showError(error.message || lbCheckout.messages.errorGeneric);
    });
  });

  $(document).on('click', '[data-lb-checkout-close]', function () {
    closeModal();
  });

  if (planSelect) {
    planSelect.addEventListener('change', function () {
      if (!activeButton) {
        return;
      }

      var config = readButtonConfig(activeButton);
      populatePlanTypeSelect(selectedPlanFromUi(config), '');
      togglePlanFields(config);
      updateOrderSummary(config);
    });
  }

  if (planTypeSelect) {
    planTypeSelect.addEventListener('change', function () {
      if (!activeButton) {
        return;
      }

      updateOrderSummary(readButtonConfig(activeButton));
    });
  }

  if (submitButton) {
    submitButton.addEventListener('click', onSubmit);
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
      closeModal();
    }
  });
})(jQuery);
