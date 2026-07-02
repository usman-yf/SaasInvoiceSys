<?php
// includes/footer.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php if (isLoggedIn() && $current_page != 'login.php' && $current_page != 'verify.php'): ?>
        </main> <!-- End Main Page Content -->
    </div> <!-- End Main Content wrapper -->
<?php endif; ?>

<?php
// Global Toast Logic
$toast_msg = '';
$toast_type = 'success';
$toast_icon = 'check-circle';

// Check for generic error message
if (isset($_GET['err'])) {
    $toast_msg = htmlspecialchars($_GET['err']);
    $toast_type = 'danger';
    $toast_icon = 'alert-triangle';
}

if (isset($_GET['msg'])) {
    $toast_msg_raw = htmlspecialchars($_GET['msg']);
    switch ($toast_msg_raw) {
        case 'added':
        case 'created':
            $toast_msg = 'Record successfully created.';
            break;
        case 'updated':
            $toast_msg = 'Record successfully updated.';
            break;
        case 'deleted':
            $toast_msg = 'Record successfully deleted.';
            break;
        case 'emailed':
            $targetEmail = isset($_GET['to']) ? htmlspecialchars($_GET['to']) : 'the customer';
            $toast_msg = "Invoice successfully sent to <strong>{$targetEmail}</strong>!";
            break;
        case 'failed':
            $targetEmail = isset($_GET['to']) ? htmlspecialchars($_GET['to']) : 'the customer';
            $toast_msg = "Failed to send the invoice email to <strong>{$targetEmail}</strong>. Please try again.";
            $toast_type = 'danger';
            $toast_icon = 'alert-triangle';
            break;
        default:
            $toast_msg = $toast_msg_raw;
            break;
    }
}

// Fallback to locally scoped page variables if 'msg' was not explicitly passed via GET
if (isset($success) && !empty($success)) {
    $toast_msg = $success;
    $toast_type = 'success';
    $toast_icon = 'check-circle';
}

if (isset($error) && !empty($error)) {
    $toast_msg = $error;
    $toast_type = 'danger';
    $toast_icon = 'alert-triangle';
}

$toast_position_pref = isset($conn) ? get_setting($conn, 'toast_position', 'top-right') : 'top-right';
$toast_duration_pref = isset($conn) ? (int)get_setting($conn, 'toast_duration', '3000') : 3000;

$toast_position_class = 'top-6 right-6';
$toast_anim_in = 'translate-x-full';

switch($toast_position_pref) {
    case 'top-left':
        $toast_position_class = 'top-6 left-6';
        $toast_anim_in = '-translate-x-full';
        break;
    case 'top-center':
        $toast_position_class = 'top-6 left-1/2 -translate-x-1/2';
        $toast_anim_in = '-translate-y-full';
        break;
    case 'bottom-right':
        $toast_position_class = 'bottom-6 right-6';
        $toast_anim_in = 'translate-x-full';
        break;
    case 'bottom-left':
        $toast_position_class = 'bottom-6 left-6';
        $toast_anim_in = '-translate-x-full';
        break;
    case 'bottom-center':
        $toast_position_class = 'bottom-6 left-1/2 -translate-x-1/2';
        $toast_anim_in = 'translate-y-full';
        break;
    default:
        $toast_position_class = 'top-6 right-6';
        $toast_anim_in = 'translate-x-full';
        break;
}
?>
<?php if ($toast_msg): ?>
    <!-- Premium Toast Notification -->
    <div id="globalToast" class="fixed <?= $toast_position_class ?> z-50 transform transition-all duration-300 <?= $toast_anim_in ?> opacity-0">
        <div class="bg-white rounded-2xl shadow-float border border-gray-100 p-4 min-w-[320px] max-w-sm flex items-start space-x-4 overflow-hidden relative group">
            <!-- Icon -->
            <div class="flex-shrink-0 mt-0.5">
                <?php if ($toast_type === 'success'): ?>
                    <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    </div>
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Content -->
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-gray-900"><?= $toast_type === 'success' ? 'Success' : 'Error' ?></h3>
                <p class="text-sm text-gray-500 mt-1"><?= $toast_msg ?></p>
            </div>
            
            <!-- Close -->
            <button onclick="hideToast()" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none flex-shrink-0">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <!-- Progress Bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-gray-100 w-full">
                <div id="toastProgressBar" class="h-full <?= $toast_type === 'success' ? 'bg-green-500' : 'bg-red-500' ?> transition-all duration-[4000ms] ease-linear" style="width: 100%;"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Premium Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 z-[100] hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity opacity-0" id="deleteModalBackdrop"></div>
    
    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-float w-full max-w-md transform scale-95 opacity-0 transition-all duration-300" id="deleteModalContent">
            <div class="p-6 sm:p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-red-50 mx-auto flex items-center justify-center mb-6">
                    <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Delete Record</h2>
                <p class="text-gray-500 mb-8" id="deleteModalMessage">Are you sure you want to permanently delete this? This action cannot be undone.</p>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-white border border-gray-200 text-gray-700 font-medium py-2.5 px-4 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors">
                        Cancel
                    </button>
                    <a href="#" id="deleteModalConfirmBtn" class="flex-1 bg-red-600 text-white font-medium py-2.5 px-4 rounded-xl hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                        Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (Keep for compatibility with custom JS like ajax/invoice.js if needed, but remove Bootstrap JS) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Initialize Lucide Icons -->
