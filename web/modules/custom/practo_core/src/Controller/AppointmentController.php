<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for appointment-related pages.
 * 
 * Handles:
 * - My Appointments page
 * - Cancel appointment action
 * - Appointment management
 */
class AppointmentController extends ControllerBase {

  /**
   * Displays user's appointments.
   * 
   * @return array
   *   Render array for my appointments page.
   */
  public function myAppointments() {
    // Redirect based on user type
    $account = \Drupal::currentUser();
    /** @var \Drupal\user\Entity\User|null $user_entity */
    $user_entity = \Drupal\user\Entity\User::load($account->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $account->hasPermission('administer site configuration') || $account->hasRole('administrator') || $account->hasRole('admin');
    $is_doctor = ($user_type === 'doctor') || $account->hasRole('doctor');

    // Doctors should view doctor dashboard instead
    if ($is_doctor) {
      return new RedirectResponse(Url::fromRoute('practo_core.doctor_dashboard')->toString());
    }

    // Admins view admin dashboard or manage appointments
    if ($is_admin) {
      return new RedirectResponse(Url::fromRoute('practo_core.admin_dashboard')->toString());
    }
    
    // Get current user
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Query user's appointments
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'appointment')
      ->condition('field_patient_ref', $user_id)
      ->condition('status', 1)
      ->sort('field_appointment_date', 'DESC')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    if (empty($nids)) {
      return [
        '#markup' => '<div class="no-appointments">
          <h3>You have no appointments yet.</h3>
          <p>Book your first appointment with our experienced doctors.</p>
          <a href="' . \Drupal::request()->getBasePath() . '/doctors" class="btn btn-primary">Find Doctors</a>
        </div>',
      ];
    }
    
    // Load appointments
    $appointments = Node::loadMultiple($nids);
    
    // Separate into upcoming and past
    $upcoming = [];
    $past = [];
    $today = date('Y-m-d');
    
    foreach ($appointments as $appointment) {
      $appointment_data = $this->buildAppointmentCard($appointment);
      $date = $appointment->get('field_appointment_date')->value;
      
      if ($date >= $today && $appointment->get('field_appointment_status')->value != 'completed') {
        $upcoming[] = $appointment_data;
      } else {
        $past[] = $appointment_data;
      }
    }
    
    $build = [
      '#theme' => 'practo_my_appointments',
      '#upcoming' => $upcoming,
      '#past' => $past,
      '#attached' => [
        'library' => [
          'practo_core/appointments',
        ],
      ],
    ];
    
    return $build;
  }
  
  /**
   * Builds appointment card data.
   * 
   * @param \Drupal\node\Entity\Node $appointment
   *   The appointment node.
   * 
   * @return array
   *   Appointment data array.
   */
  private function buildAppointmentCard(Node $appointment) {
    
    // Get doctor details
    $doctor_id = $appointment->get('field_doctor_ref')->target_id;
    $doctor = Node::load($doctor_id);
    
    // Get appointment details
    $date = $appointment->get('field_appointment_date')->value;
    $time = $appointment->get('field_appointment_time')->value;
    $status = $appointment->get('field_appointment_status')->value;
    $booking_id = $appointment->get('field_booking_id')->value;
    
    // Format date
    $date_obj = new \DateTime($date);
    $formatted_date = $date_obj->format('D, M j, Y');
    
    // Format time
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
    
    // Status labels and classes
    $status_classes = [
      'pending' => 'status-pending',
      'confirmed' => 'status-confirmed',
      'completed' => 'status-completed',
      'cancelled' => 'status-cancelled',
    ];
    
    // Build cancel link if applicable
    $cancel_link = '';
    if (in_array($status, ['pending', 'confirmed'])) {
      $cancel_url = Url::fromRoute('practo_core.cancel_appointment', ['appointment' => $appointment->id()]);
      $cancel_link = \Drupal\Core\Link::fromTextAndUrl('Cancel', $cancel_url)->toString();
    }
    
    return [
      'appointment_id' => $appointment->id(),
      'booking_id' => $booking_id,
      'doctor_name' => $doctor ? $doctor->getTitle() : 'Unknown',
      'doctor_specialization' => $doctor ? ($doctor->get('field_specialization')->value ?? '') : '',
      'clinic_name' => $doctor ? ($doctor->get('field_clinic_name')->value ?? '') : '',
      'date' => $formatted_date,
      'time' => $formatted_time,
      'status' => ucfirst($status),
      'status_class' => $status_classes[$status] ?? 'status-pending',
      'reason' => $appointment->get('field_reason_visit')->value,
      'cancel_link' => $cancel_link,
    ];
  }

  private function doctorOwnsAppointment(Node $appointment_node, \Drupal\Core\Session\AccountInterface $account, ?\Drupal\user\Entity\User $user_entity): bool {
    $doctor_id = $appointment_node->get('field_doctor_ref')->target_id;
    if (!$doctor_id) {
      return FALSE;
    }

    $doctor_node = Node::load($doctor_id);
    if (!$doctor_node) {
      return FALSE;
    }

    if ((int) $doctor_node->getOwnerId() === (int) $account->id()) {
      return TRUE;
    }

    $email = $user_entity ? $user_entity->getEmail() : '';
    $doctor_email = ($doctor_node->hasField('field_email') && !$doctor_node->get('field_email')->isEmpty()) ? $doctor_node->get('field_email')->value : '';
    return ($email && $doctor_email && $email === $doctor_email);
  }

