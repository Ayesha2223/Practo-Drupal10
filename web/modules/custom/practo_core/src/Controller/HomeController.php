<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller for Practo-style homepage.
 */
class HomeController extends ControllerBase {

  /**
   * Displays Practo-style homepage.
   */
  public function homepage() {
    
    // Get all cities from doctors
    $cities = $this->getCities();
    
    // Get all specializations
    $specializations = $this->getSpecializations();
    
    // Get featured doctors
    $featured_doctors = $this->getFeaturedDoctors();
    
    // Get statistics
    $stats = $this->getStatistics();
    
    // Health checkup packages
    $health_packages = [
      [
        'name' => 'Full Body Checkup',
        'tests' => 98,
        'price' => 1999,
        'discount' => 50,
        'image' => 'full-body.jpg',
      ],
      [
        'name' => 'Diabetes Screening',
        'tests' => 45,
        'price' => 899,
        'discount' => 30,
        'image' => 'diabetes.jpg',
      ],
      [
        'name' => 'Heart Health Checkup',
        'tests' => 62,
        'price' => 1499,
        'discount' => 40,
        'image' => 'heart.jpg',
      ],
      [
        'name' => 'Women Wellness',
        'tests' => 78,
        'price' => 1799,
        'discount' => 45,
        'image' => 'women.jpg',
      ],
    ];
    
    // Health articles
    $base_path = \Drupal::request()->getBasePath();
    $module_path = \Drupal::service('extension.list.module')->getPath('practo_core');
    $asset_base = rtrim($base_path, '/') . '/' . trim($module_path, '/') . '/';
    $health_articles = [
      [
        'title' => '10 Tips for Better Sleep',
        'category' => 'Wellness',
        'image' => $asset_base . 'images/sleep.jpg',
      ],
      [
        'title' => 'Understanding Diabetes',
        'category' => 'Health Conditions',
        'image' => $asset_base . 'images/diabetes-article.jpg',
      ],
      [
        'title' => 'Healthy Diet Plans',
        'category' => 'Nutrition',
        'image' => $asset_base . 'images/diet.jpg',
      ],
    ];
    
    $build = [
      '#theme' => 'practo_homepage_full',
      '#cities' => $cities,
      '#specializations' => $specializations,
      '#featured_doctors' => $featured_doctors,
      '#statistics' => $stats,
      '#health_packages' => $health_packages,
      '#health_articles' => $health_articles,
      '#attached' => [
        'library' => [
          'practo_core/homepage-full',
        ],
      ],
    ];
    
    return $build;
  }
  
  /**
   * Get all cities from doctor nodes.
   */
  private function getCities() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $cities = [];
    
    if (!empty($nids)) {
      $doctors = Node::loadMultiple($nids);
      foreach ($doctors as $doctor) {
        $city = $doctor->get('field_city')->value;
        if ($city && !in_array($city, $cities)) {
          $cities[] = $city;
        }
      }
    }
    
    return array_values($cities);
  }
  
  /**
   * Get all specializations.
   */
  private function getSpecializations() {
    return [
      'dentist' => [
        'label' => 'Dentist',
        'icon' => 'tooth',
        'description' => 'Teething troubles? Schedule a dental checkup',
      ],
      'gynecologist' => [
        'label' => 'Gynecologist/Obstetrician',
        'icon' => 'female',
        'description' => 'Explore for women\'s health, pregnancy and infertility treatments',
      ],
      'dietitian' => [
        'label' => 'Dietitian/Nutrition',
        'icon' => 'apple-alt',
        'description' => 'Get guidance on eating right, weight management and sports nutrition',
      ],
      'physiotherapist' => [
        'label' => 'Physiotherapist',
        'icon' => 'walking',
        'description' => 'Pulled a muscle? Get it treated by a trained physiotherapist',
      ],
      'general_surgeon' => [
        'label' => 'General Surgeon',
        'icon' => 'user-md',
        'description' => 'Need to get operated? Find the right surgeon',
      ],
      'orthopedist' => [
        'label' => 'Orthopedist',
        'icon' => 'bone',
        'description' => 'For bone and joint problems, spinal injuries, sports injuries',
      ],
      'general_physician' => [
        'label' => 'General Physician',
        'icon' => 'stethoscope',
        'description' => 'Find the right doctor and get treated for any health concerns',
      ],
      'pediatrician' => [
        'label' => 'Pediatrician',
        'icon' => 'baby',
        'description' => 'Child specialists and doctors for infant care',
      ],
      'gastroenterologist' => [
        'label' => 'Gastroenterologist',
        'icon' => 'stomach',
        'description' => 'Troubled by digestive issues? Get expert care',
      ],
      'dermatologist' => [
        'label' => 'Dermatologist',
        'icon' => 'hand-sparkles',
        'description' => 'For skin, hair and nail care treatments',
      ],
      'ent_specialist' => [
        'label' => 'ENT Specialist',
        'icon' => 'ear',
        'description' => 'Throat, ear or nose problems? Get treated by a specialist',
      ],
      'homeopath' => [
        'label' => 'Homeopath',
        'icon' => 'leaf',
        'description' => 'Get natural homeopathic treatment',
      ],
      'ayurveda' => [
        'label' => 'Ayurveda',
        'icon' => 'spa',
        'description' => 'Explore Ayurvedic treatments and therapies',
      ],
      'cardiologist' => [
        'label' => 'Cardiologist',
        'icon' => 'heartbeat',
        'description' => 'Heart disease? Consult a cardiologist',
      ],
    ];
  }
  
  /**
   * Get featured doctors.
   */
  private function getFeaturedDoctors() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 8)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $featured = [];
    
    if (!empty($nids)) {
      $doctors = Node::loadMultiple($nids);
      
      foreach ($doctors as $doctor) {
        $photo_url = '';

        
        $featured[] = [
          'id' => $doctor->id(),
          'name' => $doctor->getTitle(),
          'specialization' => $doctor->get('field_specialization')->value,
          'qualification' => $doctor->get('field_qualification')->value,
          'experience' => $doctor->get('field_experience_years')->value,
          'fee' => $doctor->get('field_consultation_fee')->value,
          'clinic' => $doctor->get('field_clinic_name')->value,
          'city' => $doctor->get('field_city')->value,
          'photo_url' => $photo_url,
        ];
      }
    }
    
    return $featured;
  }
  
  /**
   * Get platform statistics.
   */
  private function getStatistics() {
    $doctor_count = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $appointment_count = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    return [
      'doctors' => $doctor_count,
      'appointments' => $appointment_count,
      'cities' => 50,
      'clinics' => 200,
    ];
  }
}