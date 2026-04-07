<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Dashboard redirect controller based on user type.
 */
class DashboardRedirectController extends ControllerBase {

  /**
   * Redirects user to appropriate dashboard based on user type.
   */
  public function redirectDashboard() {
    
    $current_user = \Drupal::currentUser();
    
    // Load user entity to get user type
    $user_entity = User::load($current_user->id());
    
    if ($user_entity) {
      $user_type = $user_entity->get('field_user_type')->value;
      
      switch ($user_type) {
        case 'doctor':
          return new RedirectResponse(Url::fromRoute('practo_core.doctor_dashboard')->toString());
          
        case 'patient':
          return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
          
        case 'admin':
          return new RedirectResponse(Url::fromRoute('practo_core.admin_dashboard')->toString());
          
        default:
          // Default to patient dashboard if user type is not set
          return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
      }
    }
    
    // If user entity not found, redirect to appointments
    return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
  }

}
