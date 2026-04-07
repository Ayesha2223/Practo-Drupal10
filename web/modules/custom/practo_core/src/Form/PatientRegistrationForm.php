<?php

namespace Drupal\practo_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Simple patient registration form.
 */
class PatientRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'practo_patient_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['#prefix'] = '<div class="registration-form">';
    $form['#suffix'] = '</div>';
    // Attach styling library
    $form['#attached']['library'][] = 'practo_core/registration-forms';

    // Basic user info
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#description' => $this->t('This will be your username'),
      '#attributes' => ['autocomplete' => 'email'],
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#minlength' => 6,
      '#attributes' => ['autocomplete' => 'new-password'],
    ];

    $form['confirm_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm Password'),
      '#required' => TRUE,
      '#attributes' => ['autocomplete' => 'new-password'],
    ];

    // Patient basic info
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#attributes' => ['autocomplete' => 'name'],
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
      '#attributes' => ['autocomplete' => 'tel'],
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 120,
      '#attributes' => ['autocomplete' => 'off'],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register as Patient'),
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('practo_core.register_selection'),
      '#attributes' => ['class' => ['btn', 'btn-secondary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
    $email = $form_state->getValue('email');
    $password = $form_state->getValue('password');
    $confirm_password = $form_state->getValue('confirm_password');

    // Check if email already exists
    if (user_load_by_mail($email)) {
      $form_state->setErrorByName('email', $this->t('This email is already registered.'));
    }

    // Check password match
    if ($password !== $confirm_password) {
      $form_state->setErrorByName('confirm_password', $this->t('Passwords do not match.'));
    }

    // Validate phone number (basic 10-digit)
    $phone = $form_state->getValue('phone');
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
      $form_state->setErrorByName('phone', $this->t('Please enter a valid 10-digit phone number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $email = $form_state->getValue('email');
    $password = $form_state->getValue('password');
    $phone = $form_state->getValue('phone');
    
    try {
      $database = \Drupal::database();
      
      // Generate password hash
      $password_hasher = \Drupal::service('password');
      $hashed_password = $password_hasher->hash($password);
      
      // Get next user ID
      $max_uid = $database->query('SELECT MAX(uid) FROM {users}')->fetchField();
      $new_uid = $max_uid + 1;
      
      // Insert into users table (basic info only)
      $database->insert('users')
        ->fields([
          'uid' => $new_uid,
          'uuid' => \Drupal::service('uuid')->generate(),
          'langcode' => 'en',
        ])
        ->execute();
      
      // Insert into users_field_data table (actual user data)
      $database->insert('users_field_data')
        ->fields([
          'uid' => $new_uid,
          'langcode' => 'en',
          'preferred_langcode' => 'en',
          'name' => $email,
          'pass' => $hashed_password,
          'mail' => $email,
          'status' => 1,
          'created' => time(),
          'changed' => time(),
          'access' => 0,
          'login' => 0,
          'init' => $email,
          'default_langcode' => 1,
        ])
        ->execute();
      
      // Add user type field if it exists
      try {
        $field_storage = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('user');
        if (isset($field_storage['field_user_type'])) {
          $database->insert('user__field_user_type')
            ->fields([
              'bundle' => 'user',
              'deleted' => 0,
              'entity_id' => $new_uid,
              'revision_id' => $new_uid,
              'langcode' => 'en',
              'delta' => 0,
              'field_user_type_value' => 'patient',
            ])
            ->execute();
        }
      } catch (\Exception $e) {
        // Field doesn't exist, continue without it
      }
      
      // Load the user for login
      $user = \Drupal\user\Entity\User::load($new_uid);
      
      // Log in the user
      user_login_finalize($user);
      
      \Drupal::messenger()->addStatus($this->t('Registration successful! Welcome, @name!', ['@name' => $email]));
      
      // Redirect to dashboard
      $form_state->setRedirect('practo_core.dashboard');
      
    } catch (\Exception $e) {
      \Drupal::logger('practo_core')->error('Patient registration failed: @message', ['@message' => $e->getMessage()]);
      \Drupal::messenger()->addError($this->t('Registration failed. Please try again.'));
    }
  }

}
