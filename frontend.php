<div class="dictionary">
	<?php
	$page_link = get_permalink();

	$is_simple_permalinks = isset($_GET['page_id']);
	$is_search_page = isset($_GET['terms_s']) && !empty($_GET['terms_s']);
	$is_letter_page = isset($_GET['letter']) && !empty($_GET['letter']);
	$is_tag_page = isset($_GET['terms-tag']) && !empty($_GET['terms-tag']);
	$is_pagination_page = isset($_GET['page_terms']) && !empty($_GET['page_terms']);

	$page_id = $is_simple_permalinks ? $_GET['page_id'] : null;
	$search_query = $is_search_page ? $_GET['terms_s'] : '';
	$current_letter = $is_letter_page ? $_GET['letter'] : null;
	$current_tag = $is_tag_page ? $_GET['terms-tag'] : null;
	$page_terms = $is_pagination_page ? $_GET['page_terms'] : 1;

	$is_show_search = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);	
	$terms_per_page = $atts['terms_per_page'];

	$args = array( 
		'post_type' => 'dict-terms',
		'orderby' => 'title',	
		'order' => 'ASC',
		'paged' => $page_terms
	);

	if ($terms_per_page) {
		$args['posts_per_page'] = $terms_per_page;
	}

	if ($is_search_page) {
		$args['s'] = $search_query;
	}

	if ($is_letter_page) {
		$args['tax_query'][0][0]['taxonomy'] = 'dict-terms-letter';
		$args['tax_query'][0][0]['field'] = 'term_id';
		$args['tax_query'][0][0]['terms'] = $current_letter;
	}

	if ($is_tag_page) {
		$args['tax_query'][0][0]['taxonomy'] = 'dict-terms-tag';
		$args['tax_query'][0][0]['field'] = 'term_id';
		$args['tax_query'][0][0]['terms'] = $current_tag;
	}

	$td = new WP_Query($args);



	if ($is_show_search) {
		echo '<form class="terms_search">';

		if ($is_simple_permalinks) {
			echo '<input type="hidden" name="page_id" value="' . $page_id . '">';
		}
		
		echo '<input type="text" name="terms_s" value="' . $search_query . '" required>';
		echo '<input type="submit" value="search">';
		echo '</form>';
	}

	if ($td->have_posts()) {
		echo '<nav class="letters">';

		if ($is_letter_page || $current_tag) {
			echo '<a href="' . $page_link . '" class="all-letters">' . __('To all terms', 'terms-dictionary') . '</a>';
		}

		if (!$is_search_page) {
			$args = array(
				'taxonomy' => 'dict-terms-letter',
				'order' => 'ASC',
				'hide_empty' => true
			);
			$terms = get_terms($args);

			unset($_GET['terms_s']);
			unset($_GET['page_terms']);
				
			foreach($terms as $letter) {
				$current = $is_letter_page && $current_letter == $letter->term_id ? 'current' : null;
				$link = '?' . http_build_query(array_merge($_GET, array('letter' => $letter->term_id)));

				echo '<a href="' . $link . '" class="letter ' . $current . '">' . $letter->name . '</a>';
			}				
		}

		echo '</nav>';

		if ($is_search_page) {
			echo '<p class="search-notice">'. __('Search:', 'terms-dictionary') . ' ' . $search_query .'</p>';
		}

		if ($is_tag_page) {
			$term = get_term_by('term_id', $current_tag, 'dict-terms-tag');

			echo '<p class="search-notice">'. __('Tag:', 'terms-dictionary') . ' ' . $term->name . '</p>';
		}

		echo '<div class="terms">';
			while ($td->have_posts()): $td->the_post();
				$term_tags = get_the_terms($td->ID, 'dict-terms-tag');

				echo '<div class="term">';

				if (get_the_post_thumbnail()) {
					echo the_post_thumbnail('dictionary-thumbnail');
				}

				echo '<strong>' . get_the_title() . '</strong>';
				echo ' ';
				if (is_array($term_tags) && !is_wp_error($term_tags)) {
					$tags = array();
					
					foreach ($term_tags as $tag) {
						$link = '?' . http_build_query(array_merge($_GET, array('terms-tag' => $tag->term_id)));

						$tags[] = '<a href="'. $link .'">' . $tag->name . '</a>';
					}

					echo '(' . implode(', ', $tags) . ')';
				}
				echo ' - ' . get_the_content();

				echo '</div>';
			endwhile;
		echo '</div>';
		
		if ($td->max_num_pages > 1) {
			echo '<div class="td-pagination">';
				$args = array(
					'base' => '%_%',
					'format' => '?page_terms=%#%',
					'total' => $td->max_num_pages,
					'current' => $td->query['paged'],
					'prev_next' => false
				); 

				echo str_replace(array('href=""', "href=''"), 'href="."', paginate_links($args));
			echo '</div>';
		}
	} else {
		echo '<h3>' . __('No terms yet ...', 'terms-dictionary') . '</h3>';

		if ($is_search_page) {
			echo '<a href="' . $page_link . '" class="back-to-all">' . __('Back', 'terms-dictionary') . '</a>';
		}
	}

	wp_reset_postdata();
	?>
</div>