// ✅ ודא שכל הסקשנים מוסתרים כברירת מחדל ונטען רק מה שצריך

function showSection(sectionId) {
    const sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    sections.forEach(id => {
        const section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
            const inner = section.querySelector('section');
            if (inner) inner.classList.remove('section-active');
        }
    });

    const overlay = document.getElementById('pageOverlay');
    overlay.style.display = 'block';

    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.style.display = 'block';
        const inner = selectedSection.querySelector('section');
        if (inner) inner.classList.add('section-active');
        setTimeout(() => selectedSection.scrollIntoView({ behavior: 'smooth' }), 100);
    }

    overlay.onclick = () => {
        sections.forEach(id => {
            const section = document.getElementById(id);
            if (section) {
                section.style.display = 'none';
                const inner = section.querySelector('section');
                if (inner) inner.classList.remove('section-active');
            }
        });
        overlay.style.display = 'none';
    };
}

// ✅ הפונקציה הזו מופעלת עם הטעינה כדי להציג את הסקשן הפעיל משמירה קודמת

window.addEventListener('DOMContentLoaded', () => {
    const sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    const activeSection = document.body.dataset.activeSection;

    sections.forEach(id => {
        const section = document.getElementById(id);
        if (section) section.style.display = 'none';
    });

    if (activeSection && sections.includes(activeSection)) {
        showSection(activeSection);
    } else if (sessionStorage.getItem('scrollToMenu') === 'yes') {
        showSection('menuSection');
        sessionStorage.removeItem('scrollToMenu');
    }
});

function setMenuSection() {
    sessionStorage.setItem('scrollToMenu', 'yes');
}