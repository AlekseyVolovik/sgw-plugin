<?php declare(strict_types=1);

use SGWPlugin\Controllers\MatchPageController;

global $params;

// ВАЖНО: вызываем ДО get_header()
$controller = new MatchPageController($params);
$content = $controller->render();

get_header(); ?>

<div class="sgw-wrapper" data-prefix="single_match">
    <div class="ct-container-full" data-content="normal">
        <article class="post-sgw-match">
            <div class="entry-content is-layout-constrained">
                <?php echo $content; ?>
            </div>
        </article>
    </div>
</div>

<?php get_footer(); ?>
