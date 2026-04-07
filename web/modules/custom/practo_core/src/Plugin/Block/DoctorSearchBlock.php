<?php

namespace Drupal\practo_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Doctor Search' Block.
 *
 * @Block(
 *   id = "practo_doctor_search_block",
 *   admin_label = @Translation("Doctor Search Block"),
 *   category = @Translation("Practo"),
 * )
 */
class DoctorSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    // Get all unique specializations
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    $specializations = [];
    $cities = [];
    
    if (!empty($nids)) {
      $doctors = Node::loadMultiple($nids);
      
      foreach ($doctors as $doctor) {
        $spec = $doctor->get('field_specialization')->value;
        $city = $doctor->get('field_city')->value;
        
        if ($spec) {
          $specializations[$spec] = $spec;
        }
        if ($city) {
          $cities[$city] = $city;
        }
      }
    }
    
    return [
      '#theme' => 'practo_doctor_search_block',
      '#specializations' => $specializations,
      '#cities' => $cities,
      '#attached' => [
        'library' => [
          'practo_core/search-block',
        ],
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache for 1 hour
    return 3600;
  }
}