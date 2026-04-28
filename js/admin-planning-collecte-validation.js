document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('admin-collecte-form');
  if (!form) return;

  var restaurantField = document.getElementById('collecte_restaurant_id');
  var prefField = document.getElementById('collecte_pref_id');
  var userField = document.getElementById('collecte_user_id');
  var modeField = document.getElementById('collecte_mode_collecte');
  var addressGroup = document.getElementById('collecte_adresse_group');
  var addressField = document.getElementById('collecte_adresse_livraison');
  var addressLatField = document.getElementById('collecte_adresse_lat');
  var addressLngField = document.getElementById('collecte_adresse_lng');
  var timeField = document.getElementById('collecte_heure_souhaitee');
  var timeHint = document.getElementById('collecte_heure_hint');
  var mealsList = document.getElementById('collecte_meals_list');
  var mealsEmpty = document.getElementById('collecte_meals_empty');
  var itemsField = document.getElementById('collecte_items_json');
  var totalHidden = document.getElementById('collecte_montant_total');
  var totalDisplay = document.getElementById('collecte_montant_total_display');

  var restaurants = Array.isArray(window.ADMIN_COLLECTE_RESTAURANTS) ? window.ADMIN_COLLECTE_RESTAURANTS : [];
  var prefIds = Array.isArray(window.ADMIN_COLLECTE_PREF_IDS) ? window.ADMIN_COLLECTE_PREF_IDS : [];
  var userIds = Array.isArray(window.ADMIN_COLLECTE_USER_IDS) ? window.ADMIN_COLLECTE_USER_IDS : [];
  var prefIdSet = {};
  var userIdSet = {};
  prefIds.forEach(function (id) { prefIdSet[String(id)] = true; });
  userIds.forEach(function (id) { userIdSet[String(id)] = true; });
  var restaurantMap = {};
  restaurants.forEach(function (r) {
    restaurantMap[String(r.id_restaurant)] = r;
  });

  var selectedRestaurant = null;
  var selection = {};

  function setError(errorId, field, message) {
    var errorElement = document.getElementById(errorId);
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = message ? 'block' : 'none';
    }
    if (field) {
      if (message) field.classList.add('input-error');
      else field.classList.remove('input-error');
    }
  }

  function isPositiveInteger(value) {
    if (!/^\d+$/.test(String(value || '').trim())) return false;
    return parseInt(value, 10) > 0;
  }

  function parseHours(hours) {
    var h = String(hours || '').trim();
    var m = h.match(/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/);
    if (!m) return null;
    return { open: m[1], close: m[2] };
  }

  function syncAddressState() {
    var mode = modeField ? modeField.value : '';
    if (!addressField || !addressGroup) return;
    if (mode === 'delivery') {
      addressGroup.style.display = '';
      addressField.disabled = false;
      window.setTimeout(function () {
        var maps = window.__collecteAddressMaps || {};
        var map = maps.collecte_address_map;
        if (map && typeof map.invalidateSize === 'function') {
          map.invalidateSize();
        }
      }, 120);
    } else {
      addressGroup.style.display = 'none';
      addressField.disabled = true;
      addressField.value = '';
      if (addressLatField) addressLatField.value = '';
      if (addressLngField) addressLngField.value = '';
      setError('collecte_adresse_livraison-error', addressField, '');
    }
  }

  function getUnitPrice(meal) {
    if (!meal || meal.pricing_mode !== 'paid') return 0;
    var p = parseFloat(meal.price);
    return Number.isFinite(p) && p > 0 ? p : 0;
  }

  function selectedItemsPayload() {
    if (!selectedRestaurant || !Array.isArray(selectedRestaurant.meals)) return [];
    var items = [];
    selectedRestaurant.meals.forEach(function (meal) {
      var mealId = String(meal.meal_id || '');
      var qty = parseInt(selection[mealId] || 0, 10);
      if (mealId && qty > 0) {
        items.push({
          meal_id: mealId,
          quantity: qty
        });
      }
    });
    return items;
  }

  function recalcTotalAndItems() {
    var total = 0;
    var items = selectedItemsPayload();
    if (selectedRestaurant && Array.isArray(selectedRestaurant.meals)) {
      selectedRestaurant.meals.forEach(function (meal) {
        var mealId = String(meal.meal_id || '');
        var qty = parseInt(selection[mealId] || 0, 10);
        if (qty > 0) {
          total += getUnitPrice(meal) * qty;
        }
      });
    }
    var totalFixed = total.toFixed(2);
    if (totalHidden) totalHidden.value = totalFixed;
    if (totalDisplay) totalDisplay.value = totalFixed;
    if (itemsField) itemsField.value = JSON.stringify(items);
  }

  function updateMealRowButtons(row, qty, stock) {
    var minusBtn = row.querySelector('[data-action="minus"]');
    var plusBtn = row.querySelector('[data-action="plus"]');
    var valueBox = row.querySelector('.qty-value');
    if (valueBox) valueBox.textContent = String(qty);
    if (minusBtn) minusBtn.disabled = qty <= 0;
    if (plusBtn) plusBtn.disabled = qty >= stock || stock <= 0;
  }

  function renderMeals() {
    if (!mealsList) return;
    mealsList.innerHTML = '';

    if (!selectedRestaurant || !Array.isArray(selectedRestaurant.meals) || selectedRestaurant.meals.length === 0) {
      if (mealsEmpty) mealsEmpty.style.display = '';
      recalcTotalAndItems();
      return;
    }

    if (mealsEmpty) mealsEmpty.style.display = 'none';

    selectedRestaurant.meals.forEach(function (meal) {
      var mealId = String(meal.meal_id || '');
      var stock = Math.max(0, parseInt(meal.quantity || 0, 10));
      if (!mealId) return;
      if (!selection[mealId]) selection[mealId] = 0;

      var row = document.createElement('div');
      row.className = 'meal-row';

      var left = document.createElement('div');
      var name = document.createElement('div');
      name.className = 'meal-name';
      name.textContent = String(meal.meal_name || mealId);
      var meta = document.createElement('div');
      meta.className = 'meal-meta';
      var unitPrice = getUnitPrice(meal);
      meta.textContent = 'Prix: ' + unitPrice.toFixed(2) + ' DT | Stock dispo: ' + stock;
      left.appendChild(name);
      left.appendChild(meta);

      var controls = document.createElement('div');
      controls.className = 'qty-wrap';
      var minusBtn = document.createElement('button');
      minusBtn.type = 'button';
      minusBtn.className = 'qty-btn';
      minusBtn.setAttribute('data-action', 'minus');
      minusBtn.textContent = '-';

      var valueBox = document.createElement('span');
      valueBox.className = 'qty-value';
      valueBox.textContent = '0';

      var plusBtn = document.createElement('button');
      plusBtn.type = 'button';
      plusBtn.className = 'qty-btn';
      plusBtn.setAttribute('data-action', 'plus');
      plusBtn.textContent = '+';

      minusBtn.addEventListener('click', function () {
        var current = parseInt(selection[mealId] || 0, 10);
        if (current > 0) selection[mealId] = current - 1;
        updateMealRowButtons(row, selection[mealId], stock);
        recalcTotalAndItems();
      });

      plusBtn.addEventListener('click', function () {
        var current = parseInt(selection[mealId] || 0, 10);
        if (current < stock) selection[mealId] = current + 1;
        updateMealRowButtons(row, selection[mealId], stock);
        recalcTotalAndItems();
      });

      controls.appendChild(minusBtn);
      controls.appendChild(valueBox);
      controls.appendChild(plusBtn);

      row.appendChild(left);
      row.appendChild(controls);
      mealsList.appendChild(row);

      updateMealRowButtons(row, parseInt(selection[mealId] || 0, 10), stock);
    });

    recalcTotalAndItems();
  }

  function syncRestaurantContext() {
    var restaurantId = restaurantField ? String(restaurantField.value || '').trim() : '';
    selectedRestaurant = restaurantMap[restaurantId] || null;
    selection = {};

    if (selectedRestaurant) {
      var hours = parseHours(selectedRestaurant.horaires);
      if (timeHint) {
        timeHint.textContent = 'Horaires du resto: ' + (hours ? (hours.open + ' - ' + hours.close) : 'non definis');
      }
    } else {
      if (timeHint) timeHint.textContent = 'Horaires du resto: --';
    }

    renderMeals();
  }

  function clearAllErrors() {
    setError('collecte_restaurant_id-error', restaurantField, '');
    setError('collecte_pref_id-error', prefField, '');
    setError('collecte_user_id-error', userField, '');
    setError('collecte_mode_collecte-error', modeField, '');
    setError('collecte_adresse_livraison-error', addressField, '');
    setError('collecte_heure_souhaitee-error', timeField, '');
    setError('collecte_montant_total-error', totalDisplay, '');
    setError('collecte_items_json-error', mealsList, '');
  }

  function validateAgainstRestaurantHours(requestedTime) {
    if (!selectedRestaurant) return false;
    var hours = parseHours(selectedRestaurant.horaires);
    if (!hours || hours.open >= hours.close) return false;
    return requestedTime >= hours.open && requestedTime <= hours.close;
  }

  if (modeField) modeField.addEventListener('change', syncAddressState);
  if (restaurantField) {
    restaurantField.addEventListener('input', syncRestaurantContext);
    restaurantField.addEventListener('change', syncRestaurantContext);
  }

  syncAddressState();
  syncRestaurantContext();

  form.addEventListener('submit', function (event) {
    clearAllErrors();
    recalcTotalAndItems();

    var valid = true;
    var restaurantId = restaurantField ? String(restaurantField.value || '').trim() : '';
    var prefId = prefField ? String(prefField.value || '').trim() : '';
    var userId = userField ? String(userField.value || '').trim() : '';
    var mode = modeField ? modeField.value : '';
    var address = addressField ? String(addressField.value || '').trim() : '';
    var lat = addressLatField ? Number(addressLatField.value) : NaN;
    var lng = addressLngField ? Number(addressLngField.value) : NaN;
    var requestedTime = timeField ? String(timeField.value || '').trim() : '';

    if (!isPositiveInteger(restaurantId) || !selectedRestaurant) {
      setError('collecte_restaurant_id-error', restaurantField, 'ID restaurant invalide ou inexistant.');
      valid = false;
    }
    if (!isPositiveInteger(prefId)) {
      setError('collecte_pref_id-error', prefField, 'ID preference invalide.');
      valid = false;
    } else if (!prefIdSet[prefId]) {
      setError('collecte_pref_id-error', prefField, 'ID preference inexistant.');
      valid = false;
    }
    if (!isPositiveInteger(userId)) {
      setError('collecte_user_id-error', userField, 'ID user invalide.');
      valid = false;
    } else if (!userIdSet[userId]) {
      setError('collecte_user_id-error', userField, 'ID user inexistant.');
      valid = false;
    }
    if (mode !== 'pickup' && mode !== 'delivery') {
      setError('collecte_mode_collecte-error', modeField, 'Mode invalide.');
      valid = false;
    }
    if (mode === 'delivery' && (address.length < 5 || address.length > 255)) {
      setError('collecte_adresse_livraison-error', addressField, 'Adresse obligatoire (5-255).');
      valid = false;
    }
    if (mode === 'delivery') {
      var coordsValid = Number.isFinite(lat) && Number.isFinite(lng)
        && lat >= -90 && lat <= 90
        && lng >= -180 && lng <= 180;
      if (!coordsValid) {
        setError('collecte_adresse_livraison-error', addressField, 'Choisis une adresse exacte depuis la carte.');
        valid = false;
      }
    }
    if (!/^\d{2}:\d{2}$/.test(requestedTime)) {
      setError('collecte_heure_souhaitee-error', timeField, 'Heure invalide.');
      valid = false;
    } else if (!validateAgainstRestaurantHours(requestedTime)) {
      setError('collecte_heure_souhaitee-error', timeField, 'Heure hors horaires du restaurant.');
      valid = false;
    }

    var items = [];
    try {
      items = JSON.parse(itemsField ? itemsField.value : '[]');
    } catch (e) {
      items = [];
    }
    if (!Array.isArray(items) || items.length === 0) {
      setError('collecte_items_json-error', mealsList, 'Choisis au moins un meal.');
      valid = false;
    } else {
      var stockInvalid = false;
      items.forEach(function (it) {
        var meal = null;
        if (selectedRestaurant && Array.isArray(selectedRestaurant.meals)) {
          for (var i = 0; i < selectedRestaurant.meals.length; i += 1) {
            if (String(selectedRestaurant.meals[i].meal_id || '') === String(it.meal_id || '')) {
              meal = selectedRestaurant.meals[i];
              break;
            }
          }
        }
        if (!meal) {
          stockInvalid = true;
          return;
        }
        var qty = parseInt(it.quantity || 0, 10);
        var stock = parseInt(meal.quantity || 0, 10);
        if (!Number.isFinite(qty) || qty <= 0 || qty > stock) {
          stockInvalid = true;
        }
      });
      if (stockInvalid) {
        setError('collecte_items_json-error', mealsList, 'Quantite invalide par rapport au stock.');
        valid = false;
      }
    }

    if (!valid) {
      event.preventDefault();
    }
  });
});
