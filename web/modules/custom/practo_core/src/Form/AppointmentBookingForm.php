<?php

namespace Drupal\practo_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides appointment booking form.
 * 
 * This is a custom form (not using node form) for better UX.
 */
class AppointmentBookingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'practo_appointment_booking_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $doctor = NULL) {
    
    // Enforce patient-only booking.
    $account = \Drupal::currentUser();
    $user_entity = User::load($account->id());
    $user_type = $user_entity ? $user_entity->get('field_user_type')->value : 'patient';
    $is_admin = ($user_type === 'admin') || $account->hasPermission('administer site configuration') || $account->hasRole('administrator') || $account->hasRole('admin');
    $is_doctor = ($user_type === 'doctor') || $account->hasRole('doctor');
    if ($is_doctor) {
      $dashboard_url = Url::fromRoute('practo_core.doctor_dashboard')->toString();
      return [
        '#markup' => '<div class="alert alert-info">This page is for patients. <a href="' . $dashboard_url . '">Go to Doctor Dashboard</a></div>',
      ];
    }
    if ($is_admin) {
      $dashboard_url = Url::fromRoute('practo_core.admin_dashboard')->toString();
      return [
        '#markup' => '<div class="alert alert-info">This page is for patients. <a href="' . $dashboard_url . '">Go to Admin Dashboard</a></div>',
      ];
    }

    // Load doctor
    $doctor_node = Node::load($doctor);
    
    if (!$doctor_node || $doctor_node->bundle() != 'doctor') {
      \Drupal::messenger()->addError('Invalid doctor selected.');
      return $form;
    }

    // Patients can only book verified doctors (published doctor nodes).
    if (!$doctor_node->isPublished()) {
      $doctors_url = Url::fromRoute('practo_core.doctor_list')->toString();
      return [
        '#markup' => '<div class="alert alert-warning">This doctor profile is pending verification. Please choose another verified doctor from the <a href="' . $doctors_url . '">Doctors list</a>.</div>',
      ];
    }
    
    // Attach styling library
    $form['#attached']['library'][] = 'practo_core/booking-form';
    $form['#prefix'] = '<div class="booking-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Store doctor ID in form
    $form['doctor_id'] = [
      '#type' => 'hidden',
      '#value' => $doctor,
    ];
    
    // Display doctor info
    $specialization = $doctor_node->get('field_specialization')->value ?? '';
    $consultation_fee = $doctor_node->get('field_consultation_fee')->value ?? 0;
    $clinic_name = $doctor_node->get('field_clinic_name')->value ?? '';
    
    $form['doctor_info'] = [
      '#type' => 'markup',
      '#markup' => '<div class="doctor-info-box">
        <h3>Dr. ' . $doctor_node->getTitle() . '</h3>
        <p><strong>Specialization:</strong> ' . $specialization . '</p>
        <p><strong>Consultation Fee:</strong> ₹' . number_format($consultation_fee, 2) . '</p>
        <p><strong>Clinic:</strong> ' . $clinic_name . '</p>
      </div>',
    ];
    
    // Get current user
    $current_user = \Drupal::currentUser();
    $user = \Drupal\user\Entity\User::load($current_user->id());
    $user_display_name = $user ? $user->getDisplayName() : '';
    
    // Patient details section
    $form['patient_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Patient Details'),
    ];
    
    $form['patient_details']['patient_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#default_value' => $user_display_name,
      '#attributes' => ['placeholder' => 'Enter patient full name'],
    ];
    
    $form['patient_details']['patient_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 120,
      '#attributes' =>['placeholder' => 'Enter age'],
    ];
    $form['patient_details']['patient_gender'] = [
  '#type' => 'select',
  '#title' => $this->t('Gender'),
  '#required' => TRUE,
  '#options' => [
    '' => '- Select -',
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other',
  ],
];
$form['patient_details']['contact_number'] = [
  '#type' => 'tel',
  '#title' => $this->t('Contact Number'),
  '#required' => TRUE,
  '#attributes' => [
    'placeholder' => '10-digit mobile number',
    'pattern' => '[0-9]{10}',
  ],
];
// Appointment details section
$form['appointment_details'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Appointment Details'),
];
$form['appointment_details']['appointment_date'] = [
  '#type' => 'date',
  '#title' => $this->t('Preferred Date'),
  '#required' => TRUE,
  '#date_date_format' => 'Y-m-d',
  '#attributes' => [
    'min' => date('Y-m-d', strtotime('+1 day')),
  ],
  '#description' => $this->t('Select a date (minimum 1 day in advance)'),
];

// Get available days for this doctor
$available_days = [];
if (!$doctor_node->get('field_available_days')->isEmpty()) {
  foreach ($doctor_node->get('field_available_days')->getValue() as $day) {
    $available_days[] = ucfirst($day['value']);
  }
}

$consultation_hours = $doctor_node->get('field_consultation_hours')->value ?? 'Not specified';

$form['appointment_details']['available_days_info'] = [
  '#type' => 'markup',
  '#markup' => '<div class="alert alert-info">
    <strong>Doctor Available On:</strong> ' . (empty($available_days) ? 'Not specified' : implode(', ', $available_days)) . '<br>
    <strong>Consultation Hours:</strong> ' . $consultation_hours . '
  </div>',
];

$form['appointment_details']['appointment_time'] = [
  '#type' => 'select',
  '#title' => $this->t('Preferred Time'),
  '#required' => TRUE,
  '#options' => [
    '' => '- Select Time -',
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
  ],
  '#ajax' => [
    'callback' => '::checkAvailability',
    'wrapper' => 'availability-message',
    'event' => 'change',
  ],
];

