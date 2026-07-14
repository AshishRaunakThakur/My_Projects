/**
 * Ashmeera Empowers - Main JavaScript
 */

// ============================================
// Loading Screen
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Hide loading screen
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.classList.add('hidden');
        }, 1000);
    }
    
    // Navbar scroll effect
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-custom';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
});

// ============================================
// Premium Toast Notification System
// ============================================

function showToast(message, type = 'info', title = '', duration = 2000) {
    // Remove existing toasts of same type (optional)
    const existingToasts = document.querySelectorAll('.toast-bubble');
    if (existingToasts.length >= 3) {
        const oldest = existingToasts[0];
        oldest.classList.add('hiding');
        setTimeout(() => oldest.remove(), 400);
    }

    // Create container if not exists
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Toast icons mapping
    const icons = {
        success: 'fa-check-circle',
        danger: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    // Toast titles mapping
    const defaultTitles = {
        success: '🎉 Success!',
        danger: '❌ Error!',
        warning: '⚠️ Warning!',
        info: 'ℹ️ Information'
    };

    const finalTitle = title || defaultTitles[type] || 'Notification';
    const iconClass = icons[type] || icons.info;

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-bubble toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${iconClass}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-title">${finalTitle}</p>
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" onclick="closeToast(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add to container
    container.appendChild(toast);

    // Auto hide after duration (2 seconds)
    const timeout = setTimeout(() => {
        closeToast(toast);
    }, duration);

    // Store timeout on element for cleanup
    toast._timeout = timeout;

    // Click to dismiss
    toast.addEventListener('click', function(e) {
        if (e.target.closest('.toast-close') || e.target.closest('.toast-icon')) {
            return;
        }
        closeToast(this);
    });

    // Return toast reference for manual control
    return toast;
}

// Close toast with animation
function closeToast(toast) {
    if (toast._closing) return;
    toast._closing = true;

    // Clear auto-hide timeout
    if (toast._timeout) {
        clearTimeout(toast._timeout);
        toast._timeout = null;
    }

    toast.classList.add('hiding');
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 400);
}

// Close toast by element
window.closeToast = closeToast;

// Export for global use
window.showToast = showToast;

// ============================================
// Form Validation
// ============================================

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
        
        // Password validation
        if (input.type === 'password' && input.value) {
            if (input.value.length < 8) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// ============================================
// File Upload Preview
// ============================================

function previewFile(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        if (file.type.startsWith('image/')) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        } else {
            preview.innerHTML = `
                <div class="file-preview">
                    <i class="fas fa-file-pdf"></i>
                    <span>${file.name}</span>
                </div>
            `;
        }
    };
    reader.readAsDataURL(file);
}

// ============================================
// Search with Debounce
// ============================================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// AJAX Form Submit
// ============================================

function submitFormAjax(formId, url, method = 'POST', successCallback, errorCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
            showToast(data.message || 'Success!', 'success');
        } else {
            if (errorCallback) errorCallback(data);
            showToast(data.message || 'Something went wrong.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
        if (errorCallback) errorCallback(error);
    });
}

// ============================================
// Infinite Scroll (for jobs listing)
// ============================================

let isLoading = false;

function initInfiniteScroll(containerId, loadMoreUrl, pageParam = 'page') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let page = 1;
    
    window.addEventListener('scroll', debounce(function() {
        if (isLoading) return;
        
        const rect = container.getBoundingClientRect();
        const isVisible = rect.bottom <= window.innerHeight + 200;
        
        if (isVisible) {
            loadMoreItems(container, loadMoreUrl, pageParam, ++page);
        }
    }, 200));
}

