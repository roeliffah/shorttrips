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
                    <?php include FREESTAYS_PLUGIN_DIR . 'templates/hotel-card.php'; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hotels available for your search criteria.</p>
    <?php endif; ?>
</div>