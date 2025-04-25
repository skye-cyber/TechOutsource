import AOS from 'aos';
import 'aos/dist/aos.css';


document.addEventListener('DOMContentLoaded', () => {

    /*const animateElements = document.querySelectorAll('[class*="animate-"]');
    const options = {
        threshold: 2
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, options);

    animateElements.forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });*/

    AOS.init({
        once: true,
        duration: 800,
        startEvent: 'DOMContentLoaded', // initialize on DOM ready
    });
    setTimeout(() => {
        AOS.refresh(); // ensures visibility gets recalculated
    }, 100); // slight delay allows layout to stabilize

})
