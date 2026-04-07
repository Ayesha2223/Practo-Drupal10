<?php

namespace Drupal\practo_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides doctor filtering form.
 */
class DoctorFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'practo_doctor_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Get current filter values from URL
    $request = \Drupal::request();
    $current_specialization = $request->query->get('specialization');
    $current_city = $request->query->get('city');
    $current_name = $request->query->get('name');
    
    // Wrapper for horizontal layout
    $form['#attributes']['class'][] = 'doctor-filter-form-horizontal';
    
    $form['filters_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filters-row']],
    ];
    
    $form['filters_wrapper']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Doctor Name'),
      '#default_value' => $current_name,
      '#attributes' => [
        'placeholder' => 'Search by doctor name...',
        'class' => ['form-control'],
      ],
    ];
    
    $form['filters_wrapper']['specialization'] = [
      '#type' => 'select',
      '#title' => $this->t('Specialization'),
      '#options' => [
        '' => '- All Specializations -',
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
      ],
      '#default_value' => $current_specialization ?: '',
      '#attributes' => ['class' => ['form-control']],
    ];
    
    $form['filters_wrapper']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => $current_city,
      '#attributes' => [
        'placeholder' => 'Enter city name...',
        'class' => ['form-control'],
      ],
    ];
    
    $form['filters_wrapper']['actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['filter-actions']],
    ];
    
    $form['filters_wrapper']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => ['class' => ['btn', 'btn-primary']],
    ];
    
    $form['filters_wrapper']['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('practo_core.doctor_list'),
      '#attributes' => ['class' => ['btn', 'btn-secondary']],
    ];
    
    $form['#attached']['library'][] = 'practo_core/doctor-filter';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // Get form values
    $name = $form_state->getValue('name');
    $specialization = $form_state->getValue('specialization');
    $city = $form_state->getValue('city');
    
    // Build query parameters
    $query = [];
    if (!empty($name)) {
      $query['name'] = $name;
    }
    if (!empty($specialization)) {
      $query['specialization'] = $specialization;
    }
    if (!empty($city)) {
      $query['city'] = $city;
    }
    
    // Redirect with query parameters
    $form_state->setRedirect('practo_core.doctor_list', [], ['query' => $query]);
  }
}