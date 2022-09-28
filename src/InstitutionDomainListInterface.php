<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an institution domain list entity type.
 */
interface InstitutionDomainListInterface extends ConfigEntityInterface {

  /**
   * Returns the Institution ID.
   *
   * @return mixed
   *   The Institution ID if it exists, or NULL otherwise.
   */
  public function heiId();

  /**
   * Returns the domain patterns.
   *
   * @return mixed
   *   The domain patterns if any exist, or NULL otherwise.
   */
  public function patterns();

}
