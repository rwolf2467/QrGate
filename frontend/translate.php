<?php


$config = [
    'default_language' => 'en', // original text language (if you enter not the original text language the system will break!)
    'fallback_language' => 'en' 
];
// Hey User , here you can set your own languages you can enter any language you want but attention that you have the formart right :)
$languages = [
    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
    'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
    'es' => ['name' => 'Español', 'flag' => '🇪🇸'],
];

if (isset($_POST['language'])) {
    $_SESSION['language'] = $_POST['language'];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = $config['default_language'];
}

function getCacheKey($text, $sourceLang, $targetLang) {
    return md5($text . $sourceLang . $targetLang);
}

function getCache($key) {
    $cacheFile = sys_get_temp_dir() . '/trans_' . $key . '.cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        return file_get_contents($cacheFile);
    }
    return false;
}

function setCache($key, $value) {
    $cacheFile = sys_get_temp_dir() . '/trans_' . $key . '.cache';
    file_put_contents($cacheFile, $value);
}

function translateBatch($texts, $targetLang, $sourceLang) {
    if ($targetLang == $sourceLang) return array_combine($texts, $texts);
    
    $translations = [];
    $textsToTranslate = [];
    
    foreach ($texts as $text) {
        $cacheKey = getCacheKey($text, $sourceLang, $targetLang);
        $cached = getCache($cacheKey);
        
        if ($cached !== false) {
            $translations[$text] = $cached;
        } else {
            $textsToTranslate[] = $text;
        }
    }
    
    if (empty($textsToTranslate)) {
        return $translations;
    }

    $url = 'https://api.mymemory.translated.net/get';
    $results = [];
    
    foreach ($textsToTranslate as $text) {
        $params = http_build_query([
            'q' => $text,
            'langpair' => $sourceLang . '|' . $targetLang
        ]);
        
        $response = file_get_contents($url . '?' . $params);
        $data = json_decode($response, true);
        $translation = $data['responseData']['translatedText'] ?? $text;
        
        $translations[$text] = $translation;

        $cacheKey = getCacheKey($text, $sourceLang, $targetLang);
        setCache($cacheKey, $translation);
    }
    
    return $translations;
}

if (isset($_POST['translate'])) {
    header('Content-Type: application/json');
    $texts = json_decode($_POST['texts'] ?? '[]', true);
    $targetLang = $_POST['targetLang'] ?? $config['fallback_language'];
    
    echo json_encode([
        'translations' => translateBatch($texts, $targetLang, $config['default_language'])
    ]);
    exit();
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('[data-translate]');
    const currentLang = '<?php echo $_SESSION['language']; ?>';
    const defaultLang = '<?php echo $config['default_language']; ?>';
    
    if (currentLang === defaultLang) return;
    
    const textsToTranslate = [];
    const elementMap = new Map();
    
    elements.forEach(element => {
        if (!element.originalText) {
            element.originalText = element.innerText;
        }
        element.classList.add('loading');
        textsToTranslate.push(element.originalText);
        elementMap.set(element.originalText, element);
    });
    
    if (textsToTranslate.length === 0) return;
    
    const formData = new FormData();
    formData.append('translate', '1');
    formData.append('texts', JSON.stringify(textsToTranslate));
    formData.append('targetLang', currentLang);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Object.entries(data.translations).forEach(([original, translation]) => {
            const element = elementMap.get(original);
            if (element) {
                element.innerText = translation;
                element.classList.remove('loading');
            }
        });
    })
    .catch(error => {
        console.error('Übersetzungsfehler:', error);
        elements.forEach(element => element.classList.remove('loading'));
    });
});
</script>