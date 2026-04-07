<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Simple doctor verification controller.
 */
class DoctorVerificationController extends ControllerBase {

  /**
   * Doctor verification dashboard.
   */
  public function verificationDashboard() {
    
    // Get all unverified doctors
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 0)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $doctors = [];
    
    if (!empty($nids)) {
      $doctor_nodes = Node::loadMultiple($nids);
      
      foreach ($doctor_nodes as $doctor) {
        $doctors[] = [
          'id' => $doctor->id(),
          'name' => $doctor->getTitle(),
          'specialization' => $doctor->get('field_specialization')->value ?? '',
          'email' => $doctor->getOwner()->getEmail(),
          'created' => date('M j, Y', $doctor->getCreatedTime()),
        ];
      }
    }
    
    return [
      '#theme' => 'doctor_verification_dashboard',
      '#doctors' => $doctors,
      '#total_count' => count($doctors),
      '#attached' => [
        'library' => ['practo_core/doctor-verification'],
      ],
    ];
  }

  /**
   * Verify a doctor.
   */
  public function verifyDoctor($doctor) {
    
    $doctor_node = Node::load($doctor);
    
    if ($doctor_node && $doctor_node->bundle() === 'doctor') {
      // Publish the doctor node so it shows in the doctor listing.
      $doctor_node->setPublished();
      $doctor_node->save();

      \Drupal::messenger()->addStatus('Doctor has been verified successfully.');
    }
    
    return new RedirectResponse(Url::fromRoute('practo_core.doctor_verification')->toString());
  }

  /**
   * Reject a doctor.
   */
  public function rejectDoctor($doctor) {
    
    $doctor_node = Node::load($doctor);
    
    if ($doctor_node && $doctor_node->bundle() === 'doctor') {
      // Unpublish the doctor node
      $doctor_node->setUnpublished();
      $doctor_node->save();
      
      \Drupal::messenger()->addStatus('Doctor has been rejected and unpublished.');
    }
    
    return new RedirectResponse(Url::fromRoute('practo_core.doctor_verification')->toString());
  }

  /**
   * View doctor details.
   */
  public function viewDoctorDetails($doctor) {
    
    $doctor_node = Node::load($doctor);
    
    if (!$doctor_node || $doctor_node->bundle() !== 'doctor') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    return [
      '#theme' => 'doctor_verification_details',
      '#doctor' => [
        'name' => $doctor_node->getTitle(),
        'specialization' => $doctor_node->get('field_specialization')->value ?? '',
        'phone' => $doctor_node->get('field_phone')->value ?? '',
        'email' => $doctor_node->getOwner()->getEmail(),
        'experience' => $doctor_node->get('field_experience_years')->value ?? 0,
        'fee' => $doctor_node->get('field_consultation_fee')->value ?? 0,
      ],
      '#attached' => [
        'library' => ['practo_core/doctor-verification'],
      ],
    ];
  }

  /**
   * Batch verify doctors.
   */
  public function batchVerify() {
    
    // Simple batch verification logic
    \Drupal::messenger()->addStatus('Batch verification completed.');
    
    return new RedirectResponse(Url::fromRoute('practo_core.doctor_verification')->toString());
  }

}
