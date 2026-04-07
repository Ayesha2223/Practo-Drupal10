<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Simple dashboard controller for general users.
 */
class DashboardController extends ControllerBase {

  /**
   * Dashboard overview page.
   */
  public function dashboard() {
    
    $current_user = \Drupal::currentUser();
    
    return [
      '#theme' => 'user_dashboard',
      '#user_name' => $current_user->getDisplayName(),
      '#attached' => [
        'library' => ['practo_core/global-styling'],
      ],
    ];
  }

  /**
   * Edit profile page.
   */
  public function editProfile() {
    
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    
    // Check if user is a doctor and get their doctor node
    $doctor_node_id = null;
    if ($current_user->hasRole('doctor')) {
      $doctor_query = \Drupal::entityQuery('node')
        ->condition('type', 'doctor')
        ->condition('uid', $current_user->id())
        ->accessCheck(FALSE);
      
      $doctor_nids = $doctor_query->execute();
      if (!empty($doctor_nids)) {
        $doctor_node_id = reset($doctor_nids);
      }
    }
    
    return [
      '#theme' => 'user_profile_edit',
      '#user' => $user_entity,
      '#doctor_node_id' => $doctor_node_id,
      '#attached' => [
        'library' => ['practo_core/global-styling'],
      ],
    ];
  }

  /**
   * Appointments page.
   */
  public function appointments() {
    
    // Redirect to the existing my appointments page
    return $this->redirect('practo_core.my_appointments');
  }

}
