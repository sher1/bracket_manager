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
 * Defines a participant/team belonging to a tournament.
 */
#[ContentEntityType(
  id: "tournament_participant",
  label: new TranslatableMarkup("Tournament participant"),
  handlers: [
    "view_builder" => "Drupal\Core\Entity\EntityViewBuilder",
    "access" => "Drupal\Core\Entity\EntityAccessControlHandler",
    "route_provider" => [
      "html" => "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
    ],
    "form" => [
      "add" => "Drupal\Core\Entity\ContentEntityForm",
      "edit" => "Drupal\Core\Entity\ContentEntityForm",
      "delete" => "Drupal\Core\Entity\ContentEntityDeleteForm",
    ],
    "list_builder" => "Drupal\Core\Entity\EntityListBuilder",
  ],
  base_table: "tournament_participant",
  data_table: "tournament_participant_field_data",
  translatable: TRUE,
  admin_permission: "administer tournament entities",
  entity_keys: [
    "id" => "id",
    "label" => "name",
    "uuid" => "uuid",
    "langcode" => "langcode",
  ],
  links: [
    "canonical" => "/admin/structure/tournament-participant/{tournament_participant}",
    "add-form" => "/admin/structure/tournament-participant/add",
    "edit-form" => "/admin/structure/tournament-participant/{tournament_participant}/edit",
    "delete-form" => "/admin/structure/tournament-participant/{tournament_participant}/delete",
    "collection" => "/admin/structure/tournament-participant",
  ],
  field_ui_base_route: "entity.tournament.collection",
)]
class TournamentParticipant extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tournament'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tournament'))
      ->setSetting('target_type', 'tournament')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order'))
      ->setDescription(t('Seeding order for the participant.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
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

}
