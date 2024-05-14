<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\ewp_institutions_domains\Entity\InstitutionDomainList;
use Drupal\ewp_institutions_domains\Event\UserCreatedWithValidDomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service for handling Institution domains.
 */
class InstitutionDomainHandler {

  use StringTranslationTrait;

  const ENTITY_TYPE = 'hei_domain_list';
  const HEI_ID = 'hei_id';
  const PATTERNS = 'patterns';
  const STATUS = 'status';

  const WILDCARDS = "/^[*?]+$/";

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
      EntityTypeManagerInterface $entity_type_manager,
      EventDispatcherInterface $event_dispatcher,
      TranslationInterface $string_translation
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher   = $event_dispatcher;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get a list of Institution domain lists.
   *
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[]
   */
  public function getLists(): array {
    $lists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadMultiple();

    return $lists;
  }

  /**
   * Get an Institution domain list by ID.
   *
   * @param string $id
   *   The Institution domain list ID.
   *
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList|NULL
   */
  public function getList(string $id): ?InstitutionDomainList {
    $lists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties(['id' => $id]);

    // The lists array will contain one item at most.
    foreach ($lists as $id => $object) {
      return $object;
    }

    return NULL;
  }

  /**
   * Get an Institution domain list by Institution ID.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[]
   */
  public function getEnabledListByHeiId(string $hei_id): array {
    $domain_lists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties([
        self::STATUS => TRUE,
        self::HEI_ID => $hei_id,
      ]);

    return $domain_lists;
  }

  /**
   * Get a list of domain patterns per domain list.
   *
   * @return array $patterns
   */
  public function getPatterns($enabled = TRUE): array {
    $patterns = [];

    $lists = $this->getLists();

    foreach ($lists as $id => $object) {
      if (($enabled && $object->status()) || !$enabled) {
        $patterns[$id] = $object->patterns();
      }
    }

    return $patterns;
  }

  /**
   * Match a RegExp pattern.
   *
   * @param string $pattern
   * @param string $string
   *
   * @return int|false
   */
  public function matchPattern($pattern, $string) {
    $replace_chars = ['\*' => '.*', '\?' => '.'];
    $processed_str = strtr(preg_quote($pattern, '#'), $replace_chars);

    return preg_match("#^" . $processed_str . "$#i", $string);
  }

  /**
   * Validate a RegExp pattern.
   *
   * @param string $pattern
   *
   * @return int|false
   */
  public function validatePattern($pattern) {
    $parts = \explode('.', $pattern);
    if (\count($parts) < 2) { return FALSE; }

    $count = 0;
    foreach ($parts as $part) {
      if (empty($part)) { return FALSE; }
      $count = (\preg_match(self::WILDCARDS, $part)) ? $count+1 : $count;
    }

    if ($count === \count($parts)) { return FALSE; }

    return $this->matchPattern($pattern, '');
  }

  /**
   * Get all pattern matches (indexed by Institution ID) from a domain.
   *
   * @param string $domain
   *
   * @return array $matches
   */
  public function getMatches($domain): array {
    $matches = [];

    foreach ($this->getPatterns() as $id => $patterns) {
      foreach ($patterns as $pattern) {
        $result = $this->matchPattern($pattern, $domain);
        if ($result === 1) { $matches[$id][] = $pattern; }
      }
    }

    return $matches;
  }

  /**
   * Dispatch event on user creation with valid domain.
   *
   * @param \Drupal\user\UserInterface $user
   */
  public function dispatchCreated(UserInterface $user) {
    $mail = explode('@', $user->getEmail());

    $matches = $this->getMatches($mail[1]);
    $list_id = \array_keys($matches)[0] ?? NULL;

    if ($list_id) {
      $hei_id = $this->getList($list_id)->heiId();

      // Instantiate our event.
      $event = new UserCreatedWithValidDomainEvent($user, $mail[1], $hei_id);
      // Dispatch the event.
      $this->eventDispatcher
        ->dispatch($event, UserCreatedWithValidDomainEvent::EVENT_NAME);
    }
  }

}
