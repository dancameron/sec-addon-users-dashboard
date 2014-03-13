<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
	<title>
    <?php
		global $page, $paged;

		wp_title( '>', true, 'right' );

		// Add the blog name.
		bloginfo( 'name' );

		// Add the blog description for the home/front page.
		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) )
			echo " > $site_description";

		// Add a page number if necessary:
		if ( $paged >= 2 || $page >= 2 )
			echo ' ? ' . sprintf( gb__( 'Page %s' ), max( $paged, $page ) ); 

		?>
    </title>
	<?php wp_head(); ?>
		
</head>

<?php
	while ( have_posts() ) : the_post();
		the_content();
	endwhile; ?>

<?php
	wp_footer(); ?>
</body>
</html>