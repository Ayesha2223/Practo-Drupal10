<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Simple doctor dashboard controller.
 */
class DoctorDashboardController extends ControllerBase {

  /**
   * Doctor dashboard page.
   */
  public function dashboard() {
    
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $current_user->hasPermission('administer site configuration') || $current_user->hasRole('administrator') || $current_user->hasRole('admin');
    $is_doctor = ($user_type === 'doctor') || $current_user->hasRole('doctor');

    // Some sites/users may have a doctor profile created but the user role/type
    // wasn't set correctly. Treat them as doctor for this dashboard to avoid
    // redirecting them away and showing empty details.
    $has_doctor_profile = FALSE;
    $doctor_id_candidates = [];
    
    $doctor_query_by_uid = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('uid', $current_user->id())
      ->sort('changed', 'DESC')
      ->accessCheck(FALSE);
    $doctor_id_candidates = $doctor_query_by_uid->execute();

    if (empty($doctor_id_candidates)) {
      $email = $user_entity ? $user_entity->getEmail() : '';
      if ($email) {
        $doctor_query_by_email = \Drupal::entityQuery('node')
          ->condition('type', 'doctor')
          ->condition('field_email', $email)
          ->sort('changed', 'DESC')
          ->accessCheck(FALSE);
        $doctor_id_candidates = $doctor_query_by_email->execute();
      }
    }

    $has_doctor_profile = !empty($doctor_id_candidates);
    if ($has_doctor_profile) {
      $is_doctor = TRUE;
    }

    // Enforce correct entry-point:
    // - Doctors can access this dashboard.
    // - Admins go to admin dashboard.
    // - Patients go to their appointments page.
    if ($is_admin) {
      return new \Symfony\Component\HttpFoundation\RedirectResponse(Url::fromRoute('practo_core.admin_dashboard')->toString());
    }

    if (!$is_doctor) {
      return new \Symfony\Component\HttpFoundation\RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
    }
    
    // Get doctor node for this user.
    $doctor_nids = $doctor_id_candidates;
    
    if (empty($doctor_nids)) {
      $email = $user_entity ? $user_entity->getEmail() : '';
      if ($email) {
        $doctor_query_by_email = \Drupal::entityQuery('node')
          ->condition('type', 'doctor')
          ->condition('field_email', $email)
          ->sort('changed', 'DESC')
          ->accessCheck(FALSE);
        $doctor_nids = $doctor_query_by_email->execute();
      }

      if (empty($doctor_nids)) {
        return [
          '#markup' => '<div class="alert alert-warning">Doctor profile not found. Please contact admin.</div>',
        ];
      }
    }
    
    $all_doctor_ids = array_values($doctor_nids);
    $doctor_id = reset($doctor_nids);
    $doctor = Node::load($doctor_id);

    $getFieldValue = function ($entity, string $field_name): string {
      if (!$entity || !$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        return '';
      }

      $item = $entity->get($field_name)->first();
      if ($item && isset($item->value)) {
        return (string) $item->value;
      }

      return '';
    };

    // Auto-repair doctor node ownership if needed.
    // If doctor node email matches the logged-in doctor user, but owner is wrong,
    // align ownership so edit/actions work consistently.
    if ($doctor && $user_entity && $is_doctor && $doctor->getOwnerId() !== $current_user->id()) {
      $doctor_email = ($doctor->hasField('field_email') && !$doctor->get('field_email')->isEmpty()) ? $doctor->get('field_email')->value : '';
      if ($doctor_email && $doctor_email === $user_entity->getEmail()) {
        $doctor->setOwnerId($current_user->id());
        $doctor->save();
      }
    }

    // Backfill basic fields for robustness (common when doctor node was created
    // without setting required fields or was imported).
    if ($doctor && $user_entity && $is_doctor) {
      $did_mutate = FALSE;

      if ($doctor->hasField('field_email') && $doctor->get('field_email')->isEmpty()) {
        $doctor->set('field_email', $user_entity->getEmail());
        $did_mutate = TRUE;
      }

      if ($doctor->hasField('field_phone') && $doctor->get('field_phone')->isEmpty() && $user_entity->hasField('field_phone_number') && !$user_entity->get('field_phone_number')->isEmpty()) {
        $doctor->set('field_phone', $user_entity->get('field_phone_number')->value);
        $did_mutate = TRUE;
      }

      if (!$doctor->label()) {
        $doctor->setTitle($user_entity->getDisplayName());
        $did_mutate = TRUE;
      }

      if ($did_mutate) {
        $doctor->save();
      }
    }

    $profile_url = Url::fromRoute('practo_core.doctor_detail', ['doctor' => $doctor_id])->toString();
    $doctor_published = $doctor ? $doctor->isPublished() : FALSE;

    $doctor_name = $doctor ? $doctor->label() : '';
    if (!$doctor_name && $user_entity) {
      $doctor_name = $user_entity->getDisplayName();
    }
    if (!$doctor_name && $user_entity) {
      $email = $user_entity->getEmail();
      $doctor_name = $email ? strtok($email, '@') : '';
    }

    $doctor_photo_url = '';

    $doctor_meta = [
      'specialization' => $getFieldValue($doctor, 'field_specialization') ?: $getFieldValue($user_entity, 'field_specialization'),
      'clinic_name' => $getFieldValue($doctor, 'field_clinic_name'),
      'city' => $getFieldValue($doctor, 'field_city'),
      'phone' => $getFieldValue($doctor, 'field_phone') ?: $getFieldValue($user_entity, 'field_phone_number'),
      'email' => $getFieldValue($doctor, 'field_email') ?: ($user_entity ? $user_entity->getEmail() : ''),
      'fee' => $getFieldValue($doctor, 'field_consultation_fee'),
      'experience_years' => $getFieldValue($doctor, 'field_experience_years'),
    ];

    $filter = \Drupal::request()->query->get('filter') ?: 'active';
    $allowed_filters = ['active', 'all', 'completed', 'cancelled'];
    if (!in_array($filter, $allowed_filters, TRUE)) {
      $filter = 'active';
    }

    $total_appointments_count = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_doctor_ref', $all_doctor_ids, 'IN')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    // Get doctor's appointments
    $appointments_query = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_doctor_ref', $all_doctor_ids, 'IN')
      ->sort('field_appointment_date', 'DESC')
      ->accessCheck(FALSE);

    if ($filter === 'active') {
      $appointments_query->condition('field_appointment_status', ['pending', 'confirmed'], 'IN');
    }
    elseif ($filter === 'completed') {
      $appointments_query->condition('field_appointment_status', 'completed');
    }
    elseif ($filter === 'cancelled') {
      $appointments_query->condition('field_appointment_status', 'cancelled');
    }
    
    $appointment_nids = $appointments_query->execute();
    $appointments = [];
    
    if (!empty($appointment_nids)) {
      $appointment_nodes = Node::loadMultiple($appointment_nids);
      
      foreach ($appointment_nodes as $appointment) {
        $appointments[] = $this->buildAppointmentRow($appointment);
      }
    }
    
    return [
      '#theme' => 'doctor_dashboard',
      '#doctor_name' => $doctor_name,
      '#doctor_id' => $doctor_id,
      '#doctor_profile_url' => $profile_url,
      '#doctor_photo_url' => $doctor_photo_url,
      '#doctor_published' => $doctor_published,
      '#doctor_meta' => $doctor_meta,
      '#active_filter' => $filter,
      '#appointments' => $appointments,
      '#total_appointments' => (int) $total_appointments_count,
      '#attached' => [
        'library' => ['practo_core/doctor-dashboard'],
      ],
    ];
  }

