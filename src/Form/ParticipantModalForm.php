<?php

declare(strict_types=1);

namespace Drupal\bracket_manager\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
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
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      // Return the rebuilt form with errors.
      $response->addCommand(new HtmlCommand('#' . $form['#id'], $form));
      return $response;
    }

    // Submit handlers have already run; simply close the modal and notify.
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new HtmlCommand('.bracket-manager-participant-notice', $this->t('Participant created. Use autocomplete to select it.')));
    if ($participant = $form_state->get('new_participant')) {
      $option = sprintf('<option value="%d" selected="selected">%s</option>', $participant['id'], htmlspecialchars($participant['label'], ENT_QUOTES, 'UTF-8'));
      // Append the new option, remove the _none placeholder if present, and refresh preview.
      $response->addCommand(new InvokeCommand('#edit-participants option[value=\"_none\"]', 'remove', []));
      $response->addCommand(new InvokeCommand('#edit-participants', 'append', [$option]));
      $response->addCommand(new InvokeCommand('#edit-participants', 'trigger', ['change']));
    }
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

    $form_state->set('new_participant', ['id' => $participant->id(), 'label' => $name]);
    $this->messenger()->addStatus($this->t('Created participant %name.', ['%name' => $name]));
    $form_state->setRedirectUrl(Url::fromRoute('<current>'));
  }

}
