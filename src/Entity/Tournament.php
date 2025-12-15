<?php

declare(strict_types=1);

namespace Drupal\bracket_manager\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Tournament entity.
 */
#[ContentEntityType(
  id: "tournament",
  label: new TranslatableMarkup("Tournament"),
  handlers: [
    "list_builder" => "Drupal\bracket_manager\TournamentListBuilder",
    "form" => [
      "add" => "Drupal\bracket_manager\Form\TournamentForm",
      "edit" => "Drupal\bracket_manager\Form\TournamentForm",
      "delete" => "Drupal\bracket_manager\Form\TournamentDeleteForm",
    ],
    "access" => "Drupal\bracket_manager\TournamentAccessControlHandler",
    "route_provider" => [
      "html" => "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
    ],
    "view_builder" => "Drupal\bracket_manager\TournamentViewBuilder",
  ],
  base_table: "tournament",
  data_table: "tournament_field_data",
  translatable: TRUE,
  admin_permission: "administer tournament entities",
  entity_keys: [
    "id" => "id",
    "label" => "name",
    "uuid" => "uuid",
    "langcode" => "langcode",
  ],
  links: [
    "canonical" => "/admin/structure/tournaments/{tournament}",
    "add-form" => "/admin/structure/tournaments/add",
    "edit-form" => "/admin/structure/tournaments/{tournament}/edit",
    "delete-form" => "/admin/structure/tournaments/{tournament}/delete",
    "collection" => "/admin/structure/tournaments",
  ],
)]
class Tournament extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 4,
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bracket_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Bracket data'))
      ->setDescription(t('JSON configuration consumed by brackets-manager.js'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'rows' => 8,
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['participants'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participants'))
      ->setDescription(t('Select tournament participants.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default:node')
      ->setSetting('handler_settings', [
        'target_bundles' => ['participant' => 'participant'],
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Returns participant entities ordered by weight then creation time.
   *
   * @return \Drupal\bracket_manager\Entity\TournamentParticipant[]
   *   Related participant entities.
   */
  public function getParticipants(): array {
    $entities = $this->get('participants')->referencedEntities();
    $indexed = [];
    foreach ($entities as $delta => $entity) {
      $indexed[] = ['entity' => $entity, 'delta' => $delta];
    }
    // Sort by seeding then preserve reference order as tie-breaker.
    usort($indexed, static function (array $a, array $b): int {
      $a_seed = (int) ($a['entity']->get('field_seeding')->value ?? 0);
      $b_seed = (int) ($b['entity']->get('field_seeding')->value ?? 0);
      $cmp = $a_seed <=> $b_seed;
      return $cmp !== 0 ? $cmp : ($a['delta'] <=> $b['delta']);
    });
    return array_column($indexed, 'entity');
  }

  /**
   * Returns participant names in seeding order.
   */
  public function getParticipantNames(): array {
    $names = [];
    foreach ($this->getParticipants() as $participant) {
      $name = $participant->label();
      if ($name !== NULL && $name !== '') {
        $names[] = $name;
      }
    }
    return $names;
  }

}
