<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_GET['lang'])) {
    $allowed_langs = ['es', 'en', 'de'];
    $_SESSION['lang'] = in_array($_GET['lang'], $allowed_langs) ? $_GET['lang'] : 'es';
}
$lang = $_SESSION['lang'] ?? 'es';

$dict_path = __DIR__ . "/locales/{$lang}.json";
$translations = file_exists($dict_path) ? json_decode(file_get_contents($dict_path), true) : [];

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

function formatoMoneda($precio_base_mxn) {
    global $lang;
    
    if ($lang === 'es') {
        $locale = 'es_MX'; $currency = 'MXN'; $tasa_cambio = 1; 
    } elseif ($lang === 'de') {
        $locale = 'de_DE'; $currency = 'EUR'; $tasa_cambio = 21;
    } else {
        $locale = 'en_US'; $currency = 'USD'; $tasa_cambio = 17;
    }
    
    $valor_convertido = $precio_base_mxn / $tasa_cambio;
    $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($valor_convertido, $currency);
}

function formatoFecha($fecha) {
    global $lang;
    if ($lang === 'es') { $locale = 'es_MX'; } 
    elseif ($lang === 'de') { $locale = 'de_DE'; } 
    else { $locale = 'en_US'; }
    
    $formatter = new IntlDateFormatter($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
    return $formatter->format(strtotime($fecha));
}
?>