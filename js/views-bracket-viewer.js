(function (Drupal, once, drupalSettings) {
  const getSettings = (viewId, bracketId) => {
    const views = drupalSettings.bracketManager && drupalSettings.bracketManager.views;
    return views && views[viewId] ? views[viewId][bracketId] : null;
  };

  Drupal.behaviors.bracketManagerViewsViewer = {
    attach(context) {
      once('bracket-manager-views-viewer', '.js-brackets-viewer', context).forEach((wrapper) => {
        const viewId = wrapper.getAttribute('data-view-id') || 'default';
        const bracketId = wrapper.getAttribute('data-bracket-viewer-id');
        const settings = getSettings(viewId, bracketId);
        if (!settings || !window.bracketsViewer) {
          return;
        }

        // brackets-viewer.js expects a root element selector for rendering
        const data = settings.data || {};
        if (!data.stages || !data.stages.length || !data.matches || !data.matches.length || !data.participants || !data.participants.length) {
          wrapper.innerHTML = `<div class="messages messages--warning">${Drupal.t('Bracket data is incomplete for @name.', { '@name': settings.name || 'tournament' })}</div>`;
          return;
        }

        window.bracketsViewer.render(data, {
          selector: `#${wrapper.id}`,
          clear: true,
        });
      });
    },
  };
})(Drupal, once, drupalSettings);
