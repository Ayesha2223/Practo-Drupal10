<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Admin Dashboard Controller for CRUD operations.
 */
class AdminDashboardController extends ControllerBase {

  /**
   * Dashboard overview page.
   */
  public function dashboard() {
    
    // Get statistics
    $stats = $this->getStatistics();
    
    return [
      '#theme' => 'admin_dashboard',
      '#stats' => $stats,
      '#attached' => [
        'library' => ['practo_core/admin-dashboard'],
      ],
    ];
  }

  /**
   * Manage Doctors page with CRUD operations.
   */
  public function manageDoctors() {
    
    // Get all doctors
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $doctors = [];
    
    if (!empty($nids)) {
      $doctor_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      
      foreach ($doctor_nodes as $doctor) {
        $doctors[] = [
          'id' => $doctor->id(),
          'name' => $doctor->getTitle(),
          'specialization' => $doctor->get('field_specialization')->value,
          'experience' => $doctor->get('field_experience_years')->value,
          'fee' => $doctor->get('field_consultation_fee')->value,
          'city' => $doctor->get('field_city')->value,
          'status' => $doctor->isPublished() ? 'Active' : 'Inactive',
          'edit_url' => Url::fromRoute('entity.node.edit_form', ['node' => $doctor->id()])->toString(),
          'delete_url' => Url::fromRoute('entity.node.delete_form', ['node' => $doctor->id()])->toString(),
          'view_url' => Url::fromRoute('entity.node.canonical', ['node' => $doctor->id()])->toString(),
        ];
      }
    }
    
    return [
      '#theme' => 'manage_doctors',
      '#doctors' => $doctors,
      '#attached' => [
        'library' => ['practo_core/admin-dashboard'],
      ],
    ];
  }

  /**
   * Manage Appointments page with CRUD operations.
   */
  public function manageAppointments() {
    
    // Get all appointments
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->sort('field_appointment_date', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $appointments = [];
    
    if (!empty($nids)) {
      $appointment_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      
      foreach ($appointment_nodes as $appt) {
        // Get doctor name
        $doctor_id = $appt->get('field_doctor_ref')->target_id;
        $doctor = \Drupal\node\Entity\Node::load($doctor_id);
        $doctor_name = $doctor ? $doctor->getTitle() : 'N/A';
        
        // Get patient name
        $patient_id = $appt->get('field_patient_ref')->target_id;
        $patient = \Drupal\user\Entity\User::load($patient_id);
        $patient_name = $patient ? $patient->getDisplayName() : 'N/A';
        
        $appointments[] = [
          'id' => $appt->id(),
          'booking_id' => $appt->get('field_booking_id')->value,
          'patient_name' => $patient_name,
          'doctor_name' => $doctor_name,
          'date' => $appt->get('field_appointment_date')->value,
          'time' => $appt->get('field_appointment_time')->value,
          'status' => $appt->get('field_appointment_status')->value,
          'edit_url' => Url::fromRoute('entity.node.edit_form', ['node' => $appt->id()])->toString(),
          'delete_url' => Url::fromRoute('entity.node.delete_form', ['node' => $appt->id()])->toString(),
          'view_url' => Url::fromRoute('entity.node.canonical', ['node' => $appt->id()])->toString(),
        ];
      }
    }
    
    return [
      '#theme' => 'manage_appointments',
      '#appointments' => $appointments,
      '#attached' => [
        'library' => ['practo_core/admin-dashboard'],
      ],
    ];
  }

  /**
   * Manage Health Packages.
   */
  public function managePackages() {
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'health_package')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $packages = [];
    
    if (!empty($nids)) {
      $package_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      
      foreach ($package_nodes as $package) {
        $packages[] = [
          'id' => $package->id(),
          'name' => $package->getTitle(),
          'tests' => $package->get('field_tests_count')->value,
          'price' => $package->get('field_package_price')->value,
          'discount' => $package->get('field_discount')->value,
          'status' => $package->isPublished() ? 'Active' : 'Inactive',
          'edit_url' => Url::fromRoute('entity.node.edit_form', ['node' => $package->id()])->toString(),
          'delete_url' => Url::fromRoute('entity.node.delete_form', ['node' => $package->id()])->toString(),
        ];
      }
    }
    
    return [
      '#theme' => 'manage_packages',
      '#packages' => $packages,
      '#attached' => [
        'library' => ['practo_core/admin-dashboard'],
      ],
    ];
  }

  /**
   * Manage Health Articles.
   */
  public function manageArticles() {
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'health_article')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $articles = [];
    
    if (!empty($nids)) {
      $article_nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
      
      foreach ($article_nodes as $article) {
        $articles[] = [
          'id' => $article->id(),
          'title' => $article->getTitle(),
          'category' => $article->get('field_article_category')->value,
          'author' => $article->get('field_article_author')->value,
          'created' => date('M j, Y', $article->getCreatedTime()),
          'status' => $article->isPublished() ? 'Published' : 'Draft',
          'edit_url' => Url::fromRoute('entity.node.edit_form', ['node' => $article->id()])->toString(),
          'delete_url' => Url::fromRoute('entity.node.delete_form', ['node' => $article->id()])->toString(),
          'view_url' => Url::fromRoute('entity.node.canonical', ['node' => $article->id()])->toString(),
        ];
      }
    }
    
