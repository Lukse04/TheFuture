<?php
    // Nustatykite numatytąjį profilio paveikslėlį, jei vartotojas neturi įkėlęs savo
    $defaultProfilePicture = 'default_profile.webp'; // Nurodykite numatytojo paveikslėlio kelią
    $profilePicture = $fetch_profile['profile_picture'] ? $fetch_profile['profile_picture'] : $defaultProfilePicture;
?>

<div class="profile-picture-container">
    <h1>Nuotrauka</h1>
    <br>
    <img src="image/assets/profile_pictures/<?php echo htmlspecialchars($profilePicture); ?>" alt="Profilio nuotrauka" class="profile-picture">
</div>


    