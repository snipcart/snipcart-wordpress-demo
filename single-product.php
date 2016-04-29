<?php

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		while ( have_posts() ) : the_post();
        $id = get_the_ID();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header><!-- .entry-header -->

                <?php twentysixteen_post_thumbnail(); ?>

                <div class="entry-content">
                    <?php
                    the_content();
                    ?>
                </div>
                <div>
                    price: <strong><?php echo get_post_meta($id, 'price')[0]; ?>$</strong> <br />
                    inventory: <strong><?php echo get_post_meta($id, 'inventory')[0]; ?> left</strong>
                </div>
                <div class="snipcart-button-wrapper">
                    <button
                        class="snipcart-add-item"
                        data-item-id="<?php echo get_post_meta($id, 'id')[0]; ?>"
                        data-item-name="<?php echo get_the_title(); ?>"
                        data-item-price="<?php echo get_post_meta($id, 'price')[0]; ?>"
                        data-item-max-quantity="<?php echo get_post_meta($id, 'inventory')[0]; ?>"
                        data-item-url="<?php echo get_the_permalink(); ?>"
                        data-item-image="<?php echo wp_get_attachment_image_src(get_post_thumbnail_id($id), 'single-post-thumbnail')[0]?>"
                    >
                        Buy
                    </button>
                </div>

            </article>
            <?php
			// End of the loop.
		endwhile;
		?>

	</main><!-- .site-main -->

	<?php get_sidebar( 'content-bottom' ); ?>

</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>