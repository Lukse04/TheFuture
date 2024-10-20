document.addEventListener('DOMContentLoaded', function() {
    fetch('/includes/api/marketplace/get_listings.inc.php')
        .then(response => response.json())
        .then(data => {
            // Kodo tęsinys...
        });
});
