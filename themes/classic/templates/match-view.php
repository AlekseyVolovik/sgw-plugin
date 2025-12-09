<?php declare(strict_types=1);

use SGWPlugin\Controllers\MatchPageController;
use SGWPlugin\Classes\ThemeHeader;
use SGWPlugin\Classes\ThemeFooter;
use SGWPlugin\Classes\MetaBuilder;

global $params;

$controller = new MatchPageController($params);
$content = $controller->render();

$title       = method_exists(MetaBuilder::class, 'getTitle') ? MetaBuilder::getTitle() : '';
$description = method_exists(MetaBuilder::class, 'getDescription') ? MetaBuilder::getDescription() : '';
$canonical   = $params['canonical'] ?? ($GLOBALS['sgw_canonical'] ?? null);

ThemeHeader::render([
    'title'       => $title,
    'canonical'   => $canonical,
    'description' => $description,
]);
?>

<section id="post-sgw-match" class="post-sgw-match">
    <?php echo $content; ?>
</section>

<?php ThemeFooter::render(); ?>