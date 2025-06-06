<?php

namespace Drupal\ewp_institutions_assignment\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_assignment\Event\UserCreatedWithValidDomainEvent;
use Drupal\ewp_institutions_get\InstitutionManager;
use Drupal\ewp_institutions_lookup\InstitutionLookupManager;
use Drupal\ewp_institutions_user\Event\SetUserInstitutionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * EWP Institutions assignment event subscriber.
 */
class UserCreatedWithValidDomainEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EWP Institutions manager service.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $heiManager;

  /**
   * EWP Institutions lookup manager service.
   *
   * @var \Drupal\ewp_institutions_lookup\InstitutionLookupManager
   */
  protected $heiLookup;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs event subscriber.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\ewp_institutions_get\InstitutionManager $hei_manager
   *   EWP Institutions manager service.
   * @param \Drupal\ewp_institutions_lookup\InstitutionLookupManager $hei_lookup
   *   EWP Institutions lookup manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher,
    InstitutionManager $hei_manager,
    InstitutionLookupManager $hei_lookup,
    MessengerInterface $messenger,
    RendererInterface $renderer,
    TranslationInterface $string_translation,
  ) {
    $this->eventDispatcher   = $event_dispatcher;
    $this->heiManager        = $hei_manager;
    $this->heiLookup         = $hei_lookup;
    $this->messenger         = $messenger;
    $this->renderer          = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserCreatedWithValidDomainEvent::EVENT_NAME => [
        'onUserCreatedWithValidDomain',
      ],
    ];
  }

  /**
   * Subscribe to the user created with valid domain event.
   *
   * @param \Drupal\ewp_institutions_assignment\Event\UserCreatedWithValidDomainEvent $event
   *   The event object.
   */
  public function onUserCreatedWithValidDomain(UserCreatedWithValidDomainEvent $event) {
    $hei_list = [];
    // @todo get import settings from config.
    $import = TRUE;

    $exists = $this->heiManager->getInstitution($event->heiId);

    if (empty($exists) && $import) {
      $lookup = $this->heiLookup->lookup($event->heiId);

      if (\array_key_exists($event->heiId, $lookup)) {
        $exists = $this->heiManager->getInstitution(
          $event->heiId,
          $create_from = $lookup[$event->heiId]
        );
      }
    }

    if ($exists) {
      foreach ($exists as $hei) {
        $hei_list[] = $hei;
        $renderable = $hei->toLink()->toRenderable();
        $message = $this->t("User %user's email domain matches @link", [
          '%user' => $event->user->label(),
          '@link' => $this->renderer->render($renderable),
        ]);
        $this->messenger->addMessage($message);
      }

      // Instantiate our new event.
      $new_event = new SetUserInstitutionEvent(
        $event->user,
        $hei_list,
        FALSE
      );
      // Dispatch the new event.
      $this->eventDispatcher->dispatch(
        $new_event,
        SetUserInstitutionEvent::EVENT_NAME
      );
    }
    else {
      $message = $this->t("No match found for user %user's email domain.", [
        '%user' => $event->user->label(),
      ]);
      $this->messenger->addWarning($message);
    }
  }

}
