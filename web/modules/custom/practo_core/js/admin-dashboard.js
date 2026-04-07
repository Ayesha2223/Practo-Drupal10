/**
 * Admin Dashboard JavaScript
 * Provides interactive functionality for CRUD operations
 */

(function ($, Drupal) {
  
  'use strict';

  Drupal.behaviors.practoAdminDashboard = {
    attach: function (context, settings) {
      
      // Initialize dashboard functionality
      once('admin-dashboard-init', 'body', context).forEach(function () {
        initDashboard();
        initCRUDOperations();
        initAjaxOperations();
        initConfirmations();
      });
      
    }
  };

  /**
   * Initialize dashboard components
   */
  function initDashboard() {
    
    // Animate statistics cards on load
    $('.stat-card').each(function(index) {
      const $card = $(this);
      setTimeout(function() {
        $card.addClass('animated');
      }, index * 100);
    });
    
    // Add click handlers for quick action buttons
    $('.action-btn').on('click', function(e) {
      const $btn = $(this);
      
      // Add loading state
      $btn.addClass('loading');
      
      // Remove loading state after navigation
      setTimeout(function() {
        $btn.removeClass('loading');
      }, 1000);
    });
    
    // Management card hover effects
    $('.management-card').on('mouseenter', function() {
      $(this).find('i').addClass('fa-beat');
    }).on('mouseleave', function() {
      $(this).find('i').removeClass('fa-beat');
    });
    
  }

  /**
   * Initialize CRUD operations
   */
  function initCRUDOperations() {
    
    // View button handlers
    $('.action-btn-small.view').on('click', function(e) {
      const $btn = $(this);
      const url = $btn.attr('href');
      
      // Add loading state
      $btn.addClass('loading');
      
      // Log view action
      console.log('Viewing record:', url);
    });
    
    // Edit button handlers
    $('.action-btn-small.edit').on('click', function(e) {
      const $btn = $(this);
      const url = $btn.attr('href');
      
      // Add loading state
      $btn.addClass('loading');
      
      // Log edit action
      console.log('Editing record:', url);
    });
    
    // Delete button handlers
    $('.action-btn-small.delete').on('click', function(e) {
      const $btn = $(this);
      const url = $btn.attr('href');
      
      // Log delete action
      console.log('Deleting record:', url);
    });
    
    // Add new record buttons
    $('.btn-success').on('click', function(e) {
      const $btn = $(this);
      
      // Add loading animation
      $btn.addClass('loading');
      
      // Show success message
      Drupal.behaviors.practoAdminDashboard.showMessage('Opening form...', 'info');
    });
    
  }

  /**
   * Initialize AJAX operations for dynamic updates
   */
  function initAjaxOperations() {
    
    // Auto-refresh statistics every 30 seconds
    setInterval(function() {
      refreshStatistics();
    }, 30000);
    
    // Initialize status change handlers
    $('.status-badge').on('click', function() {
      const $badge = $(this);
      const currentStatus = $badge.text().trim();
      
      // Show status change dialog
      if (confirm('Change status for this record?')) {
        changeRecordStatus($badge, currentStatus);
      }
    });
    
  }

  /**
   * Initialize confirmation dialogs
   */
  function initConfirmations() {
    
    // Enhanced delete confirmations
    $('.action-btn-small.delete').on('click', function(e) {
      const $btn = $(this);
      const recordName = $btn.closest('tr').find('td:first-child strong').text();
      
      if (!confirm(`Are you sure you want to delete "${recordName}"? This action cannot be undone.`)) {
        e.preventDefault();
        return false;
      }
    });
    
    // Form submission confirmations
    $('form').on('submit', function(e) {
      const $form = $(this);
      
      if ($form.hasClass('node-form')) {
        const isEdit = $form.find('input[name="nid"]').length > 0;
        const action = isEdit ? 'update' : 'create';
        
        if (!confirm(`Are you sure you want to ${action} this record?`)) {
          e.preventDefault();
          return false;
        }
      }
    });
    
  }

  /**
   * Refresh dashboard statistics via AJAX
   */
  function refreshStatistics() {
    
    $.ajax({
      url: '/admin/practo/dashboard/stats',
      method: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data.stats) {
          updateStatCards(data.stats);
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to refresh statistics:', error);
      }
    });
    
  }

  /**
   * Update statistics cards with new data
   */
  function updateStatCards(stats) {
    
    $('.stat-card').each(function() {
      const $card = $(this);
      const $value = $card.find('h3');
      const statType = getStatType($card);
      
      if (stats[statType] !== undefined) {
        const oldValue = parseInt($value.text());
        const newValue = parseInt(stats[statType]);
        
        if (oldValue !== newValue) {
          // Animate the change
          $value.addClass('updating');
          
          setTimeout(function() {
            $value.text(newValue);
            $value.removeClass('updating').addClass('updated');
            
            setTimeout(function() {
              $value.removeClass('updated');
            }, 1000);
          }, 300);
        }
      }
    });
    
  }

  /**
   * Get statistic type from card
   */
  function getStatType($card) {
    const text = $card.find('p').text().toLowerCase();
    
    if (text.includes('doctor')) return 'total_doctors';
    if (text.includes('appointment')) return 'total_appointments';
    if (text.includes('pending')) return 'pending_appointments';
    if (text.includes('confirmed')) return 'confirmed_appointments';
    if (text.includes('package')) return 'total_packages';
    if (text.includes('article')) return 'total_articles';
    if (text.includes('user')) return 'total_users';
    
    return null;
  }

  /**
   * Change record status via AJAX
   */
  function changeRecordStatus($badge, currentStatus) {
    
    const $row = $badge.closest('tr');
    const recordId = $row.find('td:first-child').text().replace('#', '');
    const recordType = getRecordType($row);
    
    $.ajax({
      url: `/admin/practo/${recordType}/${recordId}/status`,
      method: 'POST',
      data: {
        status: currentStatus === 'Active' ? 'inactive' : 'active'
      },
      success: function(data) {
        if (data.success) {
          // Update badge
          $badge.text(data.new_status);
          $badge.removeClass('active inactive').addClass(data.new_status.toLowerCase());
          
          // Show success message
          Drupal.behaviors.practoAdminDashboard.showMessage('Status updated successfully!', 'success');
        }
      },
      error: function(xhr, status, error) {
        console.error('Failed to update status:', error);
        Drupal.behaviors.practoAdminDashboard.showMessage('Failed to update status', 'error');
      }
    });
    
  }

  /**
   * Get record type from table context
   */
  function getRecordType($row) {
    const pageClass = $('body').attr('class');
    
    if (pageClass.includes('doctors')) return 'doctors';
    if (pageClass.includes('appointments')) return 'appointments';
    if (pageClass.includes('packages')) return 'packages';
    if (pageClass.includes('articles')) return 'articles';
    
    return 'unknown';
  }

  /**
   * Show message to user
   */
  Drupal.behaviors.practoAdminDashboard.showMessage = function(message, type) {
    
    // Remove existing messages
    $('.admin-dashboard-message').remove();
    
    // Create message element
    const $message = $(`
      <div class="admin-dashboard-message ${type}">
        <i class="fas fa-${getMessageIcon(type)}"></i>
        <span>${message}</span>
      </div>
    `);
    
    // Add to page
    $('body').prepend($message);
    
    // Auto-hide after 3 seconds
    setTimeout(function() {
      $message.addClass('fade-out');
      setTimeout(function() {
        $message.remove();
      }, 300);
    }, 3000);
    
  };

  /**
   * Get message icon based on type
   */
  function getMessageIcon(type) {
    switch(type) {
      case 'success': return 'check-circle';
      case 'error': return 'exclamation-circle';
      case 'warning': return 'exclamation-triangle';
      case 'info': return 'info-circle';
      default: return 'info-circle';
    }
  }

  /**
   * Initialize table sorting
   */
  function initTableSorting() {
    
    $('.crud-table thead th').on('click', function() {
      const $th = $(this);
      const $table = $th.closest('.crud-table');
      const columnIndex = $th.index();
      const isAsc = $th.hasClass('sort-asc');
      
      // Remove sort classes from all headers
      $table.find('thead th').removeClass('sort-asc sort-desc');
      
      // Add sort class to clicked header
      $th.addClass(isAsc ? 'sort-desc' : 'sort-asc');
      
      // Sort table
      sortTable($table, columnIndex, !isAsc);
    });
    
  }

  /**
   * Sort table by column
   */
  function sortTable($table, columnIndex, asc) {
    
    const $tbody = $table.find('tbody');
    const $rows = $tbody.find('tr');
    
    $rows.sort(function(a, b) {
      const aVal = $(a).find('td').eq(columnIndex).text();
      const bVal = $(b).find('td').eq(columnIndex).text();
      
      if (asc) {
        return aVal.localeCompare(bVal);
      } else {
        return bVal.localeCompare(aVal);
      }
    });
    
    $tbody.empty().append($rows);
    
  }

  // Initialize table sorting on page load
  $(document).ready(function() {
    initTableSorting();
  });

})(jQuery, Drupal);
