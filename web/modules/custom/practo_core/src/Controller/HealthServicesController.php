<?php

namespace Drupal\practo_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Controller for health services pages.
 */
class HealthServicesController extends ControllerBase {

  /**
   * Health checkup packages page.
   */
  public function healthCheckups() {
    
    $packages = [
      [
        'id' => 1,
        'name' => 'Full Body Checkup',
        'tests' => 98,
        'price' => 1999,
        'original_price' => 3999,
        'discount' => 50,
        'description' => 'Comprehensive health screening covering all major body systems',
        'includes' => [
          'Complete Blood Count',
          'Lipid Profile',
          'Liver Function Test',
          'Kidney Function Test',
          'Thyroid Profile',
          'Blood Sugar Tests',
          'Vitamin D & B12',
          'And 91 more tests',
        ],
      ],
      [
        'id' => 2,
        'name' => 'Diabetes Screening',
        'tests' => 45,
        'price' => 899,
        'original_price' => 1299,
        'discount' => 30,
        'description' => 'Essential tests for diabetes detection and monitoring',
        'includes' => [
          'HbA1c Test',
          'Fasting Blood Sugar',
          'Post Prandial Blood Sugar',
          'Insulin Test',
          'Kidney Function Test',
          'And 40 more tests',
        ],
      ],
      [
        'id' => 3,
        'name' => 'Heart Health Checkup',
        'tests' => 62,
        'price' => 1499,
        'original_price' => 2499,
        'discount' => 40,
        'description' => 'Complete cardiac screening and risk assessment',
        'includes' => [
          'ECG',
          'Lipid Profile',
          'Cardiac Markers',
          'Blood Pressure Monitoring',
          'Stress Test',
          'And 57 more tests',
        ],
      ],
      [
        'id' => 4,
        'name' => 'Women Wellness',
        'tests' => 78,
        'price' => 1799,
        'original_price' => 3199,
        'discount' => 45,
        'description' => 'Specialized health screening for women',
        'includes' => [
          'Thyroid Profile',
          'Hormone Tests',
          'Bone Density Scan',
          'Breast Cancer Screening',
          'Gynecological Tests',
          'And 73 more tests',
        ],
      ],
      [
        'id' => 5,
        'name' => 'Senior Citizen Package',
        'tests' => 85,
        'price' => 2199,
        'original_price' => 3999,
        'discount' => 45,
        'description' => 'Comprehensive health package for seniors (60+ years)',
        'includes' => [
          'Complete Blood Count',
          'Heart Function Tests',
          'Kidney & Liver Tests',
          'Bone Health Tests',
          'Cancer Screening',
          'And 80 more tests',
        ],
      ],
      [
        'id' => 6,
        'name' => 'Fever Panel',
        'tests' => 25,
        'price' => 599,
        'original_price' => 999,
        'discount' => 40,
        'description' => 'Quick fever diagnosis panel',
        'includes' => [
          'Complete Blood Count',
          'Dengue Test',
          'Malaria Test',
          'Typhoid Test',
          'And 21 more tests',
        ],
      ],
    ];
    
    $build = [
      '#theme' => 'health_checkups_page',
      '#packages' => $packages,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Consult online page.
   */
  public function consultOnline() {
    
    // Get online doctors (doctors available for online consultation)
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'doctor')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 12)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    $doctors = [];
    
    if (!empty($nids)) {
      $doctor_nodes = Node::loadMultiple($nids);
      
      foreach ($doctor_nodes as $doctor) {
        $doctors[] = [
          'id' => $doctor->id(),
          'name' => $doctor->getTitle(),
          'specialization' => $doctor->get('field_specialization')->value,
          'qualification' => $doctor->get('field_qualification')->value,
          'experience' => $doctor->get('field_experience_years')->value,
          'fee' => $doctor->get('field_consultation_fee')->value,
          'online_available' => TRUE,
        ];
      }
    }
    
    $build = [
      '#theme' => 'consult_online_page',
      '#doctors' => $doctors,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Find hospitals page.
   */
  public function findHospitals() {
    
    $hospitals = [
      [
        'id' => 1,
        'name' => 'Apollo Hospitals',
        'location' => 'Jubilee Hills, Hyderabad',
        'specialties' => ['Cardiology', 'Neurology', 'Oncology'],
        'beds' => 500,
        'rating' => 4.5,
      ],
      [
        'id' => 2,
        'name' => 'Care Hospitals',
        'location' => 'Banjara Hills, Hyderabad',
        'specialties' => ['Orthopedics', 'Gastroenterology', 'Urology'],
        'beds' => 300,
        'rating' => 4.3,
      ],
      [
        'id' => 3,
        'name' => 'Rainbow Childrens Hospital',
        'location' => 'Ameerpet, Hyderabad',
        'specialties' => ['Pediatrics', 'Neonatology'],
        'beds' => 200,
        'rating' => 4.7,
      ],
      [
        'id' => 4,
        'name' => 'Continental Hospitals',
        'location' => 'Gachibowli, Hyderabad',
        'specialties' => ['Heart Surgery', 'Neurosurgery'],
        'beds' => 400,
        'rating' => 4.6,
      ],
    ];
    
    $build = [
      '#theme' => 'find_hospitals_page',
      '#hospitals' => $hospitals,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Order medicines page.
   */
  public function medicines() {
    
    $medicine_categories = [
      [
        'name' => 'Fever & Pain',
        'icon' => 'thermometer-half',
        'medicines_count' => 245,
      ],
      [
        'name' => 'Diabetes Care',
        'icon' => 'syringe',
        'medicines_count' => 180,
      ],
      [
        'name' => 'Heart Care',
        'icon' => 'heartbeat',
        'medicines_count' => 156,
      ],
      [
        'name' => 'Vitamins & Supplements',
        'icon' => 'capsules',
        'medicines_count' => 320,
      ],
      [
        'name' => 'Skin Care',
        'icon' => 'hand-sparkles',
        'medicines_count' => 198,
      ],
      [
        'name' => 'Baby Care',
        'icon' => 'baby',
        'medicines_count' => 210,
      ],
    ];
    
    $build = [
      '#theme' => 'medicines_page',
      '#categories' => $medicine_categories,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Lab tests page.
   */
  public function labTests() {
    
    $popular_tests = [
      [
        'name' => 'Complete Blood Count (CBC)',
        'price' => 299,
        'description' => 'Measures different components of blood',
        'sample' => 'Blood',
        'reports_in' => '24 hours',
      ],
      [
        'name' => 'Lipid Profile',
        'price' => 399,
        'description' => 'Measures cholesterol levels',
        'sample' => 'Blood',
        'reports_in' => '24 hours',
      ],
      [
        'name' => 'Liver Function Test (LFT)',
        'price' => 449,
        'description' => 'Checks liver health',
        'sample' => 'Blood',
        'reports_in' => '24 hours',
      ],
      [
        'name' => 'Kidney Function Test (KFT)',
        'price' => 499,
        'description' => 'Evaluates kidney performance',
        'sample' => 'Blood',
        'reports_in' => '24 hours',
      ],
      [
        'name' => 'Thyroid Profile',
        'price' => 549,
        'description' => 'Checks thyroid hormone levels',
        'sample' => 'Blood',
        'reports_in' => '48 hours',
      ],
      [
        'name' => 'HbA1c Test',
        'price' => 399,
        'description' => 'Diabetes monitoring test',
        'sample' => 'Blood',
        'reports_in' => '24 hours',
      ],
    ];
    
    $build = [
      '#theme' => 'lab_tests_page',
      '#tests' => $popular_tests,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Health articles listing page.
   */
  public function healthArticles() {
    
    $articles = [
      [
        'id' => 1,
        'title' => '10 Tips for Better Sleep',
        'category' => 'Wellness',
        'author' => 'Dr. Priya Sharma',
        'date' => 'Dec 10, 2024',
        'excerpt' => 'Discover proven strategies to improve your sleep quality and wake up refreshed every morning.',
        'read_time' => '5 min read',
      ],
      [
        'id' => 2,
        'title' => 'Understanding Diabetes: A Complete Guide',
        'category' => 'Health Conditions',
        'author' => 'Dr. Rajesh Kumar',
        'date' => 'Dec 8, 2024',
        'excerpt' => 'Learn everything about diabetes management, symptoms, and prevention strategies.',
        'read_time' => '8 min read',
      ],
      [
        'id' => 3,
        'title' => 'Healthy Diet Plans for Weight Loss',
        'category' => 'Nutrition',
        'author' => 'Dr. Anita Reddy',
        'date' => 'Dec 5, 2024',
        'excerpt' => 'Expert-recommended diet plans for sustainable and healthy weight loss.',
        'read_time' => '6 min read',
      ],
      [
        'id' => 4,
        'title' => 'Heart Health: Prevention is Key',
        'category' => 'Cardiology',
        'author' => 'Dr. Anil Kumar',
        'date' => 'Dec 3, 2024',
        'excerpt' => 'Simple lifestyle changes to keep your heart healthy and strong.',
        'read_time' => '7 min read',
      ],
      [
        'id' => 5,
        'title' => 'Mental Health Matters',
        'category' => 'Mental Wellness',
        'author' => 'Dr. Meera Shah',
        'date' => 'Dec 1, 2024',
        'excerpt' => 'Understanding the importance of mental health and how to maintain it.',
        'read_time' => '6 min read',
      ],
      [
        'id' => 6,
        'title' => 'Yoga for Beginners',
        'category' => 'Fitness',
        'author' => 'Dr. Suresh Nair',
        'date' => 'Nov 28, 2024',
        'excerpt' => 'Get started with yoga - poses, benefits, and tips for beginners.',
        'read_time' => '5 min read',
      ],
    ];
    
    $build = [
      '#theme' => 'health_articles_page',
      '#articles' => $articles,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * Book health package page.
   */
  public function bookPackage($package_id) {
    
    $packages_data = [
      1 => ['name' => 'Full Body Checkup', 'price' => 1999, 'tests' => 98],
      2 => ['name' => 'Diabetes Screening', 'price' => 899, 'tests' => 45],
      3 => ['name' => 'Heart Health Checkup', 'price' => 1499, 'tests' => 62],
      4 => ['name' => 'Women Wellness', 'price' => 1799, 'tests' => 78],
      5 => ['name' => 'Senior Citizen Package', 'price' => 2199, 'tests' => 85],
      6 => ['name' => 'Fever Panel', 'price' => 599, 'tests' => 25],
    ];
    
    $package = $packages_data[$package_id] ?? $packages_data[1];
    
    $build = [
      '#theme' => 'book_package_page',
      '#package_id' => $package_id,
      '#package' => $package,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }

  /**
   * View individual article page.
   */
  public function viewArticle($article_id) {
    
    $articles_data = [
      1 => [
        'title' => '10 Tips for Better Sleep',
        'category' => 'Wellness',
        'author' => 'Dr. Priya Sharma',
        'date' => 'Dec 10, 2024',
        'content' => '<p>Getting quality sleep is essential for overall health and wellbeing. Here are 10 proven tips to improve your sleep...</p>
          <h3>1. Maintain a Consistent Sleep Schedule</h3>
          <p>Go to bed and wake up at the same time every day, even on weekends.</p>
          <h3>2. Create a Relaxing Bedtime Routine</h3>
          <p>Develop calming pre-sleep rituals like reading or meditation.</p>
          <h3>3. Optimize Your Sleep Environment</h3>
          <p>Keep your bedroom cool, dark, and quiet for better sleep quality.</p>',
      ],
      2 => [
        'title' => 'Understanding Diabetes: A Complete Guide',
        'category' => 'Health Conditions',
        'author' => 'Dr. Rajesh Kumar',
        'date' => 'Dec 8, 2024',
        'content' => '<p>Diabetes is a chronic condition that affects how your body processes blood sugar...</p>',
      ],
    ];
    
    $article = $articles_data[$article_id] ?? $articles_data[1];
    
    $build = [
      '#theme' => 'view_article_page',
      '#article' => $article,
      '#article_id' => $article_id,
      '#attached' => [
        'library' => ['practo_core/health-services-page'],
      ],
    ];
    
    return $build;
  }
}