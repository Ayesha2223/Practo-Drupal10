<?php

namespace Drupal\practo_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides an 'Appointment Statistics' Block.
 *
 * @Block(
 *   id = "practo_appointment_stats_block",
 *   admin_label = @Translation("Appointment Statistics Block"),
 *   category = @Translation("Practo"),
 * )
 */
class AppointmentStatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    $current_user = \Drupal::currentUser();
    
    if ($current_user->isAnonymous()) {
      return [];
    }
    
    $user_id = $current_user->id();
    
    // Count total appointments
    $total = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_patient_ref', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Count upcoming appointments
    $upcoming = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_patient_ref', $user_id)
      ->condition('field_appointment_date', date('Y-m-d'), '>=')
      ->condition('field_appointment_status', ['cancelled', 'completed'], 'NOT IN')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Count completed appointments
    $completed = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_patient_ref', $user_id)
      ->condition('field_appointment_status', 'completed')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    return [
      '#theme' => 'practo_appointment_stats_block',
      '#total' => $total,
      '#upcoming' => $upcoming,
      '#completed' => $completed,
      '#attached' => [
        'library' => [
          'practo_core/stats-block',
        ],
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Cache per user
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Invalidate when appointments change
    return Cache::mergeTags(parent::getCacheTags(), ['node_list:appointment']);
  }
}