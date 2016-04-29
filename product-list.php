<?php
/* Template Name: Product list */
get_header();
query_posts(array('post_type' => 'product'));
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<?php
					the_title( '<h1 class="page-title">', '</h1>' );
				?>
			</header>

			<?php
            while ( have_posts() ) : the_post(); $id = get_the_ID();?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header><!-- .entry-header -->

                <?php twentysixteen_excerpt(); ?>

                <?php twentysixteen_post_thumbnail(); ?>

                    <div>
                        Price: <?php echo get_post_meta($id, 'price')[0]; ?>$<br />
                        Inventory: <?php echo get_post_meta($id, 'inventory')[0]; ?> left <br />
                    </div>

                <div class="entry-content">
                    <?php
                        the_content();
                    ?>
                </div>

                <footer class="entry-footer">
                    <?php twentysixteen_entry_meta(); ?>
                </footer>
            </article>
            <?php
			endwhile;

		else :
			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>
		</main>
	</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>