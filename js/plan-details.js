// Plan Details Page Script

document.addEventListener('DOMContentLoaded', function() {
    // Get the destination from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const destinationParam = urlParams.get('destination');
    
    // Map destination names to IDs
    const destinationMap = {
        'pumauna-waterfalls': 'pumauna-waterfalls',
        'azuela-springs': 'azuela-springs',
        'mt-kampalilis': 'mt-kampalilis',
        'tagum-river': 'tagum-river',
        'tagum-city-museum': 'tagum-city-museum',
        'san-fernando-church': 'san-fernando-church'
    };
    
    // Default to pumauna-waterfalls if no destination is specified
    const destination = destinationMap[destinationParam] || 'pumauna-waterfalls';
    
    // Show the selected destination
    showDestination(destination);
});

function showDestination(destinationId) {
    // Hide all destinations
    const allDestinations = document.querySelectorAll('.destination-detail');
    allDestinations.forEach(dest => dest.classList.remove('active'));
    
    // Show selected destination
    const selectedDestination = document.getElementById(destinationId);
    if (selectedDestination) {
        selectedDestination.classList.add('active');
        
        // Update breadcrumb title
        const title = selectedDestination.querySelector('h1').textContent;
        document.getElementById('breadcrumb-title').textContent = title;
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Hide images that fail to load to remove broken picture icons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('src')) {
            img.style.display = 'none';
            return;
        }
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});
