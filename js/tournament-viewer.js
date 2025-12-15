(function (Drupal, once, drupalSettings) {
  const libraryUrl = 'https://esm.sh/brackets-manager@1.8.1?bundle';
  let managerPromise;

  const loadManager = () => {
    if (!managerPromise) {
      managerPromise = import(libraryUrl).catch(() => null);
    }
    return managerPromise;
  };

  Drupal.behaviors.bracketManagerViewer = {
    attach(context) {
      once('bracket-manager-viewer', '.bracket-manager-viewer', context).forEach(async (wrapper) => {
        const canvas = wrapper.querySelector('.bracket-manager-viewer__canvas') || wrapper;
        const id = wrapper.getAttribute('data-tournament-id');
        const settings = (drupalSettings.bracketManager && drupalSettings.bracketManager.tournaments) ? drupalSettings.bracketManager.tournaments[id] : null;

        if (!settings) {
          return;
        }

        const participantsRaw = settings.participants || [];
        const participants = Array.isArray(participantsRaw)
          ? participantsRaw
          : (participantsRaw || '')
            .split(',')
            .map((entry) => entry.trim())
            .filter(Boolean);

        let parsedBracket = null;
        if (settings.data) {
          try {
            parsedBracket = JSON.parse(settings.data);
          }
          catch (error) {
            const errorBox = document.createElement('div');
            errorBox.className = 'messages messages--error';
            errorBox.textContent = `Bracket JSON is invalid: ${error.message}`;
            canvas.appendChild(errorBox);
            return;
          }
        }

        const lib = await loadManager();

        const header = document.createElement('div');
        header.className = 'bracket-manager-viewer__header';
        header.textContent = settings.name || 'Tournament';
        canvas.appendChild(header);

        if (!lib || !lib.helpers) {
          const warning = document.createElement('div');
          warning.className = 'messages messages--warning';
          warning.textContent = 'brackets-manager.js could not be loaded. Showing raw data.';
          canvas.appendChild(warning);
        }
        else if (lib.helpers.ensureEvenSized) {
          try {
            lib.helpers.ensureEvenSized(participants);
          }
          catch (error) {
            const info = document.createElement('div');
            info.className = 'messages messages--warning';
            info.textContent = error.message;
            canvas.appendChild(info);
          }
        }

        const participantList = document.createElement('ol');
        participantList.className = 'bracket-manager-viewer__participants';
        participants.forEach((name) => {
          const li = document.createElement('li');
          li.textContent = name;
          participantList.appendChild(li);
        });
        canvas.appendChild(participantList);

        const pre = document.createElement('pre');
        pre.className = 'bracket-manager-viewer__json';
        pre.textContent = JSON.stringify(parsedBracket || { seeding: participants }, null, 2);
        canvas.appendChild(pre);
      });
    },
  };
})(Drupal, once, drupalSettings);
