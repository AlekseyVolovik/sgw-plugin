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

<div class="sgw-wrapper" data-prefix="single_match">
    <div class="ct-container-full" data-content="normal">
        <article class="post-sgw-match">
            <div class="entry-content is-layout-constrained">
                <?php echo $content; ?>
            </div>
        </article>
    </div>
</div>

<?php ThemeFooter::render(); ?>