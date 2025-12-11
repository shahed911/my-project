// ========================
// ملف JavaScript لصفحة الطفل مع PHP
// ========================

let currentChild = null;
let currentTasks = [];
let currentTaskIndex = 0;
let timerInterval = null;
let remainingSeconds = 0;
let currentSession = null;

// تحميل الصفحة
window.onload = async function() {
    await loadChildData();
};

// تحميل بيانات الطفل
async function loadChildData() {
    try {
        const response = await fetch(`api/children.php?action=getOne&child_id=${CHILD_ID}`);
        const data = await response.json();
        
        if (!data.success) {
            alert('الطفل غير موجود!');
            window.location.href = 'parent.php';
            return;
        }
        
        currentChild = data.data;
        
        // عرض معلومات الطفل
        const genderIcon = currentChild.gender === 'ذكر' ? '👦' : '👧';
        document.getElementById('child-name').textContent = `مرحباً ${currentChild.name}! ${genderIcon}`;
        document.getElementById('child-icon').textContent = genderIcon;
        document.getElementById('child-age').textContent = `${currentChild.age} سنوات`;
        document.getElementById('total-stars').textContent = `${currentChild.total_stars || 0} نجمة`;
        document.getElementById('badge-icon').textContent = currentChild.badge_icon || '🎈';
        document.getElementById('badge-name').textContent = currentChild.badge_name || 'مبتدئ';
        
        // تحميل المهام
        await loadTasks();
        
    } catch (error) {
        alert('حدث خطأ في تحميل البيانات!');
        window.location.href = 'parent.php';
    }
}

// تحميل المهام
async function loadTasks() {
    try {
        const response = await fetch(`api/tasks.php?action=getByChild&child_id=${CHILD_ID}`);
        const data = await response.json();
        
        if (!data.success) {
            showNoTasks();
            return;
        }
        
        currentTasks = data.data.filter(t => t.is_allowed);
        
        if (currentTasks.length === 0) {
            showNoTasks();
            return;
        }
        
        // البحث عن أول مهمة لم تكتمل
        currentTaskIndex = currentTasks.findIndex(t => t.status === 'قيد الانتظار');
        
        if (currentTaskIndex === -1) {
            showAllTasksCompleted();
            return;
        }
        
        displayCurrentTask();
        
    } catch (error) {
        showNoTasks();
    }
}

function showNoTasks() {
    document.getElementById('tasks-container').innerHTML = `
        <div class="completion-card">
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <p>لا توجد مهام متاحة حالياً</p>
                <p style="margin-top: 10px;">اطلب من والديك إضافة مهام لك! 😊</p>
            </div>
        </div>
    `;
}

// عرض المهمة الحالية
function displayCurrentTask() {
    const task = currentTasks[currentTaskIndex];
    const container = document.getElementById('tasks-container');
    
    container.innerHTML = `
        <div class="task-card">
            <div class="task-header">
                <div class="task-number">${currentTaskIndex + 1}</div>
                <div class="task-content-info">
                    <h2>${task.thumbnail} ${task.title}</h2>
                    <p class="task-type">${task.type} • ${task.difficulty}</p>
                </div>
            </div>
            
            <div class="task-description">
                <p style="color: #666; font-size: 16px; line-height: 1.6; margin: 15px 0;">
                    ${task.description || ''}
                </p>
                ${task.parent_notes ? `
                    <div style="background: #fff3cd; padding: 15px; border-radius: 10px; border-right: 4px solid #ffc107; margin: 15px 0;">
                        <strong>📌 ملاحظة من والديك:</strong><br>
                        ${task.parent_notes}
                    </div>
                ` : ''}
            </div>
            
            <div id="timer-section" style="display: none;">
                <div class="timer-display">
                    <p style="font-size: 20px; color: #666;">الوقت المتبقي:</p>
                    <div class="timer" id="timer">00:00</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 100%"></div>
                </div>
            </div>
            
            <div class="task-actions-center">
                <button id="start-btn" onclick="startTask()" class="btn btn-primary btn-large">
                    ابدأ التعلم! 🚀
                </button>
                <button id="end-btn" onclick="endTask()" class="btn btn-danger btn-large" style="display: none;">
                    إنهاء المهمة ⏹️
                </button>
            </div>
            
            <div style="text-align: center; margin-top: 20px; color: #999;">
                المهمة ${currentTaskIndex + 1} من ${currentTasks.length}
            </div>
        </div>
    `;
}

