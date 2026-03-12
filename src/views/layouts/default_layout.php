<?php
$viewFile = $data['viewFile'] ?? '';
$title = $data['title'] ?? '';
$page = $data['page'] ?? '';
$email = $data['extra']['email'] ?? '';
$token = $data['extra']['token'] ?? '';
$roleFromUrl = $data['extra']['roleFromUrl'] ?? '';
$formAction = $data['extra']['formAction'] ?? '';

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiffinCraft - <?= ucfirst($title) ?></title>

    <!-- Public CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <link rel="stylesheet" href="/assets/css/dashboard.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script defer src="/assets/js/navbar.js"></script>
    <script>
        document.documentElement.classList.replace('no-js', 'js');
    </script>
</head>

<body>

    <?php
    include BASE_PATH . '/src/views/components/header.php';
    if (!empty($viewFile) && file_exists($viewFile)) {
        include $viewFile;
    } else {
        $code = 404;
        $title = 'Page Not Found';
        include BASE_PATH . '/src/views/pages/error.php';
    }
    include BASE_PATH . '/src/views/components/footer.php';
    ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Flash message auto-hide
            document.querySelectorAll(".flash-message").forEach((message) => {
                setTimeout(() => {
                    message.style.transition = "all 0.3s ease";
                    message.style.opacity = "0";
                    message.style.transform = "translateX(100%)";
                    setTimeout(() => message.remove(), 300);
                }, 3000);
            });
        });
    </script>

</body>

</html>