  /**
   * Builds appointment row data.
   */
  private function buildAppointmentRow($appointment) {
    
    // Get patient info
    $patient_id = $appointment->get('field_patient_ref')->target_id;
    $patient = \Drupal\user\Entity\User::load($patient_id);
    $patient_name = $patient ? $patient->getDisplayName() : 'Unknown';
    
    // Get appointment details
    $date = $appointment->get('field_appointment_date')->value ?? '';
    $time = $appointment->get('field_appointment_time')->value ?? '';
    $status = $appointment->get('field_appointment_status')->value ?? 'pending';
    $booking_id = $appointment->get('field_booking_id')->value ?? '';
    
    // Format date
    if ($date) {
      $date_obj = new \DateTime($date);
      $formatted_date = $date_obj->format('M j, Y');
    } else {
      $formatted_date = 'Not set';
    }
    
    // Time labels
    $time_labels = [
      '09:00' => '09:00 AM',
      '09:30' => '09:30 AM',
      '10:00' => '10:00 AM',
      '10:30' => '10:30 AM',
      '11:00' => '11:00 AM',
      '11:30' => '11:30 AM',
      '12:00' => '12:00 PM',
      '14:00' => '02:00 PM',
      '14:30' => '02:30 PM',
      '15:00' => '03:00 PM',
      '15:30' => '03:30 PM',
      '16:00' => '04:00 PM',
      '16:30' => '04:30 PM',
      '17:00' => '05:00 PM',
    ];
    $formatted_time = $time_labels[$time] ?? $time;
    
    // Status badge class
    $status_classes = [
      'pending' => 'badge-warning',
      'confirmed' => 'badge-success',
      'completed' => 'badge-info',
      'cancelled' => 'badge-danger',
    ];
    $status_class = $status_classes[$status] ?? 'badge-secondary';
    
    // Action links
    $actions = [];
    if ($status === 'pending') {
      $confirm_url = Url::fromRoute('practo_core.confirm_appointment', ['appointment' => $appointment->id()]);
      $actions[] = Link::fromTextAndUrl('Confirm', $confirm_url)->toString();

      $reject_url = Url::fromRoute('practo_core.reject_appointment', ['appointment' => $appointment->id()]);
      $actions[] = Link::fromTextAndUrl('Reject', $reject_url)->toString();
    }
    
    return [
      'booking_id' => $booking_id,
      'patient_name' => $patient_name,
      'date' => $formatted_date,
      'time' => $formatted_time,
      'status' => ucfirst($status),
      'status_class' => $status_class,
      'actions' => implode(' | ', $actions),
    ];
  }

}
