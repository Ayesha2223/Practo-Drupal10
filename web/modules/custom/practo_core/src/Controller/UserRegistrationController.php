<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Controller for user registration.
 */
class UserRegistrationController extends ControllerBase {

  /**
   * Shows registration type selection page.
   */
  public function registerSelection() {
    return [
      '#theme' => 'user_registration_selection',
      '#doctor_url' => Url::fromRoute('practo_core.register_doctor')->toString(),
      '#patient_url' => Url::fromRoute('practo_core.register_patient')->toString(),
      '#attached' => [
        'library' => ['practo_core/registration'],
      ],
    ];
  }

  /**
   * Creates a new user with basic fields.
   */
  protected function createUser($email, $password, $name, $user_type) {
    $user = User::create();
    $user->setEmail($email);
    $user->setUsername($email);
    $user->setPassword($password);
    $user->enforceIsNew();
    $user->set('field_user_type', $user_type);
    $user->activate();
    $user->save();
    
    return $user;
  }

}
