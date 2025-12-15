<?php

declare(strict_types=1);

namespace Drupal\bracket_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Adds bracket manager data to Tournament views.
 */
class TournamentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL): array {
    $build = parent::view($entity, $view_mode, $langcode);

    $data = $entity->get('bracket_data')->value ?? '';
    $participants = $entity->getParticipantNames();

    $build['bracket_manager'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['bracket-manager-viewer'],
        'data-tournament-id' => $entity->id(),
      ],
      'canvas' => [
        '#markup' => '<div class="bracket-manager-viewer__canvas"></div>',
      ],
    ];

    $build['#attached']['library'][] = 'bracket_manager/bracket_viewer';
    $build['#attached']['drupalSettings']['bracketManager']['tournaments'][$entity->id()] = [
      'name' => $entity->label(),
      'participants' => $participants,
      'data' => $data,
    ];

    return $build;
  }

}
