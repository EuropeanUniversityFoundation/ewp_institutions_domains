UserCreatedWithValidDomainEvent

<?php

namespace Drupal\ewp_institutions_user\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

/**
 * Event that is fired when a user is created with a valid email domain.
 */
class UserCreatedWithValidDomainEvent extends Event {

  const EVENT_NAME = 'user_created_valid_domain';

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user;

  /**
   * The user's email domain.
   *
   * @var string
   */
  public $domain;

  /**
   * The SCHAC code associated with the user's email domain.
   *
   * @var string
   */
  public $hei_id;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param string $domain
   *   The user's email domain.
   * @param string $hei_id
   *   The SCHAC code associated with the user's email domain.
   */
  public function __construct(UserInterface $user, string $domain, string $hei_id) {
    $this->user = $user;
    $this->domain = $domain;
    $this->hei_id = $hei_id;
  }

}