<script>
    lucide.createIcons();
    
    // Toast Logic
    <?php if ($toast_msg): ?>
    document.addEventListener('DOMContentLoaded', () => {
        const toast = document.getElementById('globalToast');
        const progress = document.getElementById('toastProgressBar');
        
        // Show toast
        setTimeout(() => {
            toast.classList.remove('<?= $toast_anim_in ?>', 'opacity-0');
            progress.style.width = '0%';
        }, 100);
        
        // Hide toast
        setTimeout(() => {
            hideToast();
        }, <?= $toast_duration_pref ?>);
    });
    <?php endif; ?>

    function hideToast() {
        const toast = document.getElementById('globalToast');
        if (toast) {
            toast.classList.add('<?= $toast_anim_in ?>', 'opacity-0');
            setTimeout(() => {
                toast.style.display = 'none';
            }, 300);
        }
    }

    // Modal Logic
    function confirmDelete(url, message) {
        if(message) {
            document.getElementById('deleteModalMessage').textContent = message;
        }
        document.getElementById('deleteModalConfirmBtn').href = url;
        
        const modal = document.getElementById('deleteConfirmModal');
        const backdrop = document.getElementById('deleteModalBackdrop');
        const content = document.getElementById('deleteModalContent');
        
        modal.classList.remove('hidden');
        
        // Trigger reflow
        void modal.offsetWidth;
        
        backdrop.classList.remove('opacity-0');
        content.classList.remove('scale-95', 'opacity-0');
    }
    
    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        const backdrop = document.getElementById('deleteModalBackdrop');
        const content = document.getElementById('deleteModalContent');
        
        backdrop.classList.add('opacity-0');
        content.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    
    // Cleanup URL query params for toast
    if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        if (url.searchParams.has('msg') || url.searchParams.has('err')) {
            url.searchParams.delete('msg');
            url.searchParams.delete('err');
            url.searchParams.delete('to');
            window.history.replaceState(null, '', url.toString());
        }
    }
</script>

<!-- Custom Scripts -->
<script src="<?= BASE_URL ?>/assets/js/invoice.js"></script>

