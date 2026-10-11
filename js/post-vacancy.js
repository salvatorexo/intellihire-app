const form = document.getElementById('vacancyForm');
const alertBox = document.getElementById('alertBox');
const postBtn = document.getElementById('postBtn');

document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
});

function showAlert(msg, type = 'error') {
    alertBox.textContent = msg;
    alertBox.className = 'alert ' + type;
    alertBox.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.style.display = 'none';

    const payload = {
        title:                  document.getElementById('title').value.trim(),
        description:            document.getElementById('description').value.trim(),
        location:               document.getElementById('location').value.trim(),
        job_type:               document.getElementById('job_type').value,
        salary_range:           document.getElementById('salary_range').value.trim(),
        deadline:               document.getElementById('deadline').value,
        education_requirements: document.getElementById('education_requirements').value.trim(),
        requirements:           document.getElementById('requirements').value.trim(),
        skills:                 document.getElementById('skills').value.trim(),
        preferred_skills:       document.getElementById('preferred_skills').value.trim(),
    };

    if (!payload.title || !payload.description || !payload.location || !payload.skills || !payload.deadline) {
        showAlert('Please fill in all required fields.');
        return;
    }

    postBtn.disabled = true;
    postBtn.textContent = 'Posting...';

    try {
        const res = await fetch('../php/post-job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showAlert('✅ Vacancy posted successfully! Redirecting...', 'success');
            setTimeout(() => window.location.href = 'my-vacancies.html', 1400);
        } else {
            showAlert(data.message || 'Failed to post vacancy.');
        }
    } catch (err) {
        showAlert('Network error. Please try again.');
    } finally {
        postBtn.disabled = false;
        postBtn.textContent = 'Post Vacancy';
    }
});