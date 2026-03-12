<?php
// Optional dynamic content
$message = $message ?? "Own a Tiffin Business?";
$buttonText = $buttonText ?? "Join TiffinCraft Business";
$buttonLink = $buttonLink ?? "/business";
?>
<div id="ctaBar" class="cta-bar fixed top-0 left-0 w-full bg-orange-100 text-orange-800 z-50 flex items-center justify-between shadow -translate-y-full opacity-0 transition-all duration-300">
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 flex justify-between items-center h-full">
        <div>
            <span class="font-semibold"><?= htmlspecialchars($message) ?></span>
            <a href="<?= htmlspecialchars($buttonLink) ?>" class="ml-2 text-orange-700 underline hover:text-orange-900"><?= htmlspecialchars($buttonText) ?></a>
        </div>
        <button id="closeCta" class="ml-4 text-orange-800 hover:text-orange-900 text-xl font-bold">&times;</button>
    </div>
</div>