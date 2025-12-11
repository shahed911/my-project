// ========================
// ملف JavaScript لصفحة الأهل مع PHP
// ========================

let currentChildForTasks = null;

// تحميل الصفحة
window.onload = function() {
    loadChildren();
    loadReports();
};

// التبديل بين الأقسام الرئيسية
function showMainTab(tabName) {
    document.querySelectorAll('.main-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById(tabName).classList.add('active');
    
    if (tabName === 'reports') {
        loadReports();
    }
}

// تسجيل الخروج
function logout() {
    if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
        window.location.href = 'logout.php';
    }
}

// ========================
// المعلومات الشخصية
// ========================

function showChangePassword() {
    document.getElementById('change-password').style.display = 'block';
}

function hideChangePassword() {
    document.getElementById('change-password').style.display = 'none';
    document.getElementById('current-password').value = '';
    document.getElementById('new-password').value = '';
    document.getElementById('confirm-new-password').value = '';
}

async function updateProfile() {
    const name = document.getElementById('profile-name').value.trim();
    const email = document.getElementById('profile-email').value.trim();
    
    if (!name || !email) {
        showToast('يرجى ملء جميع الحقول!', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'updateProfile');
    formData.append('name', name);
    formData.append('email', email);
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('user-name').textContent = `مرحباً، ${name} 👋`;
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
}

async function changePassword() {
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-new-password').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        showToast('يرجى ملء جميع الحقول!', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showToast('كلمتا المرور الجديدتان غير متطابقتين!', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'changePassword');
    formData.append('current_password', currentPassword);
    formData.append('new_password', newPassword);
    
    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideChangePassword();
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
}

// ========================
// إدارة الأطفال
// ========================

async function loadChildren() {
    try {
        const response = await fetch('api/children.php?action=getAll');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error, 'error');
            return;
        }
        
        const children = data.data;
        const container = document.getElementById('children-list');
        
        if (children.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">👶</div>
                    <p>لم تقم بإضافة أي طفل بعد</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = children.map(child => {
            const genderIcon = child.gender === 'ذكر' ? '👦' : '👧';
            const cardClass = child.gender === 'ذكر' ? 'boy' : 'girl';
            
            return `
                <div class="child-card ${cardClass}">
                    <div class="child-icon">${genderIcon}</div>
                    <div class="child-info">
                        <h3>${child.name}</h3>
                        <div class="child-details">
                            <div class="child-detail">
                                <span>العمر:</span>
                                <strong>${child.age} سنوات</strong>
                            </div>
                            <div class="child-detail">
                                <span>الجنس:</span>
                                <strong>${child.gender}</strong>
                            </div>
                            <div class="child-detail">
                                <span>الجلسات:</span>
                                <strong>${child.total_sessions || 0}</strong>
                            </div>
                            <div class="child-detail">
                                <span>النجوم:</span>
                                <strong>${child.total_stars || 0} ⭐</strong>
                            </div>
                        </div>
                        <div class="badge-display">
                            <div class="badge-icon">${child.badge_icon || '🎈'}</div>
                            <div class="badge-name">${child.badge_name || 'مبتدئ'}</div>
                        </div>
                        <div class="child-actions">
                            <button onclick="openChildPage('${child.child_id}')">
                                فتح الصفحة 🚀
                            </button>
                            <button onclick="showManageTasks('${child.child_id}', '${child.name}', ${child.age})">
                                إدارة المهام 📝
                            </button>
                            <button onclick="editChild('${child.child_id}')">
                                تعديل ✏️
                            </button>
                            <button onclick="deleteChild('${child.child_id}', '${child.name}')">
                                حذف 🗑️
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        showToast('حدث خطأ في تحميل البيانات!', 'error');
    }
}

function showAddChild() {
    document.getElementById('add-child-form').style.display = 'block';
}

function hideAddChild() {
    document.getElementById('add-child-form').style.display = 'none';
    document.getElementById('add-child-form-element').reset();
}

document.getElementById('add-child-form-element').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add');
    
    try {
        const response = await fetch('api/children.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideAddChild();
            loadChildren();
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
});

async function editChild(childId) {
    try {
        const response = await fetch(`api/children.php?action=getOne&child_id=${childId}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error, 'error');
            return;
        }
        
        const child = data.data;
        
        document.getElementById('edit-child-id').value = child.child_id;
        document.getElementById('edit-child-name').value = child.name;
        document.getElementById('edit-child-birthdate').value = child.birthdate;
        document.getElementById('edit-child-gender').value = child.gender;
        
        document.getElementById('edit-child-form').style.display = 'block';
    } catch (error) {
        showToast('حدث خطأ في تحميل البيانات!', 'error');
    }
}

function hideEditChild() {
    document.getElementById('edit-child-form').style.display = 'none';
}

document.getElementById('edit-child-form-element').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'update');
    
    try {
        const response = await fetch('api/children.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideEditChild();
            loadChildren();
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
});

