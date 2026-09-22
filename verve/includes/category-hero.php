<?php
$categoryEdits = [
    'electronics' => ['TUNE INTO YOUR EVERYDAY', 'Small tech.', 'Big possibilities.', 'Set the mood, find your focus, and stay connected. Discover the little upgrades that make every day work better.', 'Good sound. Great company.', 'Headphones, a portable speaker and a smartwatch'],
    'fashion' => ['THE EVERYDAY WARDROBE', 'Your day.', 'Your own way.', 'Easy layers, favourite fits, and the finishing touches. Find pieces that feel like you, wherever the day takes you.', 'Wear it your way.', 'Denim jacket, white sneakers and sunglasses'],
    'home-living' => ['MAKE ROOM FOR COMFORT', 'Stay in.', 'Feel at home.', 'Slow coffee mornings. A softer corner. Thoughtful details that turn your space into your favourite place.', 'Little details. Lovely living.', 'Ceramic coffee carafe, linen cushion and a vase'],
    'beauty' => ['A MOMENT JUST FOR YOU', 'A little care.', 'A daily ritual.', 'Make a little room for yourself. Explore skincare and personal-care essentials for a routine you look forward to.', 'Your everyday pause.', 'Skincare bottles, a cream jar and a towel'],
    'sports' => ['THE RADA CART OUTDOOR EDIT', 'Find your', 'next outside.', 'A little fresh air. A new favourite trail. Gear for the everyday adventures that make you feel alive.', 'Less routine. More adventure.', 'A hiker following a winding trail through sunlit green hills'],
    'books-stationery' => ['FOR CURIOUS MINDS', 'Turn a page.', 'Start something.', 'A story to get lost in. A fresh page for your next idea. Make space for reading, writing, and a little imagination.', 'Big ideas start here.', 'Clothbound books, an open notebook and pencils'],
    'bags-travel' => ['TAKE THE SCENIC ROUTE', 'Pack light.', 'Dream a little bigger.', 'From your daily commute to a weekend away, find bags and travel essentials ready to go wherever you do.', 'Next stop: somewhere new.', 'Canvas backpack, suitcase and a travel pouch'],
    'toys-games' => ['LET THE GOOD TIMES PLAY', 'More play.', 'More possibility.', 'Build something. Start a game. Share a laugh. Discover little reasons to put the everyday on pause and play together.', 'Small moments. Big smiles.', 'Wooden building blocks, a toy train and a board game'],
];
$edit = $categoryEdits[$activeCategory['slug']] ?? ['THE RADA CART COLLECTION', 'Discover your', 'next favourite.', $activeCategory['description'] ?: 'Thoughtful finds for your everyday.', 'Everyday things, thoughtfully chosen.', $activeCategory['name']];
$heroImage = $activeCategory['slug'] === 'sports' ? 'sports-outdoors-hero.png' : $activeCategory['image'];
?>
<section class="outdoor-hero category-editorial" data-category="<?= h($activeCategory['slug']) ?>" aria-labelledby="category-hero-title">
  <div class="shell outdoor-hero-inner">
    <div class="outdoor-hero-copy">
      <span class="outdoor-eyebrow"><?= h($edit[0]) ?></span>
      <p class="outdoor-category"><?= h($activeCategory['name']) ?></p>
      <h1 id="category-hero-title"><?= h($edit[1]) ?><br><em><?= h($edit[2]) ?></em></h1>
      <p class="outdoor-description"><?= h($edit[3]) ?></p>
      <a class="outdoor-cta" href="#shop-products">Explore the collection <span aria-hidden="true">&searr;</span></a>
    </div>
    <div class="outdoor-hero-photo">
      <img src="<?= h(productImageUrl($heroImage, $activeCategory['name'])) ?>" alt="<?= h($edit[5]) ?>" fetchpriority="high">
      <span class="outdoor-photo-note"><span aria-hidden="true">&#10036;</span> <?= h($edit[4]) ?></span>
    </div>
  </div>
</section>
