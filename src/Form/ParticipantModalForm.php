<?php

declare(strict_types=1);

namespace Drupal\bracket_manager\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Modal form for quickly creating participants.
 */
class ParticipantModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bracket_manager_participant_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['seeding'] = [
      '#type' => 'number',
      '#title' => $this->t('Seeding'),
      '#description' => $this->t('Numeric seeding value. Lower numbers are seeded earlier.'),
      '#min' => 0,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save participant'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * AJAX submit handler to save the participant and close the modal.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    if ($form_state->getErrors()) {
      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#' . $form['#id'], $form));
      return $response;
    }

    $this->submitForm($form, $form_state);

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new HtmlCommand('.bracket-manager-participant-notice', $this->t('Participant created. Use autocomplete to select it.')));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $name = (string) $form_state->getValue('name');
    $seeding = $form_state->getValue('seeding');

    $participant = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'participant',
      'title' => $name,
      'field_seeding' => $seeding,
      'status' => 1,
    ]);
    $participant->save();

    $this->messenger()->addStatus($this->t('Created participant %name.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('<current>'));
  }

}
