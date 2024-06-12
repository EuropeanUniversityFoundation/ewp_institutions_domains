<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * EWP Institutions Domains form alter service.
 */
class InstitutionDomainFormAlter {

  use StringTranslationTrait;

  const MAIL = 'mail';

  /**
   * The current user.
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\ewp_institutions_domains\InstitutionDomainHandler $domain_handler
   *   The Institution domain handler service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxy $current_user,
    InstitutionDomainHandler $domain_handler,
    LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $string_translation
  ) {
    $this->currentUser       = $current_user;
    $this->domainHandler     = $domain_handler;
    $this->logger            = $logger_factory->get('ewp_institutions_domains');
    $this->stringTranslation = $string_translation;
  }

  /**
   * Alter the user registration form.
   *
   * @param array $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function userFormAlter(&$form, FormStateInterface $form_state) {
    $form['#validate'][] = [$this, 'userFormValidate'];
  }

  /**
   * Validate the email domain in the user registration form.
   *
   * @param array $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function userFormValidate(&$form, FormStateInterface $form_state) {
    // Ignore validation if mail already has an error.
    $errors = $form_state->getErrors();
    if (!empty($errors[self::MAIL])) {
      return;
    }

    // Bypass validation if user has sufficient permission.
    $bypass_allowed = $this->currentUser
      ->hasPermission('bypass domain form validation', $this->currentUser);
    if ($bypass_allowed) {
      return;
    }

    $email = $form_state->getValue(self::MAIL);
    $error = $this->validateEmailDomain($email);

    if ($error) {
      $form_state->setErrorByName(self::MAIL, $error);
    }
  }

  /**
   * Validate email domain.
   *
   * @param string $email
   *   The email address to validate.
   *
   * @return Drupal\Core\StringTranslation\TranslatableMarkup $error|null
   */
  public function validateEmailDomain(string $email): ?TranslatableMarkup {
    $email_components = explode('@', $email);
    $email_domain = $email_components[1];

    $matches = $this->domainHandler->getMatches($email_domain);

    if (\count($matches) !== 1) {
      if (empty(\count($matches))) {
        $error = $this->t('The account cannot be created. @message', [
          '@message' => $this->t('The email domain %domain is not allowed.', [
            '%domain' => $email_domain,
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
          '@description' => $this->t('%email matched patterns in @lists.', [
            '%email' => $email,
            '@lists' => \implode(', ', $lists),
          ]),
        ]);

        $this->logger->error($record);
      }

      return $error;
    }

    return NULL;
  }

}
