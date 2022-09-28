<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
  const PATTERNS = 'patterns';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
      EntityTypeManagerInterface $entity_type_manager,
      LoggerChannelFactoryInterface $logger_factory,
      TranslationInterface $string_translation
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger            = $logger_factory->get('ewp_institutions_domains');
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
    $domain_list = NULL;

    $lists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties(['id' => $id]);

    // The lists array will contain one item at most.
    foreach ($lists as $id => $object) {
      $domain_list = $object;
    }

    return $domain_list;
  }

  /**
   * Get an Institution domain list by Institution ID.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return \Drupal\ewp_institutions_domains\Entity\InstitutionDomainList|NULL
   */
  public function getListByHeiId(string $hei_id): ?InstitutionDomainList {
    $domain_list = NULL;

    $lists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties([self::HEI_ID => $hei_id]);

    // The lists array will contain one item at most.
    foreach ($lists as $hei_id => $object) {
      $domain_list = $object;
    }

    return $domain_list;
  }


}
