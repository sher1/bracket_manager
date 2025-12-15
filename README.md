## INTRODUCTION

The Bracket Manager module adds a custom `Tournament` content entity with an admin UI to capture tournament metadata, participants, and bracket JSON. The edit form and the entity view leverage [brackets-manager.js](https://github.com/Drarig29/brackets-manager.js/) (loaded via esm.sh) to normalize seeding and surface quick validation feedback.

## REQUIREMENTS

- Drupal 10/11
- Access to load `https://esm.sh/brackets-manager@1.8.1?bundle` from the browser (no PHP dependencies are required).

## INSTALLATION

Install as you would normally install a contributed Drupal module. See https://www.drupal.org/node/895232 for further information.

After enabling:

1. Navigate to `Admin » Content » Add » Tournament`.
2. Add a tournament with participants (selected via autocomplete; create via modal) and optional bracket JSON (auto-filled as you add teams; matches the brackets-manager.js data structure). Participants are stored as `Participant` content items (title + seeding) to preserve ordering.
3. View a tournament to see the normalized seeding list and stored JSON (preview uses brackets-manager.js helpers when available).
4. To output brackets in Views, add a View of `Tournament` entities and place the **Tournament bracket** field. You can override which fields supply the name, participants, or bracket JSON using the field settings, and the render uses [brackets-viewer.js](https://github.com/Drarig29/brackets-viewer.js/) under the hood.

## PERMISSIONS

- `view tournament entities`
- `add tournament entities`
- `manage tournament entities`
- `delete tournament entities`
- `administer tournament entities`

## MAINTAINERS

Current maintainers for Drupal 11:

- Sherwin Harris
