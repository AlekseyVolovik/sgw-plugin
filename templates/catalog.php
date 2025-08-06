<?php declare(strict_types=1);

use SGWPlugin\Controllers\CatalogController;

global $params;

// ВАЖНО: вызываем ДО get_header()
$controller = new CatalogController($params);
$content = $controller->render();

get_header(); ?>

<div class="sgw-wrapper" data-prefix="single_page">
    <div class="ct-container-full" data-content="normal">
        <article id="post-sgw-catalog" class="post-sgw-catalog page type-page status-publish hentry">
            <div class="entry-content is-layout-constrained">
                <?php echo $content; ?>
            </div>
        </article>
    </div>
</div>

<?php get_footer(); ?>
