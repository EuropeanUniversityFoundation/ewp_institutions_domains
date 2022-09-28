<?php

namespace Drupal\ewp_institutions_domains\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions_domains\InstitutionDomainHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Institution domain list form.
 *
 * @property \Drupal\ewp_institutions_domains\InstitutionDomainListInterface $entity
 */
class InstitutionDomainListForm extends EntityForm {

  /**
   * The Institution domain handler service.
   *
   * @var \Drupal\ewp_institutions_domains\InstitutionDomainHandler
   */
  protected $domainHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->domainHandler = $container->get('ewp_institutions_domains');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    dpm($this);

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the institution domain list.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ewp_institutions_domains\Entity\InstitutionDomainList::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['hei_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Institution ID'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->heiId(),
      '#description' => $this->t('The SCHAC code of the Institution.'),
      '#required' => TRUE,
    ];

    $patterns = $this->entity->patterns();

    $form['patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domain patterns'),
      '#default_value' => ($patterns) ? implode("\n", $patterns) : '',
      '#description' => $this->t('List of accepted domains patterns.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Convert multiline text to array of patterns.
    $patterns = array_filter(
      array_map(
        'trim', explode(
          "\n", $form_state->getValue('patterns')
        )
      ), 'strlen'
    );
    $this->entity->set('patterns', $patterns);

    if ($form_state->getValue('status') && empty($patterns)) {
      $this->entity->set('status', FALSE);
      $warning = $this->t('@type %entity cannot be enabled without @field.', [
        '@type' => $this->entity->getEntityType()->getLabel(),
        '%entity' => $this->entity->label(),
        '@field' => $this->t('Domain patterns')
      ]);
      $this->messenger()->addWarning($warning);
    }

    $exists = $this->entityTypeManager
      ->getStorage('hei_domain_list')
      ->loadByProperties([
        'status' => TRUE,
        'hei_id' => $form_state->getValue('hei_id'),
      ]);

    if ($form_state->getValue('status') && !empty($exists)) {
      $this->entity->set('status', FALSE);
      $warning = $this->t('@type %entity cannot be enabled when @condition.', [
        '@type' => $this->entity->getEntityType()->getLabel(),
        '%entity' => $this->entity->label(),
        '@condition' => $this->t('the same Institution ID is already in use.')
      ]);
      $this->messenger()->addWarning($warning);
    }

    $result = $this->entity->save();
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new institution domain list %label.', $message_args)
      : $this->t('Updated institution domain list %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