    return [
      '#theme' => 'manage_articles',
      '#articles' => $articles,
      '#attached' => [
        'library' => ['practo_core/admin-dashboard'],
      ],
    ];
  }

  /**
   * Get platform statistics.
   */
  private function getStatistics() {
    
    $stats = [];
    
    // Total Doctors
    $stats['total_doctors'] = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Total Appointments
    $stats['total_appointments'] = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Pending Appointments
    $stats['pending_appointments'] = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_appointment_status', 'pending')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Confirmed Appointments
    $stats['confirmed_appointments'] = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_appointment_status', 'confirmed')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Total Health Packages
    $stats['total_packages'] = \Drupal::entityQuery('node')
      ->condition('type', 'health_package')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Total Health Articles
    $stats['total_articles'] = \Drupal::entityQuery('node')
      ->condition('type', 'health_article')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Total Registered Users
    $stats['total_users'] = \Drupal::entityQuery('user')
      ->condition('uid', 0, '>')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    return $stats;
  }

  /**
   * AJAX endpoint to get updated statistics.
   */
  public function getStatsAjax() {
    
    // Get fresh statistics
    $stats = $this->getStatistics();
    
    // Return JSON response
    return new \Symfony\Component\HttpFoundation\JsonResponse([
      'success' => TRUE,
      'stats' => $stats,
      'timestamp' => time()
    ]);
  }

  /**
   * AJAX endpoint to change record status.
   */
  public function changeStatus($type, $id) {
    
    try {
      // Validate type
      $valid_types = ['doctors', 'appointments', 'packages', 'articles'];
      if (!in_array($type, $valid_types)) {
        throw new \Exception('Invalid record type');
      }
      
      // Map type to node type
      $node_type_map = [
        'doctors' => 'doctor',
        'appointments' => 'appointment',
        'packages' => 'health_package',
        'articles' => 'health_article'
      ];
      
      $node_type = $node_type_map[$type];
      
      // Load the node
      $node = \Drupal\node\Entity\Node::load($id);
      if (!$node || $node->bundle() !== $node_type) {
        throw new \Exception('Record not found');
      }
      
      // Toggle status
      $current_status = $node->isPublished();
      $node->setPublished(!$current_status);
      $node->save();
      
      // Get new status text
      $new_status = $node->isPublished() ? 'Active' : 'Inactive';
      
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => TRUE,
        'new_status' => $new_status,
        'message' => 'Status updated successfully'
      ]);
      
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => FALSE,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * AJAX endpoint for bulk actions.
   */
  public function bulkActions() {
    
    try {
      $request = \Drupal::request();
      $action = $request->request->get('action');
      $ids = $request->request->get('ids', []);
      $type = $request->request->get('type');
      
      if (empty($ids) || empty($action)) {
        throw new \Exception('Missing required parameters');
      }
      
      $processed = 0;
      
      foreach ($ids as $id) {
        $node = \Drupal\node\Entity\Node::load($id);
        if ($node) {
          switch ($action) {
            case 'publish':
              $node->setPublished(TRUE);
              $node->save();
              $processed++;
              break;
              
            case 'unpublish':
              $node->setPublished(FALSE);
              $node->save();
              $processed++;
              break;
              
            case 'delete':
              $node->delete();
              $processed++;
              break;
          }
        }
      }
      
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => TRUE,
        'message' => "Processed {$processed} records successfully"
      ]);
      
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => FALSE,
        'message' => $e->getMessage()
      ], 400);
    }
  }

  /**
   * AJAX endpoint for quick search.
   */
  public function quickSearch() {
    
    try {
      $request = \Drupal::request();
      $query = $request->query->get('q');
      $type = $request->query->get('type', 'all');
      
      if (empty($query)) {
        throw new \Exception('Search query is required');
      }
      
      $results = [];
      
      if ($type === 'all' || $type === 'doctors') {
        $doctor_query = \Drupal::entityQuery('node')
          ->condition('type', 'doctor')
          ->condition('title', '%' . $query . '%', 'LIKE')
          ->accessCheck(FALSE)
          ->range(0, 5);
        
        $doctor_ids = $doctor_query->execute();
        $doctors = \Drupal\node\Entity\Node::loadMultiple($doctor_ids);
        
        foreach ($doctors as $doctor) {
          $results[] = [
            'id' => $doctor->id(),
            'title' => $doctor->getTitle(),
            'type' => 'doctor',
            'url' => $doctor->toUrl('edit-form')->toString()
          ];
        }
      }
      
      if ($type === 'all' || $type === 'appointments') {
        $appointment_query = \Drupal::entityQuery('node')
          ->condition('type', 'appointment')
          ->condition('field_booking_id', '%' . $query . '%', 'LIKE')
          ->accessCheck(FALSE)
          ->range(0, 5);
        
        $appointment_ids = $appointment_query->execute();
        $appointments = \Drupal\node\Entity\Node::loadMultiple($appointment_ids);
        
        foreach ($appointments as $appointment) {
          $results[] = [
            'id' => $appointment->id(),
            'title' => $appointment->get('field_booking_id')->value,
            'type' => 'appointment',
            'url' => $appointment->toUrl('edit-form')->toString()
          ];
        }
      }
      
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => TRUE,
        'results' => $results
      ]);
      
    } catch (\Exception $e) {
      return new \Symfony\Component\HttpFoundation\JsonResponse([
        'success' => FALSE,
        'message' => $e->getMessage()
      ], 400);
    }
  }
}