async function deleteChild(childId, childName) {
    if (!confirm(`هل أنت متأكد من حذف ${childName}؟\nسيتم حذف جميع البيانات والمهام المرتبطة به!`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('child_id', childId);
    
    try {
        const response = await fetch('api/children.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadChildren();
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
}

function openChildPage(childId) {
    window.location.href = `child.php?id=${childId}`;
}

// ========================
// إدارة المهام
// ========================

async function showManageTasks(childId, childName, childAge) {
    currentChildForTasks = { id: childId, name: childName, age: childAge };
    
    document.getElementById('tasks-child-name').textContent = childName;
    
    await loadCurrentTasks(childId);
    await loadContentForAge(childAge);
    
    document.getElementById('manage-tasks-modal').style.display = 'block';
}

function hideManageTasks() {
    document.getElementById('manage-tasks-modal').style.display = 'none';
    currentChildForTasks = null;
}

async function loadCurrentTasks(childId) {
    try {
        const response = await fetch(`api/tasks.php?action=getByChild&child_id=${childId}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error, 'error');
            return;
        }
        
        const tasks = data.data;
        const container = document.getElementById('current-tasks-list');
        
        if (tasks.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:#999;">لا توجد مهام حالياً</p>';
            return;
        }
        
        container.innerHTML = tasks.map(task => `
            <div class="task-item">
                <div class="task-order">${task.task_order}</div>
                <div class="task-info">
                    <div class="task-title">${task.thumbnail} ${task.title}</div>
                    <div class="task-meta">
                        ${task.type} • ${task.assigned_duration} دقيقة • ${task.status}
                    </div>
                </div>
                <div class="task-actions">
                    <button onclick="moveTask('${task.task_id}', 'up')" class="btn-warning">↑</button>
                    <button onclick="moveTask('${task.task_id}', 'down')" class="btn-warning">↓</button>
                    <button onclick="deleteTask('${task.task_id}')" class="btn-danger">حذف</button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        showToast('حدث خطأ في تحميل المهام!', 'error');
    }
}

async function loadContentForAge(age) {
    try {
        const response = await fetch(`api/tasks.php?action=getContentByAge&age=${age}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error, 'error');
            return;
        }
        
        const content = data.data;
        const select = document.getElementById('task-content');
        
        select.innerHTML = '<option value="">اختر المحتوى...</option>' +
            content.map(c => 
                `<option value="${c.content_id}" data-duration="${c.default_duration}">
                    ${c.thumbnail} ${c.title} (${c.type} - ${c.difficulty})
                </option>`
            ).join('');
        
        select.onchange = function() {
            const option = this.options[this.selectedIndex];
            if (option.dataset.duration) {
                document.getElementById('task-duration').value = option.dataset.duration;
            }
        };
    } catch (error) {
        showToast('حدث خطأ في تحميل المحتوى!', 'error');
    }
}

document.getElementById('add-task-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'add');
    formData.append('child_id', currentChildForTasks.id);
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            this.reset();
            loadCurrentTasks(currentChildForTasks.id);
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
});

async function deleteTask(taskId) {
    if (!confirm('هل أنت متأكد من حذف هذه المهمة؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('task_id', taskId);
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCurrentTasks(currentChildForTasks.id);
            showToast(data.message, 'success');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
}

async function moveTask(taskId, direction) {
    const formData = new FormData();
    formData.append('action', 'updateOrder');
    formData.append('task_id', taskId);
    formData.append('direction', direction);
    
    try {
        const response = await fetch('api/tasks.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCurrentTasks(currentChildForTasks.id);
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال!', 'error');
    }
}

// ========================
// التقارير
// ========================

async function loadReports() {
    try {
        const response = await fetch('api/reports.php?action=getAll');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.error, 'error');
            return;
        }
        
        const reports = data.data;
        const container = document.getElementById('reports-list');
        
        if (reports.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <p>لا توجد تقارير بعد</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = reports.map(report => `
            <div class="report-item">
                <div class="report-header">
                    <div class="report-title">
                        ${report.gender === 'ذكر' ? '👦' : '👧'} ${report.child_name}
                    </div>
                    <div class="report-date">
                        ${report.completed_sessions} جلسة
                    </div>
                </div>
                <div class="report-stats">
                    <div class="stat-box">
                        <div class="stat-value">${report.total_stars}</div>
                        <div class="stat-label">⭐ إجمالي النجوم</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">${report.total_time_minutes}</div>
                        <div class="stat-label">⏱️ دقيقة تعلم</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value">${parseFloat(report.avg_stars).toFixed(1)}</div>
                        <div class="stat-label">📊 متوسط النجوم</div>
                    </div>
                </div>
                <div class="report-content">
                    <strong>آخر الجلسات:</strong><br>
                    ${report.recent_sessions.map(s => {
                        const date = new Date(s.end_time).toLocaleDateString('ar-SA', { 
                            month: 'short', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        return `• ${s.title} - ${s.stars_earned}⭐ - ${date}`;
                    }).join('<br>')}
                </div>
            </div>
        `).join('');
    } catch (error) {
        showToast('حدث خطأ في تحميل التقارير!', 'error');
    }
}

// ========================
// وظائف مساعدة
// ========================

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}