(function (Drupal, once) {
  const libraryUrl = 'https://esm.sh/brackets-manager@1.8.1?bundle';
  let managerPromise;

  const loadManager = () => {
    if (!managerPromise) {
      managerPromise = import(libraryUrl).catch(() => null);
    }
    return managerPromise;
  };

  const parseParticipants = (form) => {
    const select = form.querySelector('select[name*="participants"]');
    if (select) {
      return Array.from(select.selectedOptions)
        .map((option) => option.textContent.trim())
        .filter(Boolean);
    }

    const field = form.querySelector('input[name*="participants"]') || form.querySelector('[name^="participants"]');
    if (!field) {
      return [];
    }
    return field.value
      .split(',')
      .map((entry) => entry.trim().replace(/\s*\([^)]*\)\s*$/, ''))
      .filter(Boolean);
  };

  const parseBracketData = (form) => {
    const field = form.querySelector('textarea[name*="bracket_data"]');
    if (!field || !field.value.trim()) {
      return null;
    }
    try {
      return JSON.parse(field.value);
    }
    catch (error) {
      return { __error: error };
    }
  };

  const renderPreview = async (preview) => {
    const form = preview.closest('form');
    const canvas = preview.querySelector('.bracket-manager-preview__canvas');
    if (!form || !canvas) {
      return;
    }

    const bracket = parseBracketData(form);
    const participants = parseParticipants(form);

    if (bracket && bracket.__error) {
      canvas.innerHTML = '';
      const error = document.createElement('div');
      error.className = 'messages messages--error';
      error.textContent = `Invalid JSON in bracket data: ${bracket.__error.message}`;
      canvas.appendChild(error);
      return;
    }

    const lib = await loadManager();
    canvas.innerHTML = '';

    if (!lib || !lib.helpers) {
      const warning = document.createElement('div');
      warning.className = 'messages messages--warning';
      warning.textContent = 'brackets-manager.js could not be loaded. You can still save the tournament.';
      canvas.appendChild(warning);
      if (bracket) {
        const pre = document.createElement('pre');
        pre.className = 'bracket-manager-preview__json';
        pre.textContent = JSON.stringify(bracket, null, 2);
        canvas.appendChild(pre);
      }
      return;
    }

    // Leverage helpers from brackets-manager.js to validate seeding and normalize.
    const { helpers } = lib;
    let seeding = participants;
    if (helpers.fixSeeding) {
      seeding = helpers.fixSeeding(seeding);
    }
    if (helpers.ensureNoDuplicates) {
      try {
        helpers.ensureNoDuplicates(seeding);
      }
      catch (error) {
        const errorBox = document.createElement('div');
        errorBox.className = 'messages messages--error';
        errorBox.textContent = error.message;
        canvas.appendChild(errorBox);
      }
    }

    const summary = document.createElement('div');
    summary.className = 'bracket-manager-preview__summary';
    summary.innerHTML = `<div><strong>Teams:</strong> ${seeding.length}</div>`;
    const list = document.createElement('ol');
    list.className = 'bracket-manager-preview__participants';
    seeding.forEach((name) => {
      const li = document.createElement('li');
      li.textContent = name;
      list.appendChild(li);
    });
    summary.appendChild(list);
    canvas.appendChild(summary);

    const dataPre = document.createElement('pre');
    dataPre.className = 'bracket-manager-preview__json';
    dataPre.textContent = JSON.stringify(bracket || { seeding }, null, 2);
    canvas.appendChild(dataPre);
  };

  Drupal.behaviors.bracketManagerForm = {
    attach(context) {
      once('bracket-manager-form', '.bracket-manager-preview', context).forEach((preview) => {
        const form = preview.closest('form');
        if (!form) {
          return;
        }

        const participantsField = form.querySelector('select[name*="participants"]') || form.querySelector('input[name*="participants"]') || form.querySelector('[name^="participants"]');
        const bracketField = form.querySelector('textarea[name*="bracket_data"]');
        const refresh = () => renderPreview(preview);

        if (participantsField) {
          const updateParticipants = Drupal.debounce(() => {
            autoUpdateBracketData(form);
            refresh();
          }, 300);
          participantsField.addEventListener('input', updateParticipants);
          participantsField.addEventListener('change', updateParticipants);
        }
        if (bracketField) {
          bracketField.addEventListener('input', Drupal.debounce(() => {
            bracketField.dataset.userEdited = '1';
            refresh();
          }, 300));
        }

        refresh();
      });
    },
  };

  /**
   * When participants change, prefill bracket JSON if the user has not edited it.
   */
  function autoUpdateBracketData(form) {
    const participants = parseParticipants(form);
    const bracketField = form.querySelector('textarea[name*="bracket_data"]');
    if (!bracketField || bracketField.dataset.userEdited) {
      return;
    }
    const data = { participants: participants.map((name, index) => ({ id: index + 1, name })) };
    bracketField.value = JSON.stringify(data, null, 2);
  }
})(Drupal, once);
