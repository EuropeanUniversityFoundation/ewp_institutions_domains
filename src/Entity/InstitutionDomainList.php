<?php

namespace Drupal\ewp_institutions_domains\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ewp_institutions_domains\InstitutionDomainListInterface;

/**
 * Defines the institution domain list entity type.
 *
 * @ConfigEntityType(
 *   id = "hei_domain_list",
 *   label = @Translation("Institution domain list"),
 *   label_collection = @Translation("Institution domain lists"),
 *   label_singular = @Translation("institution domain list"),
 *   label_plural = @Translation("institution domain lists"),
 *   label_count = @PluralTranslation(
 *     singular = "@count institution domain list",
 *     plural = "@count institution domain lists",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\ewp_institutions_domains\InstitutionDomainListListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ewp_institutions_domains\Form\InstitutionDomainListForm",
 *       "edit" = "Drupal\ewp_institutions_domains\Form\InstitutionDomainListForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "hei_domain_list",
 *   admin_permission = "administer institution domains",
 *   links = {
 *     "collection" = "/admin/ewp/hei/domains",
 *     "add-form" = "/admin/ewp/hei/domains/add",
 *     "edit-form" = "/admin/ewp/hei/domains/{hei_domain_list}",
 *     "delete-form" = "/admin/ewp/hei/domains/{hei_domain_list}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "hei_id",
 *     "patterns"
 *   }
 * )
 */
class InstitutionDomainList extends ConfigEntityBase implements InstitutionDomainListInterface {

  /**
   * The institution domain list ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The institution domain list label.
   *
   * @var string
   */
  protected $label;

  /**
   * The institution domain list status.
   *
   * @var bool
   */
  protected $status;

  /**
   * The institution ID.
   *
   * @var string
   */
  protected $hei_id;

  /**
   * The institution domain list of patterns.
   *
   * @var array
   */
  protected $patterns;

  /**
   * Returns the Institution ID.
   *
   * @return mixed
   *   The Institution ID if it exists, or NULL otherwise.
   */
  public function heiId() {
    return $this->get('hei_id');
  }

  /**
   * Returns the domain patterns.
   *
   * @return mixed
   *   The domain patterns if any exist, or NULL otherwise.
   */
  public function patterns() {
    return $this->get('patterns');
  }

}