// بدء المهمة
async function startTask() {
    const task = currentTasks[currentTaskIndex];
    
    try {
        // بدء الجلسة في قاعدة البيانات
        const formData = new FormData();
        formData.append('action', 'start');
        formData.append('child_id', CHILD_ID);
        formData.append('content_id', task.content_id);
        formData.append('task_id', task.task_id);
        
        const response = await fetch('api/sessions.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!data.success) {
            alert('فشل بدء الجلسة!');
            return;
        }
        
        currentSession = data.data.session_id;
        
        // بدء المؤقت
        remainingSeconds = task.assigned_duration * 60;
        
        document.getElementById('start-btn').style.display = 'none';
        document.getElementById('end-btn').style.display = 'inline-block';
        document.getElementById('timer-section').style.display = 'block';
        
        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);
        
    } catch (error) {
        alert('حدث خطأ في بدء الجلسة!');
    }
}

// تحديث المؤقت
function updateTimer() {
    if (remainingSeconds <= 0) {
        endTask();
        return;
    }
    
    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    
    document.getElementById('timer').textContent = 
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    
    // تحديث شريط التقدم
    const task = currentTasks[currentTaskIndex];
    const totalSeconds = task.assigned_duration * 60;
    const progress = ((totalSeconds - remainingSeconds) / totalSeconds) * 100;
    document.getElementById('progress-fill').style.width = progress + '%';
    
    remainingSeconds--;
}

// إنهاء المهمة
async function endTask() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    
    const task = currentTasks[currentTaskIndex];
    const totalSeconds = task.assigned_duration * 60;
    const elapsedSeconds = totalSeconds - remainingSeconds;
    const completionPercentage = Math.round((elapsedSeconds / totalSeconds) * 100);
    
    // حساب النجوم
    let stars = 1;
    if (completionPercentage >= 90) stars = 5;
    else if (completionPercentage >= 75) stars = 4;
    else if (completionPercentage >= 60) stars = 3;
    else if (completionPercentage >= 40) stars = 2;
    
    try {
        // إنهاء الجلسة في قاعدة البيانات
        const formData = new FormData();
        formData.append('action', 'end');
        formData.append('session_id', currentSession);
        formData.append('duration_minutes', Math.ceil(elapsedSeconds / 60));
        formData.append('stars_earned', stars);
        formData.append('completion_percentage', completionPercentage);
        
        const response = await fetch('api/sessions.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // تحديث بيانات الطفل المحلية
            currentChild = data.data.child;
            
            // عرض النتيجة
            showTaskResult(stars, completionPercentage);
        } else {
            alert('فشل إنهاء الجلسة!');
        }
        
    } catch (error) {
        alert('حدث خطأ في إنهاء الجلسة!');
    }
}

