<?php
/*
Template Name: Hotel Page
*/

get_header(); ?>

<div class="hotel-page">

    <?php
    // Check if hotel ID is provided
    if (isset($_GET['hotel_id'])) {
        $hotel_id = intval($_GET['hotel_id']);
        
        // Fetch hotel details from the database or API
        // This is a placeholder for the actual data fetching logic
        $hotel_details = []; // Replace with actual data fetching logic

        if (!empty($hotel_details)) {
            ?>
            <h1><?php echo esc_html($hotel_details['name']); ?></h1>
            <div class="hotel-details">
                <img src="<?php echo esc_url($hotel_details['image_url']); ?>" alt="<?php echo esc_attr($hotel_details['name']); ?>">
                <p><?php echo esc_html($hotel_details['description']); ?></p>
                <p><strong>Location:</strong> <?php echo esc_html($hotel_details['city'] . ', ' . $hotel_details['country']); ?></p>
                <p><strong>Star Rating:</strong> <?php echo esc_html($hotel_details['star_rating']); ?></p>
                <p><strong>Price:</strong> <?php echo esc_html($hotel_details['price_total'] . ' ' . $hotel_details['currency']); ?></p>
            </div>

            <div class="booking-form">
                <?php
                // Include the booking form template
                get_template_part('page-templates/template-booking-form');
                ?>
            </div>
            <?php
        } else {
            echo '<p>No hotel details found.</p>';
        }
    } else {
        echo '<p>No hotel ID provided.</p>';
    }
    ?>

</div>

<?php
get_footer();
?>