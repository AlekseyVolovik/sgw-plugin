<?php declare(strict_types=1);

use SGWPlugin\Controllers\LeaguePageController;
use SGWPlugin\Classes\ThemeHeader;
use SGWPlugin\Classes\ThemeFooter;
use SGWPlugin\Classes\MetaBuilder;

global $params;

$controller = new LeaguePageController($params);
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

<section id="post-sgw-catalog" class="post-sgw-catalog">
    <?php echo $content; ?>
</section>

<?php ThemeFooter::render(); ?>