// عرض نتيجة المهمة
function showTaskResult(stars, percentage) {
    const starsDisplay = '⭐'.repeat(stars);
    let message = '';
    
    if (stars === 5) message = 'رائع جداً! 🎉';
    else if (stars === 4) message = 'عمل ممتاز! 👏';
    else if (stars === 3) message = 'جيد! 👍';
    else if (stars === 2) message = 'جيد، حاول أكثر! 💪';
    else message = 'أكمل المهمة المرة القادمة! 😊';
    
    const container = document.getElementById('tasks-container');
    
    container.innerHTML = `
        <div class="completion-card">
            <h2 style="color: #667eea; font-size: 32px;">🎓 انتهى وقت التعلم!</h2>
            <div class="stars-display">${starsDisplay}</div>
            <div class="completion-message">${message}</div>
            <div style="margin: 30px 0;">
                <div class="stat-box" style="display: inline-block; margin: 0 10px;">
                    <div class="stat-value">${stars}</div>
                    <div class="stat-label">نجوم حصلت عليها</div>
                </div>
                <div class="stat-box" style="display: inline-block; margin: 0 10px;">
                    <div class="stat-value">${percentage}%</div>
                    <div class="stat-label">نسبة الإكمال</div>
                </div>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin: 20px 0;">
                <h3 style="color: #667eea; margin-bottom: 15px;">📊 إحصائياتك الإجمالية:</h3>
                <p style="font-size: 18px; margin: 10px 0;">
                    <strong>مجموع النجوم:</strong> ${currentChild.total_stars} ⭐
                </p>
                <p style="font-size: 18px; margin: 10px 0;">
                    <strong>عدد الجلسات:</strong> ${currentChild.total_sessions} 📚
                </p>
                <p style="font-size: 18px; margin: 10px 0;">
                    <strong>اللقب الحالي:</strong> ${currentChild.badge_icon} ${currentChild.badge_name}
                </p>
            </div>
            <button onclick="nextTask()" class="btn btn-primary btn-large">
                المهمة التالية ⏭️
            </button>
            <button onclick="goBackToParent()" class="btn btn-secondary btn-large" style="margin-top: 10px;">
                العودة للأهل 🏠
            </button>
        </div>
    `;
    
    // تحديث الإحصائيات في الرأس
    document.getElementById('total-stars').textContent = `${currentChild.total_stars} نجمة`;
    document.getElementById('badge-icon').textContent = currentChild.badge_icon;
    document.getElementById('badge-name').textContent = currentChild.badge_name;
}

// الانتقال للمهمة التالية
function nextTask() {
    currentTaskIndex++;
    
    if (currentTaskIndex >= currentTasks.length) {
        showAllTasksCompleted();
        return;
    }
    
    // البحث عن المهمة التالية غير المكتملة
    while (currentTaskIndex < currentTasks.length && currentTasks[currentTaskIndex].status === 'مكتمل') {
        currentTaskIndex++;
    }
    
    if (currentTaskIndex >= currentTasks.length) {
        showAllTasksCompleted();
        return;
    }
    
    displayCurrentTask();
}

// عرض رسالة إكمال جميع المهام
function showAllTasksCompleted() {
    const container = document.getElementById('tasks-container');
    
    container.innerHTML = `
        <div class="completion-card">
            <h2 style="color: #667eea; font-size: 36px;">🎊 أحسنت!</h2>
            <div style="font-size: 80px; margin: 30px 0;">🏆</div>
            <div class="completion-message" style="font-size: 24px;">
                لقد أكملت جميع المهام! 
            </div>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        color: white; 
                        padding: 30px; 
                        border-radius: 20px; 
                        margin: 30px 0;">
                <h3 style="font-size: 28px; margin-bottom: 20px;">إنجازاتك اليوم:</h3>
                <p style="font-size: 24px; margin: 15px 0;">
                    ⭐ ${currentChild.total_stars} نجمة
                </p>
                <p style="font-size: 24px; margin: 15px 0;">
                    📚 ${currentChild.total_sessions} جلسة تعليمية
                </p>
                <p style="font-size: 24px; margin: 15px 0;">
                    ${currentChild.badge_icon} ${currentChild.badge_name}
                </p>
            </div>
            <p style="font-size: 20px; color: #666; margin: 20px 0;">
                ${currentChild.badge_description || 'استمر في التعلم!'}
            </p>
            <button onclick="goBackToParent()" class="btn btn-primary btn-large">
                العودة للأهل 🏠
            </button>
        </div>
    `;
}

// العودة لصفحة الأهل
function goBackToParent() {
    window.location.href = 'parent.php';
}