<!-- Premium Form Validation Logic -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Disable default HTML5 validation tooltip
        form.setAttribute('novalidate', true);
        
        const inputs = form.querySelectorAll('input, select, textarea');
        
        const validateInput = (input) => {
            // Remove existing error message
            let container = input.parentElement.classList.contains('relative') ? input.parentElement.parentElement : input.parentElement;
            const existingError = container.querySelector('.premium-error-msg');
            if (existingError) {
                existingError.remove();
            }
            
            // Reset borders
            input.classList.remove('border-red-500', 'focus:ring-red-500', 'border-green-500', 'focus:ring-green-500');
            
            if (!input.checkValidity()) {
                input.classList.add('border-red-500', 'focus:ring-red-500');
                
                // Determine error message
                let msg = 'This field is required.';
                if (input.validity.typeMismatch && input.type === 'email') {
                    msg = 'Please enter a valid email address.';
                } else if (input.validity.patternMismatch) {
                    msg = input.title || 'Invalid format.';
                } else if (input.validity.tooShort) {
                    msg = `Minimum length is ${input.minLength} characters.`;
                }
                
                // Inject premium error msg
                const errorDiv = document.createElement('div');
                errorDiv.className = 'premium-error-msg flex items-center text-red-500 text-xs mt-1.5 font-medium animate-pulse';
                errorDiv.innerHTML = `<i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> ${msg}`;
                
                let containerToAppend = input.parentElement.classList.contains('relative') ? input.parentElement.parentElement : input.parentElement;
                containerToAppend.appendChild(errorDiv);
                
                lucide.createIcons();
                return false;
            } else if (input.value.trim() !== '') {
                // Show success border if not empty
                input.classList.add('border-green-500', 'focus:ring-green-500');
            }
            return true;
        };
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                // Only validate if it's required or has a value
                if (input.hasAttribute('required') || input.value.trim() !== '') {
                    validateInput(input);
                }
            });
            input.addEventListener('input', () => {
                if(input.classList.contains('border-red-500')) {
                    validateInput(input);
                }
            });
        });
        
        form.addEventListener('submit', (e) => {
            let isValid = true;
            inputs.forEach(input => {
                if (input.hasAttribute('required') || input.value.trim() !== '') {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = form.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });
    
    // Global Premium Dropdowns
    window.initPremiumDropdowns = function() {
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            // Skip if already initialized or inside a hidden template
            if (select.nextElementSibling && select.nextElementSibling.classList.contains('custom-dropdown-container')) return;
            
            // Hide original select
            select.style.display = 'none';
            
            // Create container
            const container = document.createElement('div');
            container.className = 'relative custom-dropdown-container';
            
            // Create button
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'w-full h-[42px] bg-white border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 px-3 flex items-center justify-between transition-colors outline-none shadow-sm';
            
            // Check if there's a selected option
            const selectedOption = select.options[select.selectedIndex];
            
            const textSpan = document.createElement('span');
            textSpan.className = 'truncate';
            textSpan.innerText = selectedOption ? selectedOption.text : 'Select...';
            
            const iconSpan = document.createElement('span');
            iconSpan.innerHTML = '<i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>';
            
            button.appendChild(textSpan);
            button.appendChild(iconSpan);
            
            // Create dropdown menu
            const menu = document.createElement('div');
            menu.className = 'absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-xl hidden max-h-60 overflow-y-auto py-1 custom-dropdown-menu';
            
            // Populate options
            Array.from(select.options).forEach(opt => {
                const item = document.createElement('div');
                item.className = 'px-4 py-2.5 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 cursor-pointer transition-colors';
                if (opt.selected) {
                    item.classList.add('bg-brand-50', 'text-brand-700', 'font-medium');
                }
                item.innerText = opt.text;
                
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Update original select
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true })); // Trigger change event
                    
                    // Update button text
                    textSpan.innerText = opt.text;
                    
                    // Update active state in menu
                    Array.from(menu.children).forEach(child => {
                        child.classList.remove('bg-brand-50', 'text-brand-700', 'font-medium');
                    });
                    item.classList.add('bg-brand-50', 'text-brand-700', 'font-medium');
                    
                    // Hide menu
                    menu.classList.add('hidden');
                });
                
                menu.appendChild(item);
            });
            
            // Toggle menu
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close all other dropdowns
                document.querySelectorAll('.custom-dropdown-menu').forEach(m => {
                    if (m !== menu) m.classList.add('hidden');
                });
                menu.classList.toggle('hidden');
            });
            
            container.appendChild(button);
            container.appendChild(menu);
            
            select.parentNode.insertBefore(container, select.nextSibling);
            
            // Re-initialize lucide icons for the newly injected chevron-down
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    };
    
    window.initPremiumDropdowns();
    
    
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown-container')) {
            document.querySelectorAll('.custom-dropdown-menu').forEach(m => m.classList.add('hidden'));
        }
    });

});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Search Inputs
    const searchInputs = document.querySelectorAll('input[placeholder^="Search"]');
    let localSearchInput = null;
    let globalSearchInput = null;
    
    searchInputs.forEach(input => {
        if(input.placeholder === 'Search anything...') {
            globalSearchInput = input;
        } else {
            localSearchInput = input;
        }
    });

    if (localSearchInput) {
        localSearchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const tbody = document.querySelector('table tbody');
            if(tbody) {
                const rows = tbody.querySelectorAll('tr');
                let matchCount = 0;
                rows.forEach(row => {
                    // Skip empty state rows
                    if(row.cells.length === 1 && row.cells[0].colSpan > 1) return;
                    
                    if(row.innerText.toLowerCase().includes(term)) {
                        row.style.display = '';
                        matchCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update pagination placeholder if it exists
                const countDisplay = document.querySelector('.p-4.border-t.border-gray-100 > div');
                if (countDisplay && countDisplay.innerText.includes('Showing')) {
                    countDisplay.innerText = `Showing ${matchCount} records (filtered)`;
                }
            }
        });
    }

    if (globalSearchInput) {
        globalSearchInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                const term = this.value;
                if(term) {
                    window.location.href = `/index.php?search=${encodeURIComponent(term)}`;
                }
            }
        });
    }

    // Filter Buttons
    const filterBtns = document.querySelectorAll('button');
    let filterBtn = null;
    filterBtns.forEach(btn => {
        if(btn.innerText.includes('Filter') || btn.querySelector('i[data-lucide="filter"]')) {
            filterBtn = btn;
        }
    });

    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            // Simple sort by first column text
            const tbody = document.querySelector('table tbody');
            if(tbody) {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                // Skip sorting if only one empty row
                if(rows.length <= 1 && rows[0] && rows[0].cells.length === 1) return;
                
                let isAsc = tbody.getAttribute('data-sort') !== 'asc';
                
                rows.sort((a, b) => {
                    const textA = a.cells[0].innerText.trim();
                    const textB = b.cells[0].innerText.trim();
                    return isAsc ? textA.localeCompare(textB) : textB.localeCompare(textA);
                });
                
                rows.forEach(row => tbody.appendChild(row));
                tbody.setAttribute('data-sort', isAsc ? 'asc' : 'desc');
                
                // Show visual feedback
                const icon = filterBtn.querySelector('i');
                if(icon) {
                    icon.setAttribute('data-lucide', isAsc ? 'arrow-down-a-z' : 'arrow-up-z-a');
                    lucide.createIcons();
                }
            }
        });
    }
    // Sidebar Toggle
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    if (desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-minimized');
            // Save state to local storage
            localStorage.setItem('sidebarMinimized', document.body.classList.contains('sidebar-minimized'));
        });
        
        // Restore state
        if (localStorage.getItem('sidebarMinimized') === 'true') {
            document.body.classList.add('sidebar-minimized');
        }
    }
    
    // Notifications Toggle
    const notifToggle = document.getElementById('notificationToggle');
    const notifDropdown = document.getElementById('notificationDropdown');
    
    if (notifToggle && notifDropdown) {
        notifToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!notifToggle.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });
    }

    // Profile Toggle
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.add('hidden');
            }
        });
    }
});

