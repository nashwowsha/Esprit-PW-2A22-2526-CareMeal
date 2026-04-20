document.addEventListener('DOMContentLoaded', function () {
  var preferenceForm = document.getElementById('admin-pref-form');
  var addOptionForm = document.getElementById('admin-regime-option-form');
  var editOptionForm = document.getElementById('admin-regime-option-edit-form');
  var deleteOptionForm = document.getElementById('admin-regime-option-delete-form');

  var regimeOptions = Array.isArray(window.REGIME_OPTIONS) ? window.REGIME_OPTIONS : [];

  var idUserField = document.getElementById('id_user');
  var allergiesField = document.getElementById('allergies');
  var localisationField = document.getElementById('localisation');

  var idUserError = document.getElementById('id_user-error');
  var regimesError = document.getElementById('regimes-error');
  var allergiesError = document.getElementById('allergies-error');
  var localisationError = document.getElementById('localisation-error');

  var optionLabelField = document.getElementById('option_label');
  var optionIconFileField = document.getElementById('option_icon_file');
  var optionLabelError = document.getElementById('option_label-error');
  var optionIconError = document.getElementById('option_icon_class-error');
  var optionIconPreview = document.getElementById('option_icon_preview');

  var editOptionIdField = document.getElementById('edit_option_id');
  var editOptionLabelField = document.getElementById('edit_option_label');
  var editOptionIconFileField = document.getElementById('edit_option_icon_file');
  var editOptionIdError = document.getElementById('edit_option_id-error');
  var editOptionLabelError = document.getElementById('edit_option_label-error');
  var editOptionIconError = document.getElementById('edit_option_icon_class-error');
  var editOptionPreview = document.getElementById('edit_option_icon_preview');

  var deleteOptionIdField = document.getElementById('delete_option_id');
  var deleteOptionIdError = document.getElementById('delete_option_id-error');

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

  function getSelectedRegimes() {
    return document.querySelectorAll('input[name="regimes[]"]:checked');
  }

  function toggleTagSelectionVisual() {
    var checkboxes = document.querySelectorAll('.tag input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
      var checkbox = checkboxes[i];
      var tag = checkbox.closest('.tag');
      if (!tag) {
        continue;
      }

      if (checkbox.checked) {
        tag.classList.add('selected');
      } else {
        tag.classList.remove('selected');
      }

      checkbox.addEventListener('change', function () {
        var parentTag = this.closest('.tag');
        if (!parentTag) {
          return;
        }

        if (this.checked) {
          parentTag.classList.add('selected');
        } else {
          parentTag.classList.remove('selected');
        }
      });
    }
  }

  function setupIconChoiceSelection(container) {
    if (!container) {
      return;
    }

    var choices = container.querySelectorAll('.icon-choice');
    for (var i = 0; i < choices.length; i++) {
      (function (choice) {
        var radio = choice.querySelector('input[type="radio"]');
        if (!radio) {
          return;
        }

        function refreshChoice() {
          if (radio.checked) {
            choice.classList.add('selected');
          } else {
            choice.classList.remove('selected');
          }
        }

        refreshChoice();
        radio.addEventListener('change', function () {
          var all = container.querySelectorAll('.icon-choice input[type="radio"]');
          for (var j = 0; j < all.length; j++) {
            var otherChoice = all[j].closest('.icon-choice');
            if (otherChoice) {
              otherChoice.classList.remove('selected');
            }
          }
          refreshChoice();
        });
      })(choices[i]);
    }
  }

  function getRadioValue(form, name) {
    if (!form) {
      return '';
    }
    var selected = form.querySelector('input[name="' + name + '"]:checked');
    return selected ? selected.value : '';
  }

  function setRadioValue(form, name, value) {
    if (!form) {
      return;
    }
    var radios = form.querySelectorAll('input[name="' + name + '"]');
    for (var i = 0; i < radios.length; i++) {
      radios[i].checked = radios[i].value === value;
      var choice = radios[i].closest('.icon-choice');
      if (choice) {
        if (radios[i].checked) {
          choice.classList.add('selected');
        } else {
          choice.classList.remove('selected');
        }
      }
    }
  }

  function findOptionById(idValue) {
    var idText = String(idValue || '');
    for (var i = 0; i < regimeOptions.length; i++) {
      if (String(regimeOptions[i].id) === idText) {
        return regimeOptions[i];
      }
    }

    return null;
  }

  function isValidOptionLabel(label) {
    if (!label) {
      return false;
    }

    if (label.length < 2 || label.length > 50) {
      return false;
    }

    if (/\d/.test(label)) {
      return false;
    }

    return /^[A-Za-z\u00C0-\u024F\s]+$/.test(label);
  }

  function labelExists(label, excludeId) {
    var target = String(label || '').trim().toLowerCase();
    var excludeText = String(excludeId || '');

    for (var i = 0; i < regimeOptions.length; i++) {
      var current = regimeOptions[i];
      var currentLabel = String(current.label || '').trim().toLowerCase();
      if (currentLabel === target && String(current.id) !== excludeText) {
        return true;
      }
    }

    return false;
  }

  function isImageFileName(name) {
    return /\.(png|jpg|jpeg|webp|gif|svg)$/i.test(String(name || ''));
  }

  function setPreview(previewElement, src) {
    if (!previewElement) {
      return;
    }

    if (src) {
      previewElement.src = src;
      previewElement.style.display = 'block';
    } else {
      previewElement.removeAttribute('src');
      previewElement.style.display = 'none';
    }
  }

  function bindPreviewFromFile(fileInput, previewElement) {
    if (!fileInput || !previewElement) {
      return;
    }

    fileInput.addEventListener('change', function () {
      var file = this.files && this.files[0] ? this.files[0] : null;
      if (!file) {
        setPreview(previewElement, '');
        return;
      }

      var reader = new FileReader();
      reader.onload = function (event) {
        setPreview(previewElement, event.target ? event.target.result : '');
      };
      reader.readAsDataURL(file);
    });
  }

  function updateIconModeUI(form, faPickerId, uploadPickerId) {
    if (!form) {
      return;
    }

    var mode = getRadioValue(form, 'icon_mode') || 'fa';
    var faPicker = document.getElementById(faPickerId);
    var uploadPicker = document.getElementById(uploadPickerId);

    if (faPicker) {
      faPicker.style.display = mode === 'fa' ? 'block' : 'none';
    }

    if (uploadPicker) {
      uploadPicker.style.display = mode === 'upload' ? 'block' : 'none';
    }
  }

  function bindIconModeToggle(form, faPickerId, uploadPickerId) {
    if (!form) {
      return;
    }

    var radios = form.querySelectorAll('input[name="icon_mode"]');
    for (var i = 0; i < radios.length; i++) {
      radios[i].addEventListener('change', function () {
        updateIconModeUI(form, faPickerId, uploadPickerId);
      });
    }

    updateIconModeUI(form, faPickerId, uploadPickerId);
  }

  function fillEditFormFromSelection() {
    if (!editOptionForm || !editOptionIdField || !editOptionLabelField) {
      return;
    }

    var selected = findOptionById(editOptionIdField.value);
    if (!selected) {
      editOptionLabelField.value = '';
      return;
    }

    editOptionLabelField.value = selected.label || '';

    if ((selected.icon_type || 'fa') === 'image') {
      setRadioValue(editOptionForm, 'icon_mode', 'upload');
      setPreview(editOptionPreview, selected.icon_image ? ('../' + selected.icon_image) : '');
    } else {
      setRadioValue(editOptionForm, 'icon_mode', 'fa');
      setRadioValue(editOptionForm, 'icon_class', selected.icon || 'fa-utensils');
      setPreview(editOptionPreview, '');
    }

    if (editOptionIconFileField) {
      editOptionIconFileField.value = '';
    }

    updateIconModeUI(editOptionForm, 'edit-fa-picker', 'edit-upload-picker');
  }

  toggleTagSelectionVisual();

  if (addOptionForm) {
    setupIconChoiceSelection(document.getElementById('add-fa-picker'));
    bindIconModeToggle(addOptionForm, 'add-fa-picker', 'add-upload-picker');
  }

  if (editOptionForm) {
    setupIconChoiceSelection(document.getElementById('edit-fa-picker'));
    bindIconModeToggle(editOptionForm, 'edit-fa-picker', 'edit-upload-picker');
  }

  bindPreviewFromFile(optionIconFileField, optionIconPreview);
  bindPreviewFromFile(editOptionIconFileField, editOptionPreview);

  if (editOptionIdField) {
    fillEditFormFromSelection();
    editOptionIdField.addEventListener('change', fillEditFormFromSelection);
  }

  if (deleteOptionIdField && editOptionIdField) {
    deleteOptionIdField.value = editOptionIdField.value;
    editOptionIdField.addEventListener('change', function () {
      deleteOptionIdField.value = editOptionIdField.value;
    });
    deleteOptionIdField.addEventListener('change', function () {
      editOptionIdField.value = deleteOptionIdField.value;
      fillEditFormFromSelection();
    });
  }

  if (preferenceForm) {
    preferenceForm.addEventListener('submit', function (event) {
      var isValid = true;

      var idUser = idUserField ? idUserField.value.trim() : '';
      var allergies = allergiesField ? allergiesField.value.trim() : '';
      var localisation = localisationField ? localisationField.value.trim() : '';
      var selectedRegimes = getSelectedRegimes();

      setError(idUserError, idUserField, '');
      setError(regimesError, null, '');
      setError(allergiesError, allergiesField, '');
      setError(localisationError, localisationField, '');

      if (!/^\d+$/.test(idUser) || parseInt(idUser, 10) <= 0) {
        setError(idUserError, idUserField, 'User ID must be a positive integer.');
        isValid = false;
      }

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
      }

      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  if (addOptionForm) {
    addOptionForm.addEventListener('submit', function (event) {
      var isValid = true;
      var label = optionLabelField ? optionLabelField.value.trim() : '';
      var mode = getRadioValue(addOptionForm, 'icon_mode') || 'fa';
      var selectedIcon = getRadioValue(addOptionForm, 'icon_class');
      var uploadedFile = optionIconFileField && optionIconFileField.files ? optionIconFileField.files[0] : null;

      setError(optionLabelError, optionLabelField, '');
      setError(optionIconError, null, '');

      if (!isValidOptionLabel(label)) {
        setError(optionLabelError, optionLabelField, 'Use letters/spaces only (2 to 50 chars, no numbers).');
        isValid = false;
      } else if (labelExists(label, '')) {
        setError(optionLabelError, optionLabelField, 'This option already exists.');
        isValid = false;
      }

      if (mode === 'upload') {
        if (!uploadedFile) {
          setError(optionIconError, null, 'Please choose an image file.');
          isValid = false;
        } else if (!isImageFileName(uploadedFile.name)) {
          setError(optionIconError, null, 'Allowed formats: png, jpg, jpeg, webp, gif, svg.');
          isValid = false;
        }
      } else {
        if (!selectedIcon) {
          setError(optionIconError, null, 'Please choose a built-in icon.');
          isValid = false;
        }
      }

      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  if (editOptionForm) {
    editOptionForm.addEventListener('submit', function (event) {
      var isValid = true;
      var selectedId = editOptionIdField ? editOptionIdField.value : '';
      var selectedOption = findOptionById(selectedId);
      var label = editOptionLabelField ? editOptionLabelField.value.trim() : '';
      var mode = getRadioValue(editOptionForm, 'icon_mode') || 'fa';
      var selectedIcon = getRadioValue(editOptionForm, 'icon_class');
      var uploadedFile = editOptionIconFileField && editOptionIconFileField.files ? editOptionIconFileField.files[0] : null;

      setError(editOptionIdError, editOptionIdField, '');
      setError(editOptionLabelError, editOptionLabelField, '');
      setError(editOptionIconError, null, '');

      if (!selectedId || selectedId === '0') {
        setError(editOptionIdError, editOptionIdField, 'Please choose an option.');
        isValid = false;
      }

      if (!isValidOptionLabel(label)) {
        setError(editOptionLabelError, editOptionLabelField, 'Use letters/spaces only (2 to 50 chars, no numbers).');
        isValid = false;
      } else if (labelExists(label, selectedId)) {
        setError(editOptionLabelError, editOptionLabelField, 'Another option already has this label.');
        isValid = false;
      }

      if (mode === 'upload') {
        var currentHasImage = selectedOption && (selectedOption.icon_type || 'fa') === 'image' && selectedOption.icon_image;
        if (!uploadedFile && !currentHasImage) {
          setError(editOptionIconError, null, 'Please choose an image file.');
          isValid = false;
        } else if (uploadedFile && !isImageFileName(uploadedFile.name)) {
          setError(editOptionIconError, null, 'Allowed formats: png, jpg, jpeg, webp, gif, svg.');
          isValid = false;
        }
      } else {
        if (!selectedIcon) {
          setError(editOptionIconError, null, 'Please choose a built-in icon.');
          isValid = false;
        }
      }

      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  if (deleteOptionForm) {
    deleteOptionForm.addEventListener('submit', function (event) {
      var selectedId = deleteOptionIdField ? deleteOptionIdField.value : '';
      setError(deleteOptionIdError, deleteOptionIdField, '');

      if (!selectedId || selectedId === '0') {
        setError(deleteOptionIdError, deleteOptionIdField, 'Please choose an option.');
        event.preventDefault();
      }
    });
  }
});
