<?php

declare(strict_types=1);

namespace Drupal\bracket_manager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a listing of Tournament entities.
 */
class TournamentListBuilder extends EntityListBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Title');
    $header['active'] = $this->t('Active');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\bracket_manager\Entity\Tournament $entity */
    $row['name'] = $entity->toLink();
    $row['active'] = $entity->get('active')->isEmpty() ? $this->t('No') : ($entity->get('active')->value ? $this->t('Yes') : $this->t('No'));
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}
