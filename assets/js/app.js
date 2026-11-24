// ===== FORM VALIDATION =====
// Add Bootstrap's validation styles and block forms with invalid required fields.
document.querySelectorAll('.needs-validation').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
});

// ===== BOOKING DATE LIMIT =====
// Prevent selecting a booking date earlier than today when that date field exists.
const bookingDate = document.getElementById('bdate');
if (bookingDate) {
    bookingDate.min = new Date().toISOString().split('T')[0];
}

// ===== CONFIRM DESTRUCTIVE ACTIONS =====
// Ask for confirmation before submitting actions marked with a data-confirm message.
document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
        if (!confirm(button.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// ===== GALLERY LIGHTBOX =====
const galleryLightbox = document.getElementById('gallery-lightbox');
if (galleryLightbox) {
    const lightboxImage = document.getElementById('gallery-lightbox-image');
    const lightboxCaption = document.getElementById('gallery-lightbox-caption');

    galleryLightbox.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement) || !lightboxImage || !lightboxCaption) {
            return;
        }

        lightboxImage.src = trigger.getAttribute('href') || '';
        lightboxImage.alt = trigger.dataset.galleryAlt || '';
        lightboxCaption.textContent = trigger.dataset.galleryCaption || '';
    });

    galleryLightbox.addEventListener('hidden.bs.modal', () => {
        if (lightboxImage && lightboxCaption) {
            lightboxImage.removeAttribute('src');
            lightboxImage.alt = '';
            lightboxCaption.textContent = '';
        }
    });
}
