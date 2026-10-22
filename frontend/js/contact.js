const contactForm = document.querySelector('.contact-form');

if (contactForm) {
    contactForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(contactForm);
        const subject = contactForm.elements.subject.selectedOptions[0].textContent.trim();
        const body = [
            `Nom : ${formData.get('name')}`,
            `Adresse e-mail : ${formData.get('email')}`,
            `Téléphone : ${formData.get('phone') || 'Non renseigné'}`,
            '',
            'Message :',
            formData.get('message'),
        ].join('\n');
        const composeUrl = new URL('https://mail.google.com/mail/');

        composeUrl.search = new URLSearchParams({
            view: 'cm',
            fs: '1',
            to: contactForm.dataset.recipient,
            su: `CongoVital - ${subject}`,
            body,
        }).toString();

        window.location.assign(composeUrl);
    });
}
