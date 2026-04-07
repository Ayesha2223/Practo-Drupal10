<?php

namespace Drupal\practo_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Featured Doctors' Block.
 *
 * @Block(
 *   id = "practo_featured_doctors_block",
 *   admin_label = @Translation("Featured Doctors Block"),
 *   category = @Translation("Practo"),
 * )
 */
class FeaturedDoctorsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    // Get 3 latest doctors
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    $doctors = [];
    if (!empty($nids)) {
      $doctor_nodes = Node::loadMultiple($nids);
      
      foreach ($doctor_nodes as $doctor) {
        
        $photo_url = '';
        
        $doctors[] = [
          'id' => $doctor->id(),
          'name' => $doctor->getTitle(),
          'specialization' => $doctor->get('field_specialization')->value,
          'experience' => $doctor->get('field_experience_years')->value,
          'photo_url' => $photo_url,
          'profile_url' => Url::fromRoute('practo_core.doctor_detail', 
            ['doctor' => $doctor->id()])->toString(),
        ];
      }
    }
    
    return [
      '#theme' => 'practo_featured_doctors_block',
      '#doctors' => $doctors,
      '#attached' => [
        'library' => [
          'practo_core/featured-doctors',
        ],
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 1800; // 30 minutes
  }
}