function markNotificationsRead() {
    fetch(window.BASE_URL + '/ajax/notifications_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_read'
    })
    .then(response => response.text())
    .then(data => {
        // Hide the red badge
        const toggle = document.getElementById('notificationToggle');
        if(toggle) {
            const badge = toggle.querySelector('.bg-red-500');
            if(badge) badge.remove();
        }
        // Update styling of all unread items in dropdown
        const items = document.querySelectorAll('#notificationDropdown .unread-notif');
        items.forEach(item => {
            item.classList.remove('unread-notif', 'bg-brand-50', 'shadow-sm', 'hover:bg-brand-100', 'border-brand-100');
            item.classList.add('opacity-75', 'bg-white', 'hover:bg-gray-50', 'border-gray-50');
        });
    });
}

function markSingleNotificationRead(id) {
    fetch(window.BASE_URL + '/ajax/notifications_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_read&id=' + id
    })
    .then(response => response.text())
    .then(data => {
        const item = document.getElementById('notif-' + id);
        if(item && item.classList.contains('unread-notif')) {
            item.classList.remove('unread-notif', 'bg-brand-50', 'shadow-sm', 'hover:bg-brand-100', 'border-brand-100');
            item.classList.add('opacity-75', 'bg-white', 'hover:bg-gray-50', 'border-gray-50');
        }
        
        // Check if there are any unread left
        const unreadItems = document.querySelectorAll('#notificationDropdown .unread-notif');
        if (unreadItems.length === 0) {
            const toggle = document.getElementById('notificationToggle');
            if(toggle) {
                const badge = toggle.querySelector('.bg-red-500');
                if(badge) badge.remove();
            }
        }
    });
}
</script>

<!-- Premium Tooltips (Tippy.js) -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css" />
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/animations/shift-away.css" />

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Convert all title attributes to premium tooltips
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(el => {
        const content = el.getAttribute('title');
        if (content) {
            el.setAttribute('data-tippy-content', content);
            el.removeAttribute('title'); // Prevent native tooltip
        }
    });

    tippy('[data-tippy-content]', {
        theme: 'light-border',
        animation: 'shift-away',
        arrow: true,
        placement: 'top',
        delay: [50, 0]
    });
    
    // Custom styling for premium theme
    const style = document.createElement('style');
    style.innerHTML = `
        .tippy-box[data-theme~='light-border'] {
            background-color: #ffffff;
            color: #1e293b;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            font-weight: 500;
            font-size: 12px;
            padding: 2px 6px;
        }
        .tippy-box[data-theme~='light-border'][data-placement^='top'] > .tippy-arrow::before {
            border-top-color: #ffffff;
        }
        .tippy-box[data-theme~='light-border'][data-placement^='bottom'] > .tippy-arrow::before {
            border-bottom-color: #ffffff;
        }
    `;
    document.head.appendChild(style);
});
</script>

</body>
</html>