// Container for availability message
$form['appointment_details']['availability_message'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'availability-message'],
];

$form['appointment_details']['reason_visit'] = [
  '#type' => 'textarea',
  '#title' => $this->t('Reason for Visit / Symptoms'),
  '#required' => TRUE,
  '#rows' => 4,
  '#attributes' => ['placeholder' => 'Describe your health concern...'],
];

// Terms and conditions
$form['terms'] = [
  '#type' => 'checkbox',
  '#title' => $this->t('I agree to the terms and conditions'),
  '#required' => TRUE,
];

// Submit button
$form['actions'] = [
  '#type' => 'actions',
];

$form['actions']['submit'] = [
  '#type' => 'submit',
  '#value' => $this->t('Confirm Booking'),
  '#attributes' => ['class' => ['btn', 'btn-primary', 'btn-lg']],
];

$form['actions']['cancel'] = [
  '#type' => 'link',
  '#title' => $this->t('Cancel'),
  '#url' => Url::fromRoute('practo_core.doctor_detail', ['doctor' => $doctor]),
  '#attributes' => ['class' => ['btn', 'btn-secondary']],
];


return $form;

}
/**

AJAX callback to check time slot availability.
*/
public function checkAvailability(array &$form, FormStateInterface $form_state) {

$doctor_id = $form_state->getValue('doctor_id');
$date = $form_state->getValue('appointment_date');
$time = $form_state->getValue('appointment_time');

if ($date && $time) {
  // Check if slot is available
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'appointment')
    ->condition('field_doctor_ref', $doctor_id)
    ->condition('field_appointment_date', $date)
    ->condition('field_appointment_time', $time)
    ->condition('field_appointment_status', 'cancelled', '!=')
    ->accessCheck(FALSE);
  
  $existing = $query->execute();
  
  if (!empty($existing)) {
    $message = '<div class="alert alert-danger">
      ❌ This time slot is already booked. Please select another time.
    </div>';
  } else {
    $message = '<div class="alert alert-success">
      ✅ This time slot is available!
    </div>';
  }
  
  $form['appointment_details']['availability_message']['#markup'] = $message;
}

return $form['appointment_details']['availability_message'];
}
/**

{@inheritdoc}
*/
public function validateForm(array &$form, FormStateInterface $form_state) {

// Validate phone number
$phone = $form_state->getValue('contact_number');
if (!preg_match('/^[0-9]{10}$/', $phone)) {
  $form_state->setErrorByName('contact_number', 
    $this->t('Please enter a valid 10-digit phone number.'));
}

// Validate date is in future
$date = $form_state->getValue('appointment_date');
$selected_date = new \DateTime($date);
$tomorrow = new \DateTime('+1 day');
$tomorrow->setTime(0, 0, 0);

if ($selected_date < $tomorrow) {
  $form_state->setErrorByName('appointment_date', 
    $this->t('Appointment must be booked at least 1 day in advance.'));
}

// Validate time slot availability
$doctor_id = $form_state->getValue('doctor_id');
$time = $form_state->getValue('appointment_time');

$query = \Drupal::entityQuery('node')
  ->condition('type', 'appointment')
  ->condition('field_doctor_ref', $doctor_id)
  ->condition('field_appointment_date', $date)
  ->condition('field_appointment_time', $time)
  ->condition('field_appointment_status', 'cancelled', '!=')
  ->accessCheck(FALSE);

$existing = $query->execute();

if (!empty($existing)) {
  $form_state->setErrorByName('appointment_time', 
    $this->t('This time slot is no longer available. Please select another time.'));
}

// Validate selected day matches doctor's availability
$doctor = Node::load($doctor_id);
if ($doctor && !$doctor->get('field_available_days')->isEmpty()) {
  $available_days = [];
  foreach ($doctor->get('field_available_days')->getValue() as $day) {
    $available_days[] = $day['value'];
  }

  $day_of_week = strtolower($selected_date->format('l'));
  if (!in_array($day_of_week, $available_days)) {
    $form_state->setErrorByName('appointment_date', 
      $this->t('Doctor is not available on @day.', ['@day' => ucfirst($day_of_week)]));
  }
}
}
/**

*{@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {

// Get form values
$doctor_id = $form_state->getValue('doctor_id');
$current_user = \Drupal::currentUser();

// Generate booking ID
$booking_id = 'BK' . strtoupper(substr(uniqid(), -8));

// Create appointment node
$appointment = Node::create([
  'type' => 'appointment',
  'title' => 'Appointment - ' . $booking_id,
  'field_patient_ref' => ['target_id' => $current_user->id()],
  'field_doctor_ref' => ['target_id' => $doctor_id],
  'field_appointment_date' => $form_state->getValue('appointment_date'),
  'field_appointment_time' => $form_state->getValue('appointment_time'),
  'field_patient_name' => $form_state->getValue('patient_name'),
  'field_patient_age' => $form_state->getValue('patient_age'),
  'field_patient_gender' => $form_state->getValue('patient_gender'),
  'field_contact_number' => $form_state->getValue('contact_number'),
  'field_reason_visit' => $form_state->getValue('reason_visit'),
  'field_appointment_status' => 'pending',
  'field_booking_id' => $booking_id,
  'status' => 1,
]);

$appointment->save();

  // Success message
  \Drupal::messenger()->addStatus($this->t(
    'Appointment booked successfully! Your Booking ID is: <strong>@booking_id</strong>. 
  You can track the status from your dashboard.',
    ['@booking_id' => $booking_id]
  ));

// Redirect to my appointments
$form_state->setRedirect('practo_core.my_appointments');
}
}