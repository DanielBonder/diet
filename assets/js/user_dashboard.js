document.addEventListener('DOMContentLoaded', function() {
    // הסתר את כל הסקשנים כברירת מחדל
    var sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    sections.forEach(function(id) {
        var section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
        }
    });
});

function showSection(sectionId) {
    // הסתר את כל הסקשנים
    var sections = ['appointmentsSection', 'menuSection', 'paymentSection'];
    sections.forEach(function(id) {
        var section = document.getElementById(id);
        if (section) {
            section.style.display = 'none';
            section.querySelector('section').classList.remove('section-active');
        }
    });

    // הצג את האוברליי
    var overlay = document.getElementById('pageOverlay');
    overlay.style.display = 'block';

    // הצג את הסקשן שנבחר
    var selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.style.display = 'block';
        selectedSection.querySelector('section').classList.add('section-active');
        
        // הוסף אפקט הנפשה לסקשן
        setTimeout(function() {
            selectedSection.scrollIntoView({behavior: 'smooth'});
        }, 100);
    }
    
    // הוסף אפשרות לסגור את האוברליי בלחיצה
    overlay.onclick = function() {
        sections.forEach(function(id) {
            var section = document.getElementById(id);
            if (section) {
                section.style.display = 'none';
                if (section.querySelector('section')) {
                    section.querySelector('section').classList.remove('section-active');
                }
            }
        });
        this.style.display = 'none';
    };
}

 