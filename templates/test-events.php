<?php declare(strict_types=1);

use SGWPlugin\Controllers\TestEventsController;

global $params;

$controller = new TestEventsController($params);
$content = $controller->render();

get_header(); ?>
<div class="sgw-wrapper" data-prefix="test_events">
  <div class="ct-container-full" data-content="normal">
    <article class="post-sgw-test">
      <div class="entry-content is-layout-constrained">
        <?php echo $content; ?>
      </div>
    </article>
  </div>
</div>
<?php get_footer(); ?>
