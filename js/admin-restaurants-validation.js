document.addEventListener('DOMContentLoaded', function () {
  var restaurantForm = document.getElementById('admin-restaurant-form');
  var mealForm = document.getElementById('admin-meal-form');

  function setError(errorId, field, message) {
    var errorElement = document.getElementById(errorId);
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = message ? 'block' : 'none';
    }
    if (field) {
      if (message) {
        field.classList.add('input-error');
      } else {
        field.classList.remove('input-error');
      }
    }
  }

  function isImageFileName(name) {
    return /\.(png|jpg|jpeg|webp|gif)$/i.test(String(name || ''));
  }

  function isTimeBetween(value, min, max) {
    return value >= min && value <= max;
  }

  if (restaurantForm) {
    restaurantForm.addEventListener('submit', function (event) {
      var valid = true;
      var ownerField = document.getElementById('owner_id');
      var nameField = document.getElementById('restaurant_name');
      var locationField = document.getElementById('restaurant_location');
      var phoneField = document.getElementById('restaurant_phone');
      var openField = document.getElementById('restaurant_open_time');
      var closeField = document.getElementById('restaurant_close_time');
      var descriptionField = document.getElementById('restaurant_description');
      var imageField = document.getElementById('restaurant_image');
      var requireImage = restaurantForm.getAttribute('data-require-image') === '1';
      var imageFile = imageField && imageField.files ? imageField.files[0] : null;

      var owner = ownerField ? ownerField.value.trim() : '';
      var name = nameField ? nameField.value.trim() : '';
      var location = locationField ? locationField.value.trim() : '';
      var phone = phoneField ? phoneField.value.trim() : '';
      var openTime = openField ? openField.value.trim() : '';
      var closeTime = closeField ? closeField.value.trim() : '';
      var description = descriptionField ? descriptionField.value.trim() : '';

      setError('owner_id-error', ownerField, '');
      setError('restaurant_name-error', nameField, '');
      setError('restaurant_location-error', locationField, '');
      setError('restaurant_phone-error', phoneField, '');
      setError('restaurant_open_time-error', openField, '');
      setError('restaurant_close_time-error', closeField, '');
      setError('restaurant_description-error', descriptionField, '');
      setError('restaurant_image-error', imageField, '');

      if (!/^\d+$/.test(owner) || parseInt(owner, 10) <= 0) {
        setError('owner_id-error', ownerField, 'Partner ID invalide.');
        valid = false;
      }
      if (!name || name.length < 2 || name.length > 100 || !/^[A-Za-z0-9\u00C0-\u024F\s'-]+$/.test(name)) {
        setError('restaurant_name-error', nameField, 'Nom invalide.');
        valid = false;
      }
      if (!location || location.length < 2 || location.length > 150) {
        setError('restaurant_location-error', locationField, 'Localisation invalide.');
        valid = false;
      }
      if (!/^\+?[0-9 ]{8,15}$/.test(phone)) {
        setError('restaurant_phone-error', phoneField, 'Telephone invalide.');
        valid = false;
      }

      if (!openTime) {
        setError('restaurant_open_time-error', openField, "Heure d'ouverture obligatoire.");
        valid = false;
      } else if (!isTimeBetween(openTime, '09:00', '22:00')) {
        setError('restaurant_open_time-error', openField, 'Doit etre entre 09:00 et 22:00.');
        valid = false;
      }

      if (!closeTime) {
        setError('restaurant_close_time-error', closeField, 'Heure de fermeture obligatoire.');
        valid = false;
      } else if (!isTimeBetween(closeTime, '09:00', '22:00')) {
        setError('restaurant_close_time-error', closeField, 'Doit etre entre 09:00 et 22:00.');
        valid = false;
      }

      if (openTime && closeTime && openTime >= closeTime) {
        setError('restaurant_close_time-error', closeField, 'La fermeture doit etre apres louverture.');
        valid = false;
      }

      if (!description || description.length < 10 || description.length > 1000) {
        setError('restaurant_description-error', descriptionField, 'Description invalide.');
        valid = false;
      }
      if (requireImage && !imageFile) {
        setError('restaurant_image-error', imageField, 'Image obligatoire.');
        valid = false;
      } else if (imageFile) {
        if (!isImageFileName(imageFile.name)) {
          setError('restaurant_image-error', imageField, 'Formats: png, jpg, jpeg, webp, gif.');
          valid = false;
        } else if (imageFile.size > 3 * 1024 * 1024) {
          setError('restaurant_image-error', imageField, 'Image max 3MB.');
          valid = false;
        }
      }

      if (!valid) {
        event.preventDefault();
      }
    });
  }

  if (mealForm) {
    var modeField = document.getElementById('meal_pricing_mode');
    var priceField = document.getElementById('meal_price');
    var priceGroup = document.getElementById('meal_price_group');
    var regimeCheckboxes = mealForm.querySelectorAll('input[name="regime_tags[]"]');
    var firstRegimeInput = regimeCheckboxes.length ? regimeCheckboxes[0] : null;

    function syncTagVisual(checkbox) {
      if (!checkbox) return;
      var label = checkbox.closest('.tag');
      if (!label) return;
      if (checkbox.checked) {
        label.classList.add('selected');
      } else {
        label.classList.remove('selected');
      }
    }

    function syncPriceField() {
      if (!modeField || !priceField) return;
      if (modeField.value === 'free') {
        priceField.value = '0';
        priceField.disabled = true;
        if (priceGroup) {
          priceGroup.classList.add('hidden');
        }
      } else {
        priceField.disabled = false;
        if (priceGroup) {
          priceGroup.classList.remove('hidden');
        }
      }
    }

    regimeCheckboxes.forEach(function (checkbox) {
      syncTagVisual(checkbox);
      checkbox.addEventListener('change', function () {
        syncTagVisual(checkbox);
        setError('meal_regime_tags-error', firstRegimeInput, '');
      });
    });

    if (modeField) {
      modeField.addEventListener('change', function () {
        syncPriceField();
        setError('meal_price-error', priceField, '');
      });
      syncPriceField();
    }

    mealForm.addEventListener('submit', function (event) {
      var valid = true;
      var nameField = document.getElementById('meal_name');
      var qtyField = document.getElementById('meal_quantity');
      var ingField = document.getElementById('meal_ingredients');
      var allergensField = document.getElementById('meal_allergens');
      var mode = modeField ? modeField.value : '';
      var price = priceField ? priceField.value.trim() : '';

      var name = nameField ? nameField.value.trim() : '';
      var qty = qtyField ? qtyField.value.trim() : '';
      var ingredients = ingField ? ingField.value.trim() : '';
      var allergens = allergensField ? allergensField.value.trim() : '';
      var selectedRegimes = mealForm.querySelectorAll('input[name="regime_tags[]"]:checked');

      setError('meal_name-error', nameField, '');
      setError('meal_quantity-error', qtyField, '');
      setError('meal_ingredients-error', ingField, '');
      setError('meal_regime_tags-error', firstRegimeInput, '');
      setError('meal_allergens-error', allergensField, '');
      setError('meal_pricing_mode-error', modeField, '');
      setError('meal_price-error', priceField, '');

      if (!name || name.length < 2 || name.length > 100 || !/^[A-Za-z0-9\u00C0-\u024F\s'-]+$/.test(name)) {
        setError('meal_name-error', nameField, 'Nom meal invalide.');
        valid = false;
      }
      if (!/^\d+$/.test(qty) || parseInt(qty, 10) <= 0 || parseInt(qty, 10) > 1000) {
        setError('meal_quantity-error', qtyField, 'Quantite invalide.');
        valid = false;
      }
      if (!ingredients || ingredients.length < 2 || ingredients.length > 1000 || !/^[A-Za-z\u00C0-\u024F\s,'-]+$/u.test(ingredients)) {
        setError('meal_ingredients-error', ingField, 'Ingredients invalides.');
        valid = false;
      }
      if (selectedRegimes.length === 0) {
        setError('meal_regime_tags-error', firstRegimeInput, 'Selectionner au moins un regime.');
        valid = false;
      }
      if (!allergens) {
        setError('meal_allergens-error', allergensField, 'Champ obligatoire.');
        valid = false;
      }
      if (mode !== 'free' && mode !== 'paid') {
        setError('meal_pricing_mode-error', modeField, 'Mode invalide.');
        valid = false;
      }
      if (mode === 'paid' && (!/^\d+(\.\d{1,2})?$/.test(price) || parseFloat(price) <= 0 || parseFloat(price) > 500)) {
        setError('meal_price-error', priceField, 'Prix invalide (> 0).');
        valid = false;
      }

      if (!valid) {
        event.preventDefault();
      }
    });
  }
});
