<?php
/**
 * Template voor één hotelkaart (card) in de zoekresultaten.
 * Verwacht een $hotel array/object met minimaal:
 * - name
 * - location
 * - image_url
 * - price
 * - url (link naar detailpagina)
 */
if (!isset($hotel)) return;
?>
<div class="fs-hotel-card">
    <a href="<?php echo esc_url($hotel['url']); ?>">
        <img class="fs-hotel-img" src="<?php echo esc_url($hotel['image_url']); ?>" alt="<?php echo esc_attr($hotel['name']); ?>">
    </a>
    <div class="fs-hotel-info">
        <a href="<?php echo esc_url($hotel['url']); ?>" class="fs-hotel-title">
            <?php echo esc_html($hotel['name']); ?>
        </a>
        <div class="fs-hotel-location">
            <?php echo esc_html($hotel['location']); ?>
        </div>
        <?php if (!empty($hotel['price'])): ?>
            <div class="fs-hotel-price">
                Vanaf &euro;<?php echo esc_html($hotel['price']); ?> per nacht
            </div>
        <?php endif; ?>
        <div style="margin-top:12px;">
            <a href="<?php echo esc_url($hotel['url']); ?>" class="fs-btn fs-btn-primary">Bekijk hotel</a>
        </div>
    </div>
</div>
<?php if (!empty($hotel_data)): ?>
<div class="fs-hotel-card">
    <h3><?php echo esc_html($hotel_data['name'] ?? ''); ?></h3>
    <p><?php echo esc_html($hotel_data['address'] ?? ''); ?>, <?php echo esc_html($hotel_data['city'] ?? ''); ?></p>
    <p>Sterren: <?php echo esc_html($hotel_data['classification'] ?? ''); ?></p>
    <p>Thema's: <?php echo esc_html($hotel_data['themes'] ?? ''); ?></p>
    <p>Prijs: <?php echo esc_html($hotel_data['price'] ?? ''); ?></p>
    <a href="#" class="fs-btn fs-btn-yellow">Bekijk hotel</a>
</div>
<?php endif; ?>