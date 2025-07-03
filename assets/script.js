
document.addEventListener("DOMContentLoaded", function () {
    var sidebar = document.getElementById("sidebar");
    var toggleBtn = document.getElementById("toggle-btn");

    toggleBtn.addEventListener("click", function () {
        if (sidebar.style.left === "-220px") {
            sidebar.style.left = "0"; // Open Sidebar
        } else {
            sidebar.style.left = "-220px"; // Close Sidebar
        }
    });
});


// 10/04/2025
document.addEventListener('DOMContentLoaded', function () {
    const resumeForm = document.getElementById('resumeForm');
    const linkedinForm = document.getElementById('linkedinForm');
    const resumeStatus = document.getElementById('resumeStatus');
    const linkedinStatus = document.getElementById('linkedinStatus');

    // Resume Upload
    resumeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(resumeForm);

        fetch('backend/parse_pdf.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            resumeStatus.innerHTML = data.success 
                ? `<span style="color: green;">✅ ${data.message}</span>`
                : `<span style="color: red;">❌ ${data.error}</span>`;
        })
        .catch(err => {
            resumeStatus.innerHTML = `<span style="color: red;">❌ Network error: ${err}</span>`;
        });
    });

    // LinkedIn URL Fetch
    linkedinForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(linkedinForm);

        fetch('backend/fetch_linkedin_data.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            linkedinStatus.innerHTML = data.success 
                ? `<span style="color: green;">✅ ${data.message}</span>`
                : `<span style="color: red;">❌ ${data.error}</span>`;
        })
        .catch(err => {
            linkedinStatus.innerHTML = `<span style="color: red;">❌ Network error: ${err}</span>`;
        });
    });
});
