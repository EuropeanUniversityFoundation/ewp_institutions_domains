<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_domains\Entity\InstitutionDomainList;

/**
 * Service for handling Institution domains.
 */
class InstitutionDomainHandler {

  use StringTranslationTrait;

  const ENTITY_TYPE = 'hei_domain_list';
  const HEI_ID = 'hei_id';
  const STATUS = 'status';

  const WILDCARDS = "/^[*?]+$/";

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Get a list of Institution domain lists.
   *
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[]
   *   An array of Institution domain lists.
   */
  public function getLists(): array {
    /** @var \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[] */
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
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList|null
   *   An Institution domain list, if one exists.
   */
  public function getList(string $id): ?InstitutionDomainList {
    /** @var \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[] */
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
   *   An array of Institution domain lists, keyed by entity ID.
   */
  public function getEnabledListByHeiId(string $hei_id): array {
    /** @var \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList[] */
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
   * @return array
   *   An array of domain patterns.
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
   *   The RegExp pattern to be matched.
   * @param string $string
   *   The string to match.
   *
   * @return int|false
   *   The result of the pattern match.
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
   *   The RegExp pattern to be validated.
   *
   * @return int|false
   *   The result of the pattern validation.
   */
  public function validatePattern($pattern) {
    $parts = \explode('.', $pattern);
    if (\count($parts) < 2) {
      return FALSE;
    }

    $count = 0;
    foreach ($parts as $part) {
      if (empty($part)) {
        return FALSE;
      }

      $count = (\preg_match(self::WILDCARDS, $part)) ? $count + 1 : $count;
    }

    if ($count === \count($parts)) {
      return FALSE;
    }

    return $this->matchPattern($pattern, '');
  }

  /**
   * Get all pattern matches from a domain.
   *
   * @param string $domain
   *   The domain to be matched.
   *
   * @return array
   *   An array of pattern matches keyed by Institution ID.
   */
  public function getMatches($domain): array {
    $matches = [];

    foreach ($this->getPatterns() as $id => $patterns) {
      foreach ($patterns as $pattern) {
        $result = $this->matchPattern($pattern, $domain);
        if ($result === 1) {
          $matches[$id][] = $pattern;
        }
      }
    }

    return $matches;
  }

}
