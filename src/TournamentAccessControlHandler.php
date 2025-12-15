<?php

declare(strict_types=1);

namespace Drupal\bracket_manager;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls access for Tournament entities.
 */
class TournamentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view tournament entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage tournament entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete tournament entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'add tournament entities');
  }

}
