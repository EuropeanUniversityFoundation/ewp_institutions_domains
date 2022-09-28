<?php

namespace Drupal\ewp_institutions_domains;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of institution domain lists.
 */
class InstitutionDomainListListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    $header['hei_id'] = $this->t('Institution ID');
    $header['patterns'] = $this->t('Domain patterns');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ewp_institutions_domains\InstitutionDomainListInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status()
      ? $this->t('Enabled')
      : $this->t('Disabled');
    $row['hei_id'] = $entity->heiId();
    $row['patterns']['data'] = [
      '#type' => 'markup',
      '#markup' => implode("<br />", $entity->patterns())
    ];
    return $row + parent::buildRow($entity);
  }

}
