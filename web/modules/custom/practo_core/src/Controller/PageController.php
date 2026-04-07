<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Simple page controller for static pages.
 */
class PageController extends ControllerBase {

  /**
   * Health services page.
   */
  public function healthServices() {
    return [
      '#theme' => 'health_services',
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
  }

  /**
   * Privacy policy page.
   */
  public function privacyPolicy() {
    return [
      '#theme' => 'privacy_policy',
      '#attached' => [
        'library' => ['practo_core/global-styling'],
      ],
    ];
  }

  /**
   * Terms of service page.
   */
  public function terms() {
    return [
      '#theme' => 'terms_of_service',
      '#attached' => [
        'library' => ['practo_core/global-styling'],
      ],
    ];
  }

  /**
   * Contact us page.
   */
  public function contact() {
    return [
      '#theme' => 'contact_us',
      '#attached' => [
        'library' => ['practo_core/global-styling'],
      ],
    ];
  }

}
