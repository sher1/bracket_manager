<?php

declare(strict_types=1);

namespace Drupal\bracket_manager\Plugin\views\field;

use Drupal\Core\Render\Markup;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bracket_manager\Entity\Tournament;
use Drupal\Core\Entity\EntityInterface;

/**
 * Displays a bracket using brackets-viewer.js for Tournament entities.
 */
#[ViewsField("tournament_bracket")]
class TournamentBracket extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['name_field'] = ['default' => 'title'];
    $options['participants_field'] = ['default' => 'field_tournament_participants'];
    $options['bracket_data_field'] = ['default' => 'field_bracket_data'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $form['name_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name field'),
      '#description' => $this->t('Machine name of the field or Views field ID providing the tournament title.'),
      '#default_value' => $this->options['name_field'],
    ];
    $form['participants_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Participants field'),
      '#description' => $this->t('Machine name of the field or Views field ID that lists participants (one per line).'),
      '#default_value' => $this->options['participants_field'],
    ];
    $form['bracket_data_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bracket data field'),
      '#description' => $this->t('Machine name of the field or Views field ID containing brackets-viewer.js data JSON.'),
      '#default_value' => $this->options['bracket_data_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // No query alterations; relies on existing entity/fields.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): array {
    $entity = $this->getEntity($values);
    $view_id = $this->view->storage ? $this->view->storage->id() : 'view';
    $bracket_id = $entity ? $entity->id() : uniqid('bracket_', TRUE);
    $bracket_key = (string) $bracket_id;
    $sanitized_id = preg_replace('/[^a-zA-Z0-9_-]/', '-', $bracket_key);

    $name = $this->extractValue($values, $this->options['name_field'], $entity ? $entity->label() : $this->t('Tournament'));
    $participant_names = $this->loadParticipantNames($entity);
    $participants_raw = $this->extractValue($values, $this->options['participants_field']);
    $fallback_bracket = '';
    if ($entity) {
      if ($entity->hasField('bracket_data')) {
        $fallback_bracket = (string) $entity->get('bracket_data')->value;
      }
      elseif ($entity->hasField('field_bracket_data')) {
        $fallback_bracket = (string) $entity->get('field_bracket_data')->value;
      }
    }
    $bracket_data_raw = $this->extractValue($values, $this->options['bracket_data_field'], $fallback_bracket);

    $decoded_data = NULL;
    if (!empty($bracket_data_raw)) {
      $decoded_data = json_decode($bracket_data_raw, TRUE);
    }

    $participant_lines = $participant_names ?: array_values(array_filter(array_map('trim', explode("\n", $participants_raw))));
    $fallback_participants = [];
    foreach ($participant_lines as $index => $participant_name) {
      $fallback_participants[] = [
        'id' => $index + 1,
        'name' => $participant_name,
      ];
    }

    $data = $decoded_data ?? [];
    if (empty($data['participants']) && !empty($fallback_participants)) {
      $data['participants'] = $fallback_participants;
    }
    $data['stages'] = $data['stages'] ?? [];
    $data['matches'] = $data['matches'] ?? [];
    $data['matchGames'] = $data['matchGames'] ?? [];

    $element_id = 'brackets-viewer-' . $sanitized_id;
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['brackets-viewer', 'js-brackets-viewer'],
        'id' => $element_id,
        'data-view-id' => $view_id,
        'data-bracket-viewer-id' => $sanitized_id,
      ],
      'fallback' => [
        '#markup' => Markup::create($this->t('Loading bracket for %name…', ['%name' => $name])),
      ],
      '#attached' => [
        'library' => [
          'bracket_manager/views_bracket_viewer',
        ],
        'drupalSettings' => [
          'bracketManager' => [
            'views' => [
              $view_id => [
                $sanitized_id => [
                  'name' => $name,
                  'data' => $data,
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns participant names associated with a tournament entity.
   */
  protected function loadParticipantNames(EntityInterface $entity = NULL): array {
    if ($entity instanceof Tournament) {
      return $entity->getParticipantNames();
    }
    if ($entity && $entity->hasField('field_tournament_participants')) {
      $names = [];
      foreach ($entity->get('field_tournament_participants')->referencedEntities() as $participant) {
        $names[] = $participant->label();
      }
      return $names;
    }
    return [];
  }

  /**
   * Extracts a value from the view row, either from a field, entity property, or default.
   */
  protected function extractValue(ResultRow $values, string $field_id, string $default = ''): string {
    if (isset($this->view->field[$field_id])) {
      return (string) $this->view->field[$field_id]->advancedRender($values);
    }

    if (isset($values->{$field_id})) {
      return (string) $values->{$field_id};
    }

    $entity = $this->getEntity($values);
    if ($entity && $entity->hasField($field_id) && !$entity->get($field_id)->isEmpty()) {
      return (string) $entity->get($field_id)->value;
    }

    return $default;
  }

}
