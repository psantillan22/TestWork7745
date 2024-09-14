<?php
/**
 * The template for displaying full width pages.
 *
 * Template Name: Weather Template
 *
 * @package storefront
 */

get_header(); 

global $wpdb;

$query = "SELECT p.ID, p.post_title, pm1.meta_value AS latitude, pm2.meta_value AS longitude, t.name AS country
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
    INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
    INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
    LEFT JOIN {$wpdb->postmeta} pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_city_latitude')
    LEFT JOIN {$wpdb->postmeta} pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_city_longitude')
    WHERE p.post_type = 'cities'
    AND p.post_status = 'publish'
    AND tt.taxonomy = 'country'";

$cities = $wpdb->get_results( $query );

?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<h1><?php the_title(); ?></h1>

			<?php do_action('before_cities_table'); ?>

			<input type="text" id="search_city" placeholder="Search City...">

			
			<table class="table-cities" id="cities_table">
			<thead>
				<tr>
					<th>Country</th>
					<th>City Name</th>
					<th>Latitude</th>
					<th>Longitude</th>
					<th>Temperature</th>
				</tr>
			</thead>
			<tbody id="cities_table_body">
			<?php
			if ( ! empty( $cities ) ) {
				

				foreach ( $cities as $city ) {
					$temperature = get_city_temperature($city->latitude, $city->longitude);

					echo '<tr>';
					echo '<td>' . esc_html( $city->country ) . '</td>';
					echo '<td>' . esc_html( $city->post_title ) . '</td>';
					echo '<td>' . esc_html( $city->latitude ) . '</td>';
					echo '<td>' . esc_html( $city->longitude ) . '</td>';
					echo '<td>' . esc_html( $temperature ) . '° </td>';
					echo '</tr>';
				}
			} else {
				echo '<tr><td colspan="4">No data found.</td></tr>';
			}
			?>
			</tbody>
		</table>

		<?php do_action('after_cities_table'); ?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
