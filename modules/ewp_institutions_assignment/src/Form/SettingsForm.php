<?php

declare(strict_types=1);

namespace Drupal\ewp_institutions_assignment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure EWP Institutions assignment settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ewp_institutions_assignment_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ewp_institutions_assignment.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Import Institution if not present in the system.'),
      '#config_target' => 'ewp_institutions_assignment.settings:import',
    ];
    return parent::buildForm($form, $form_state);
  }

}
