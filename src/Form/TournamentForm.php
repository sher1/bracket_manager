<?php

declare(strict_types=1);

namespace Drupal\bracket_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Tournament add/edit forms.
 */
class TournamentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#attached']['library'][] = 'bracket_manager/bracket_form';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['participants_modal'] = [
      '#type' => 'link',
      '#title' => $this->t('Add participant'),
      '#url' => \Drupal\Core\Url::fromRoute('bracket_manager.participant_modal'),
      '#attributes' => [
        'class' => ['button', 'button--small', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(['width' => 600]),
      ],
      '#weight' => -9,
    ];

    $form['participants_notice'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['bracket-manager-participant-notice']],
      '#weight' => -8,
    ];

    $form['bracket_preview'] = [
      '#type' => 'container',
      '#weight' => 6,
      '#attributes' => ['class' => ['bracket-manager-preview']],
      'canvas' => [
        '#markup' => '<div class="bracket-manager-preview__canvas"></div>',
      ],
      '#description' => $this->t('Live preview generated from the participants and bracket data fields.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $message_args = ['%label' => $entity->label()];
    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created tournament %label.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Updated tournament %label.', $message_args));
    }

    $form_state->setRedirect('entity.tournament.collection');
  }

}