  /**
   * Confirms an appointment.
   * 
   * @param int $appointment
   *   The appointment node ID.
   * 
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to dashboard page.
   */
  public function confirmAppointment($appointment) {
    
    // Load appointment
    $appointment_node = Node::load($appointment);
    
    if (!$appointment_node || $appointment_node->bundle() != 'appointment') {
      \Drupal::messenger()->addError('Invalid appointment.');
      return new RedirectResponse(Url::fromRoute('practo_core.doctor_dashboard')->toString());
    }
    
    // Get current user and check permissions
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $current_user->hasPermission('administer site configuration') || $current_user->hasRole('administrator') || $current_user->hasRole('admin');
    $is_doctor = ($user_type === 'doctor') || $current_user->hasRole('doctor');
    $redirect_route = $is_admin ? 'practo_core.admin_dashboard' : 'practo_core.doctor_dashboard';
    
    // Check if user is doctor or admin
    if (!$is_doctor && !$is_admin) {
      \Drupal::messenger()->addError('You do not have permission to confirm appointments.');
      return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
    }
    
    // If user is doctor, check if this appointment is with them
    if ($is_doctor) {
      if (!$this->doctorOwnsAppointment($appointment_node, $current_user, $user_entity)) {
        \Drupal::messenger()->addError('You can only confirm appointments with you.');
        return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
      }
    }
    
    // Check if appointment can be confirmed
    $status = $appointment_node->get('field_appointment_status')->value;
    if ($status !== 'pending') {
      \Drupal::messenger()->addError('This appointment cannot be confirmed.');
      return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
    }
    
    // Confirm the appointment
    $appointment_node->set('field_appointment_status', 'confirmed');
    $appointment_node->save();
    
    \Drupal::messenger()->addStatus('Appointment has been confirmed successfully.');
    
    return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
  }

  /**
   * Rejects an appointment (sets status to cancelled).
   */
  public function rejectAppointment($appointment) {

    $appointment_node = Node::load($appointment);
    if (!$appointment_node || $appointment_node->bundle() != 'appointment') {
      \Drupal::messenger()->addError('Invalid appointment.');
      return new RedirectResponse(Url::fromRoute('practo_core.doctor_dashboard')->toString());
    }

    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $current_user->hasPermission('administer site configuration') || $current_user->hasRole('administrator') || $current_user->hasRole('admin');
    $is_doctor = ($user_type === 'doctor') || $current_user->hasRole('doctor');
    $redirect_route = $is_admin ? 'practo_core.admin_dashboard' : 'practo_core.doctor_dashboard';

    if (!$is_doctor && !$is_admin) {
      \Drupal::messenger()->addError('You do not have permission to reject appointments.');
      return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
    }

    if ($is_doctor && !$this->doctorOwnsAppointment($appointment_node, $current_user, $user_entity)) {
      \Drupal::messenger()->addError('You can only reject appointments with you.');
      return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
    }

    $status = $appointment_node->get('field_appointment_status')->value;
    if ($status !== 'pending') {
      \Drupal::messenger()->addError('This appointment cannot be rejected.');
      return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
    }

    $appointment_node->set('field_appointment_status', 'cancelled');
    $appointment_node->save();

    \Drupal::messenger()->addStatus('Appointment has been rejected successfully.');
    return new RedirectResponse(Url::fromRoute($redirect_route)->toString());
  }

  /**
   * Cancels an appointment.
   * 
   * @param int $appointment
   *   The appointment node ID.
   * 
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to my appointments page.
   */
  public function cancelAppointment($appointment) {
    
    // Load appointment
    $appointment_node = Node::load($appointment);
    
    if (!$appointment_node || $appointment_node->bundle() != 'appointment') {
      \Drupal::messenger()->addError('Invalid appointment.');
      return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
    }
    
    // Check if user owns this appointment (patients) or admin.
    $current_user = \Drupal::currentUser();
    $user_entity = \Drupal\user\Entity\User::load($current_user->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $current_user->hasPermission('administer site configuration') || $current_user->hasRole('administrator') || $current_user->hasRole('admin');
    $patient_id = $appointment_node->get('field_patient_ref')->target_id;

    if (!$is_admin && $patient_id != $current_user->id()) {
      \Drupal::messenger()->addError('You do not have permission to cancel this appointment.');
      return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
    }
    
    // Check if appointment can be cancelled
    $status = $appointment_node->get('field_appointment_status')->value;
    if (!in_array($status, ['pending', 'confirmed'])) {
      \Drupal::messenger()->addError('This appointment cannot be cancelled.');
      return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
    }
    
    // Cancel the appointment
    $appointment_node->set('field_appointment_status', 'cancelled');
    $appointment_node->save();
    
    \Drupal::messenger()->addStatus('Your appointment has been cancelled successfully.');
    
    return new RedirectResponse(Url::fromRoute('practo_core.my_appointments')->toString());
  }
}