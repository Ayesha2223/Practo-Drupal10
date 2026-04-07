<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller for doctor-related pages.
 * 
 * Handles:
 * - Doctor listing page
 * - Individual doctor profile page
 * - Doctor search functionality
 */
class DoctorController extends ControllerBase {

  /**
   * Displays list of all doctors with filtering options.
   * 
   * @return array
   *   Render array for the doctor listing page.
   */
  public function doctorList(Request $request) {
    
    // Get filter parameters from URL
    $specialization = $request->query->get('specialization');
    $city = $request->query->get('city');
    $search_name = $request->query->get('name');
    
    // Build query to fetch doctors
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->sort('title', 'ASC')
      ->accessCheck(FALSE);
    
    // Apply filters if provided
    if ($specialization && $specialization != 'all') {
      $query->condition('field_specialization', $specialization);
    }
    
    if ($city) {
      $query->condition('field_city', $city, 'CONTAINS');
    }
    
    if ($search_name) {
      $query->condition('title', $search_name, 'CONTAINS');
    }
    
    // Execute query
    $nids = $query->execute();
    
    // Load doctor nodes
    $doctors = Node::loadMultiple($nids);

    $cache_tags = [];
    
    // Build doctor cards
    $doctor_items = [];
    foreach ($doctors as $doctor) {
      $doctor_items[] = $this->buildDoctorCard($doctor);
      $cache_tags = array_merge($cache_tags, $doctor->getCacheTags());
    }
    
    // Build filter form
    $filter_form = \Drupal::formBuilder()->getForm('Drupal\practo_core\Form\DoctorFilterForm');
    
    // Build page render array
    $build = [
      '#theme' => 'practo_doctor_listing',
      '#filter_form' => $filter_form,
      '#doctors' => $doctor_items,
      '#total_count' => count($doctors),
      '#cache' => [
        'contexts' => ['url.query_args:city', 'url.query_args:specialization', 'url.query_args:name'],
        'tags' => $cache_tags,
      ],
      '#attached' => [
        'library' => [
          'practo_core/doctor-listing',
        ],
      ],
    ];
    
    return $build;
  }
  
  /**
   * Builds a doctor card render array.
   * 
   * @param \Drupal\node\Entity\Node $doctor
   *   The doctor node.
   * 
   * @return array
   *   Render array for a single doctor card.
   */
  private function buildDoctorCard(Node $doctor) {
    
    // Get field values
    $specialization = $doctor->get('field_specialization')->value ?? '';
    $experience = $doctor->get('field_experience_years')->value ?? 0;
    $fee = $doctor->get('field_consultation_fee')->value ?? 0;
    $clinic = $doctor->get('field_clinic_name')->value ?? '';
    $city = $doctor->get('field_city')->value ?? '';
    
    // Get specialization label
    $specialization_options = [
      'cardiology' => 'Cardiologist',
      'dermatology' => 'Dermatologist',
      'pediatrics' => 'Pediatrician',
      'orthopedics' => 'Orthopedic Surgeon',
      'general' => 'General Physician',
      'gynecology' => 'Gynecologist',
      'neurology' => 'Neurologist',
      'psychiatry' => 'Psychiatrist',
      'dentistry' => 'Dentist',
      'ophthalmology' => 'Ophthalmologist',
    ];
    $specialization_label = $specialization_options[$specialization] ?? $specialization;
    
    // Build view profile link
    $view_url = Url::fromRoute('practo_core.doctor_detail', ['doctor' => $doctor->id()]);
    $view_link = Link::fromTextAndUrl('View Profile', $view_url)->toString();
    
    // Build book appointment link
    $book_url = Url::fromRoute('practo_core.book_appointment', ['doctor' => $doctor->id()]);
    $book_link = Link::fromTextAndUrl('Book Appointment', $book_url)->toString();
    
    return [
      '#theme' => 'practo_doctor_card',
      '#doctor_id' => $doctor->id(),
      '#doctor_name' => $doctor->getTitle(),
      '#specialization' => $specialization_label,
      '#experience' => $experience,
      '#fee' => number_format($fee, 2),
      '#clinic' => $clinic,
      '#city' => $city,
      '#view_link' => $view_link,
      '#book_link' => $book_link,
    ];
  }
  
