document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('student-pref-form');
  if (!form) {
    return;
  }

  var allergiesField = document.getElementById('pref-allergies');
  var localisationField = document.getElementById('pref-localisation');
  var localisationLatField = document.getElementById('pref-localisation-lat');
  var localisationLngField = document.getElementById('pref-localisation-lng');

  var regimesError = document.getElementById('regimes-error');
  var allergiesError = document.getElementById('allergies-error');
  var localisationError = document.getElementById('localisation-error');

  function setError(errorElement, fieldElement, message) {
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = message ? 'block' : 'none';
    }

    if (fieldElement) {
      if (message) {
        fieldElement.classList.add('pref-input-error');
      } else {
        fieldElement.classList.remove('pref-input-error');
      }
    }
  }

  function splitList(value) {
    var parts = String(value || '').split(/[;,]+/);
    var clean = [];

    for (var i = 0; i < parts.length; i++) {
      var item = parts[i].trim();
      if (item) {
        clean.push(item);
      }
    }

    return clean;
  }

  form.addEventListener('submit', function (event) {
    var isValid = true;

    var selectedRegimes = form.querySelectorAll('input[name="regimes[]"]:checked');
    var allergies = allergiesField ? allergiesField.value.trim() : '';
    var localisation = localisationField ? localisationField.value.trim() : '';
    var localisationLat = localisationLatField ? localisationLatField.value.trim() : '';
    var localisationLng = localisationLngField ? localisationLngField.value.trim() : '';

    setError(regimesError, null, '');
    setError(allergiesError, allergiesField, '');
    setError(localisationError, localisationField, '');

    if (selectedRegimes.length === 0) {
      setError(regimesError, null, 'Please select at least one regime.');
      isValid = false;
    } else if (selectedRegimes.length > 4) {
      setError(regimesError, null, 'Maximum 4 regimes allowed.');
      isValid = false;
    }

    if (!allergies) {
      setError(allergiesError, allergiesField, 'Allergies field is required.');
      isValid = false;
    } else if (/\d/.test(allergies)) {
      setError(allergiesError, allergiesField, 'Allergies cannot contain numbers.');
      isValid = false;
    } else if (allergies.length > 1000) {
      setError(allergiesError, allergiesField, 'Allergies must be 1000 characters or less.');
      isValid = false;
    } else {
      var allergyItems = splitList(allergies);
      var seen = [];
      var allergyPattern = /^[A-Za-z\u00C0-\u024F\s'-]+$/;

      if (allergyItems.length === 0) {
        setError(allergiesError, allergiesField, 'Add at least one allergy item.');
        isValid = false;
      } else {
        for (var i = 0; i < allergyItems.length; i++) {
          var token = allergyItems[i];
          var normalized = token.toLowerCase();

          if (seen.indexOf(normalized) !== -1) {
            setError(allergiesError, allergiesField, 'Duplicate allergy items are not allowed.');
            isValid = false;
            break;
          }

          if (!allergyPattern.test(token)) {
            setError(allergiesError, allergiesField, 'Use only letters, spaces, apostrophe or hyphen.');
            isValid = false;
            break;
          }

          seen.push(normalized);
        }
      }
    }

    if (!localisation) {
      setError(localisationError, localisationField, 'Localisation field is required.');
      isValid = false;
    } else if (localisation.length > 1000) {
      setError(localisationError, localisationField, 'Localisation must be 1000 characters or less.');
      isValid = false;
    } else {
      var lat = Number(localisationLat);
      var lng = Number(localisationLng);
      if (!localisationLat || !localisationLng || !Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        setError(localisationError, localisationField, 'Choose the localisation on the map.');
        isValid = false;
      }
    }

    if (!isValid) {
      event.preventDefault();
    }
  });
});
