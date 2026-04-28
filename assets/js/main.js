document.addEventListener('DOMContentLoaded', function () {
    var toggleButton = document.querySelector('[data-nav-toggle]');
    var navMenu = document.querySelector('[data-nav-menu]');

    if (toggleButton && navMenu) {
        toggleButton.addEventListener('click', function () {
            navMenu.classList.toggle('is-open');
        });
    }

    var revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window && revealElements.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.18,
        });

        revealElements.forEach(function (element) {
            observer.observe(element);
        });
    } else {
        revealElements.forEach(function (element) {
            element.classList.add('is-visible');
        });
    }

    var heroCarousel = document.querySelector('[data-hero-carousel]');

    if (heroCarousel) {
        var slides = heroCarousel.querySelectorAll('.hero-slide');
        var dots = document.querySelectorAll('.hero-dot');
        var activeIndex = 0;

        var setActiveSlide = function (index) {
            slides.forEach(function (slide, slideIndex) {
                slide.classList.toggle('is-active', slideIndex === index);
            });

            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        };

        var nextSlide = function () {
            activeIndex = (activeIndex + 1) % slides.length;
            setActiveSlide(activeIndex);
        };

        if (slides.length > 1) {
            heroCarousel.addEventListener('click', nextSlide);

            window.setInterval(function () {
                nextSlide();
            }, 3500);
        }
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var requiredFields = form.querySelectorAll('[required]');
            var firstInvalid = null;

            requiredFields.forEach(function (field) {
                var value = field.value.trim();
                field.setCustomValidity('');

                if (!value) {
                    field.setCustomValidity('Please complete this field.');
                    firstInvalid = firstInvalid || field;
                }

                if (field.type === 'email' && value) {
                    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!emailPattern.test(value)) {
                        field.setCustomValidity('Please enter a valid email address.');
                        firstInvalid = firstInvalid || field;
                    }
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.reportValidity();
                firstInvalid.focus();
            }
        });
    });
});