  /**
   * Displays individual doctor profile page.
   * 
   * @param int $doctor
   *   The doctor node ID.
   * 
   * @return array
   *   Render array for doctor profile page.
   */
  public function doctorDetail($doctor) {
    
    // Load doctor node
    $doctor_node = Node::load($doctor);
    
    if (!$doctor_node || $doctor_node->bundle() != 'doctor') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $cache_tags = $doctor_node->getCacheTags();
    
    // Get all field values
    $data = [
      'name' => $doctor_node->getTitle(),
      'specialization' => $doctor_node->get('field_specialization')->value ?? '',
      'qualification' => $doctor_node->get('field_qualification')->value ?? '',
      'experience' => $doctor_node->get('field_experience_years')->value ?? 0,
      'fee' => $doctor_node->get('field_consultation_fee')->value ?? 0,
      'clinic_name' => $doctor_node->get('field_clinic_name')->value ?? '',
      'clinic_address' => $doctor_node->get('field_clinic_address')->value ?? '',
      'city' => $doctor_node->get('field_city')->value ?? '',
      'phone' => $doctor_node->get('field_phone')->value ?? '',
      'email' => $doctor_node->get('field_email')->value ?? '',
      'consultation_hours' => $doctor_node->get('field_consultation_hours')->value ?? '',
      'about' => $doctor_node->get('field_about_doctor')->value ?? '',
      'languages' => $doctor_node->get('field_languages_spoken')->value ?? '',
    ];
    
    // Get available days
    $available_days = [];
    if (!$doctor_node->get('field_available_days')->isEmpty()) {
      foreach ($doctor_node->get('field_available_days')->getValue() as $day) {
        $day_labels = [
          'monday' => 'Monday',
          'tuesday' => 'Tuesday',
          'wednesday' => 'Wednesday',
          'thursday' => 'Thursday',
          'friday' => 'Friday',
          'saturday' => 'Saturday',
          'sunday' => 'Sunday',
        ];
        $available_days[] = $day_labels[$day['value']] ?? $day['value'];
      }
    }
    $data['available_days'] = empty($available_days) ? 'Not specified' : implode(', ', $available_days);
    
    // Get specialization label
    $specialization_options = [
      'cardiology' => 'Cardiologist',
      'dermatology' => 'Dermatologist',
      'pediatrics' => 'Pediatrician',
      'orthopedics' => 'Orthopedic Surgeon',
      'general' => 'General Physician',
      'gynecology' => 'Gynecologist',
      'neurology' => 'Neurologist',
      'psychiatry' => 'Psychiatrist',
      'dentistry' => 'Dentist',
      'ophthalmology' => 'Ophthalmologist',
    ];
    $data['specialization_label'] = $specialization_options[$data['specialization']] ?? $data['specialization'];
    
    // Build book appointment button
    $book_url = Url::fromRoute('practo_core.book_appointment', ['doctor' => $doctor]);
    $data['book_link'] = Link::fromTextAndUrl('Book Appointment Now', $book_url)->toString();
    
    // Get recent reviews (if you add review functionality later)
    $data['reviews'] = [];
    
    $build = [
      '#theme' => 'practo_doctor_profile',
      '#data' => $data,
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => $cache_tags,
      ],
      '#attached' => [
        'library' => [
          'practo_core/doctor-profile',
        ],
      ],
    ];
    
    return $build;
  }
  
  /**
   * Returns the page title for doctor profile.
   * 
   * @param int $doctor
   *   The doctor node ID.
   * 
   * @return string
   *   The page title.
   */
  public function doctorTitle($doctor) {
    $doctor_node = Node::load($doctor);
    if ($doctor_node) {
      return 'Dr. ' . $doctor_node->getTitle();
    }
    return 'Doctor Profile';
  }
  
  /**
   * Handles doctor search with AJAX.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * 
   * @return array
   *   Render array with search results.
   */
  public function searchDoctors(Request $request) {
    
    $search_term = $request->query->get('q');
    $specialization = $request->query->get('specialization');
    $city = $request->query->get('city');
    
    if (!$search_term && !$specialization && !$city) {
      return [
        '#markup' => '<p>Please enter search criteria.</p>',
      ];
    }
    
    // Build search query
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->accessCheck(FALSE);
    
    if ($search_term) {
      $group = $query->orConditionGroup()
        ->condition('title', $search_term, 'CONTAINS')
        ->condition('field_clinic_name', $search_term, 'CONTAINS');
      $query->condition($group);
    }
    
    if ($specialization) {
      $query->condition('field_specialization', $specialization);
    }
    
    if ($city) {
      $query->condition('field_city', $city, 'CONTAINS');
    }
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      return [
        '#markup' => '<div class="no-results"><p>No doctors found matching your search criteria.</p></div>',
      ];
    }
    
    $doctors = Node::loadMultiple($nids);
    $doctor_items = [];
    
    foreach ($doctors as $doctor) {
      $doctor_items[] = $this->buildDoctorCard($doctor);
    }
    
    return [
      '#theme' => 'practo_search_results',
      '#doctors' => $doctor_items,
      '#count' => count($doctors),
      '#search_term' => $search_term,
    ];
  }
}