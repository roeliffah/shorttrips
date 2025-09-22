<?php
// Template for displaying a list of available hotels based on search criteria.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Assuming $hotels is an array of hotel data passed to this template
?>

<div class="hotel-list">
    <h2>Available Hotels</h2>
    <?php if (!empty($hotels)): ?>
        <ul>
            <?php foreach ($hotels as $hotel): ?>
                <li class="hotel-item">
                    <h3><?php echo esc_html($hotel['name']); ?></h3>
                    <p><?php echo esc_html($hotel['city']); ?>, <?php echo esc_html($hotel['country']); ?></p>
                    <p>Star Rating: <?php echo esc_html($hotel['star_rating']); ?></p>
                    <p>Price: <?php echo esc_html($hotel['price_total']); ?> <?php echo esc_html($hotel['currency']); ?></p>
                    <a href="<?php echo esc_url(get_permalink($hotel['hotel_id'])); ?>" class="button">View Details</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hotels available for your search criteria.</p>
    <?php endif; ?>
</div>