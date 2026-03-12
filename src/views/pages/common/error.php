<?php
$code = $data['code'] ?? 500;
$title = $data['title'] ?? 'Internal Server Error';
$message = $data['message'] ?? 'Oops! Something went wrong. Please try again later.';

// Color mapping for different codes
$colors = [
    403 => '#ef4444', // red-500
    404 => '#f97316', // orange-500
    500 => '#6b7280'  // gray-500
];
$color = $colors[$code] ?? '#6b7280';
?>

<main style="
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center; 
    min-height: 83vh; 
    text-align: center; 
    padding: 4rem 0; 
    font-family: Arial, sans-serif;
">
    <div class="main-container">
        <div style="
        font-size: 6rem; 
        font-weight: bold; 
        color: <?= $color ?>;
        margin-bottom: 10px;
    ">
            <?= $code ?>
        </div>
        <h2 style="
        font-size: 1.8rem; 
        margin-bottom: 10px; 
        color: #111827;
    ">
            <?= htmlspecialchars($title) ?>
        </h2>
        <p style="
        font-size: 1.1rem; 
        color: #4b5563; 
        max-width: 600px;
        margin-bottom: 20px;
    ">
            <?= htmlspecialchars($message) ?>
        </p>
        
    </div>
    <?php
    $fillColor = '#fffbeb';
    $invert = true;
    $offset = true;

    include BASE_PATH . '/src/views/components/divider-banner.php';
    ?>
</main>