function loadMoreItems(container, url, pageParam, page) {
    isLoading = true;
    
    fetch(`${url}?${pageParam}=${page}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.html) {
            container.insertAdjacentHTML('beforeend', data.html);
        }
        if (!data.has_more) {
            // Remove scroll listener if no more items
            window.removeEventListener('scroll', loadMoreItems);
        }
        isLoading = false;
    })
    .catch(error => {
        console.error('Error loading more items:', error);
        isLoading = false;
    });
}

// ============================================
// Job Apply (AJAX)
// ============================================

function applyForJob(jobId) {
    if (!confirm('Are you sure you want to apply for this job?')) return;
    
    const formData = new FormData();
    formData.append('job_id', jobId);
    
    fetch(`/user/job/${jobId}/apply`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            document.querySelector('.apply-btn').disabled = true;
            document.querySelector('.apply-btn').innerHTML = '<i class="fas fa-check"></i> Applied';
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
    });
}

// ============================================
// Save/Unsave Job (AJAX)
// ============================================

function toggleSaveJob(jobId, button) {
    fetch(`/user/job/${jobId}/save`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `job_id=${jobId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.saved) {
            button.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
            button.classList.add('saved');
            showToast('Job saved successfully!', 'success');
        } else {
            button.innerHTML = '<i class="far fa-bookmark"></i> Save';
            button.classList.remove('saved');
            showToast('Job removed from saved.', 'info');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
    });
}

// ============================================
// Update Application Status (Employer)
// ============================================

function updateApplicationStatus(appId, status, button) {
    if (!confirm(`Are you sure you want to ${status} this application?`)) return;
    
    const formData = new FormData();
    formData.append('status', status);
    
    fetch(`/employer/application/${appId}/status`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Update UI
            const statusBadge = button.closest('.application-item').querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                statusBadge.className = `status-badge status-${status}`;
            }
            // Update buttons
            const actionButtons = button.closest('.action-buttons');
            if (actionButtons) {
                actionButtons.innerHTML = `
                    <span class="text-success"><i class="fas fa-check"></i> Updated</span>
                `;
            }
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
    });
}

// ============================================
// Toggle Job Status (Employer)
// ============================================

function toggleJobStatus(jobId, button) {
    fetch(`/employer/job/${jobId}/toggle`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `job_id=${jobId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const statusBadge = button.closest('.job-item').querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = data.is_active ? 'Active' : 'Inactive';
                statusBadge.className = `status-badge ${data.is_active ? 'status-active' : 'status-inactive'}`;
            }
            button.textContent = data.is_active ? 'Deactivate' : 'Activate';
            button.className = `btn btn-sm ${data.is_active ? 'btn-warning' : 'btn-success'}`;
            showToast(data.message, 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
    });
}

// ============================================
// Delete Item with Confirmation
// ============================================

function deleteItem(url, itemName, callback) {
    if (!confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) return;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Deleted successfully.', 'success');
            if (callback) callback();
        } else {
            showToast(data.message || 'Failed to delete.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'danger');
    });
}

// ============================================
// Copy to Clipboard
// ============================================

function copyToClipboard(text, message = 'Copied to clipboard!') {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showToast(message, 'success');
            })
            .catch(() => {
                fallbackCopy(text);
            });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('Copied to clipboard!', 'success');
}

// ============================================
// Password Strength Indicator
// ============================================

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    const strengthLevels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors = ['#EF4444', '#F59E0B', '#FBBF24', '#34D399', '#10B981'];
    
    return {
        score: strength,
        level: strengthLevels[strength] || strengthLevels[0],
        color: strengthColors[strength] || strengthColors[0],
        percentage: (strength / 5) * 100
    };
}

// ============================================
// Filter Tags (for job search)
// ============================================

function addFilterTag(tagText, filterId) {
    const container = document.getElementById(filterId);
    if (!container) return;
    
    const tag = document.createElement('span');
    tag.className = 'filter-tag';
    tag.innerHTML = `
        ${tagText}
        <button type="button" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(tag);
}

// ============================================
// Export functions for global use
// ============================================

window.showToast = showToast;
window.closeToast = closeToast;
window.validateForm = validateForm;
window.previewFile = previewFile;
window.applyForJob = applyForJob;
window.toggleSaveJob = toggleSaveJob;
window.updateApplicationStatus = updateApplicationStatus;
window.toggleJobStatus = toggleJobStatus;
window.deleteItem = deleteItem;
window.copyToClipboard = copyToClipboard;
window.checkPasswordStrength = checkPasswordStrength;
window.addFilterTag = addFilterTag;
window.submitFormAjax = submitFormAjax;