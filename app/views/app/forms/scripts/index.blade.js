document.addEventListener('DOMContentLoaded', function () {

    var splieIdentifier = document.getElementById('splideCarousel');
    if (splieIdentifier){
        new Splide('#splideCarousel', {
            type   : 'loop',        // Continuous loop for seamless scrolling
            perPage: 8,             // Show 4 slides on desktop
            breakpoints: {
                1920: { perPage: 8 }, // 8 slides on desktop
                1600: { perPage: 6 }, // 6 slides on desktop
                1024: { perPage: 4 }, // 4 slides on desktop
                768: { perPage: 3 },  // 3 slides on tablet
                640: { perPage: 2 },  // 2 slides on mobile
                480: { perPage: 1 },  // 1 slide on mobile
            },
            gap    : '1rem',        // Gap between slides
            pagination: false,      // Hide pagination bullets
            arrows : true,          // Show navigation arrows
        }).mount();
    }
    
});