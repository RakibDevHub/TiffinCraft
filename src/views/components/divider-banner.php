<?php

/**
 * Divider Banner Component
 *
 * Options:
 *  - $fillColor  (string)  SVG fill color (default: #FFFBEB)
 *  - $invert     (bool)    Adds --invert modifier class
 *  - $offset     (bool)    Adds --offset modifier class
 *  - $height     (int)     SVG height in px (default: 320)
 *  - $class      (string)  Extra wrapper classes
 */

$fillColor = $fillColor ?? '#FFFBEB';
$invert    = $invert ?? false;
$offset    = $offset ?? false;
$height    = $height ?? 320;
$class     = $class ?? '';

$wrapperClasses = 'divider-banner';

if ($invert) {
    $wrapperClasses .= ' divider-banner--invert';
}

if ($offset) {
    $wrapperClasses .= ' divider-banner--offset';
}

if ($class) {
    $wrapperClasses .= ' ' . $class;
}
?>

<div class="<?= htmlspecialchars($wrapperClasses) ?>">
    <svg class="divider-banner__svg"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 1440 <?= (int)$height ?>"
        preserveAspectRatio="none">
        <path fill="<?= htmlspecialchars($fillColor) ?>"
            d="M0,224L48,208C96,192,192,160,288,154.7C384,149,480,171,576,186.7C672,203,768,213,864,192C960,171,1056,117,1152,112C1248,107,1344,149,1392,170.7L1440,192V0H0Z" />
    </svg>
</div>