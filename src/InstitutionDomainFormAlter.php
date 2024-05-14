<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_domains\InstitutionDomainHandler;

/**
 * EWP Institutions Domains form alter service.
 */
class InstitutionDomainFormAlter {

  use StringTranslationTrait;

  const MAIL = 'mail';

  /**
   * The institution domain handler.service.
   *
   * @var \Drupal\ewp_institutions_domains\InstitutionDomainHandler
   */
  protected $domainHandler;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The constructor.
   *
   * @param \Drupal\ewp_institutions_domains\InstitutionDomainHandler $domain_handler
   *   The Institution domain handler service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    InstitutionDomainHandler $domain_handler,
    LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $string_translation
  ) {
    $this->domainHandler     = $domain_handler;
    $this->logger            = $logger_factory->get('ewp_institutions_domains');
    $this->stringTranslation = $string_translation;
  }

  /**
   * Alter the user registration form.
   *
   * @param array $form
   * @param Drupal\Core\Form\FormStateInterface $form_state
   */
  public function userFormAlter(&$form, FormStateInterface $form_state) {
    $form['#validate'][] = [$this, 'validateEmail'];
  }

  /**
   * Validate email address.
   *
   * @param array $form
   * @param Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateEmail(&$form, FormStateInterface $form_state) {
    // Ignore validation if mail already has an error.
    $errors = $form_state->getErrors();
    if (!empty($errors[self::MAIL])) {
      return;
    }

    $mail = explode('@', $form_state->getValue(self::MAIL));

    $matches = $this->domainHandler->getMatches($mail[1]);

    if (\count($matches) !== 1) {
      if (empty(\count($matches))) {
        $error = $this->t('The account cannot be created. @message', [
          '@message' => $this->t('The email domain %domain is not allowed.', [
            '%domain' => $mail[1],
          ]),
        ]);
      }
      else {
        $error = $this->t('The account cannot be created. @message', [
          '@message' => $this->t('Please contact the system administrator.'),
        ]);

        $lists = [];

        foreach ($matches as $id => $patterns) {
          $lists[] = $this->t('@label (@id)', [
            '@label' => $this->domainHandler->getList($id)->label(),
            '@id' => $id,
          ]);
        }

        $record = $this->t('Email validation failed: @description', [
          '@description' => $this->t('%mail matched patterns in @lists.', [
            '%mail' => $form_state->getValue(self::MAIL),
            '@lists' => \implode(', ', $lists),
          ])
        ]);

        $this->logger->error($record);
      }

      $form_state->setErrorByName(self::MAIL, $error);
    }
  }

}
