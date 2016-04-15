<?php

/**
 * Register [badgeos_search] shortcode.
 *
 * @since 1.4.0
 */
function badgeos_register_search_shortcode() {

	// Setup a custom array of achievement types
	$achievement_types = array_diff( badgeos_get_achievement_types_slugs(), array( 'step' ) );
	array_unshift( $achievement_types, 'all' );

    // Setup a custom array of achievement tag
    $achievement_tags = get_terms('post_tag', 'fields=names&orderby=name');
	array_unshift( $achievement_tags, 'all' );

	badgeos_register_shortcode( array(
		'name'            => __( 'Search', 'badgeos' ),
		'description'     => __( 'Output a list of achievements.', 'badgeos' ),
		'slug'            => 'badgeos_search',
		'output_callback' => 'badgeos_search_shortcode',
		'attributes'      => array(
			'type' => array(
				'name'        => __( 'Achievement Type(s)', 'badgeos' ),
				'description' => __( 'Single, or comma-separated list of, achievement type(s) to display.', 'badgeos' ),
				'type'        => 'text',
				'values'      => $achievement_types,
				'default'     => 'all',
				),
			'limit' => array(
				'name'        => __( 'Limit', 'badgeos' ),
				'description' => __( 'Number of achievements to display.', 'badgeos' ),
				'type'        => 'text',
				'default'     => 10,
				),
			'orderby' => array(
				'name'        => __( 'Order By', 'badgeos' ),
				'description' => __( 'Parameter to use for sorting.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'menu_order' => __( 'Menu Order', 'badgeos' ),
					'ID'         => __( 'Achievement ID', 'badgeos' ),
					'title'      => __( 'Achievement Title', 'badgeos' ),
					'date'       => __( 'Published Date', 'badgeos' ),
					'modified'   => __( 'Last Modified Date', 'badgeos' ),
					'author'     => __( 'Achievement Author', 'badgeos' ),
					'rand'       => __( 'Random', 'badgeos' ),
					),
				'default'     => 'menu_order',
				),
			'order' => array(
				'name'        => __( 'Order', 'badgeos' ),
				'description' => __( 'Sort order.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array( 'ASC' => __( 'Ascending', 'badgeos' ), 'DESC' => __( 'Descending', 'badgeos' ) ),
				'default'     => 'ASC',
				),
			'wpms' => array(
				'name'        => __( 'Include Multisite Achievements', 'badgeos' ),
				'description' => __( 'Show achievements from all network sites.', 'badgeos' ),
				'type'        => 'select',
				'values'      => array(
					'true'  => __( 'True', 'badgeos' ),
					'false' => __( 'False', 'badgeos' )
					),
				'default'     => 'false',
				),
			'layout' => array(
				'name'        => __( 'Layout', 'badgeos' ),
				'description' => __( 'Achievements layout', 'badgeos' ),
                'type'        => 'select',
                'values'      => array(
                    'grid' => __('Grid', 'badgeos'),
                    'list' => __('List', 'badgeos'),
                    ),
                'default'     => 'list',
 				),
            'tag' => array(
				'name'        => __( 'Achievement Tag(s)', 'badgeos' ),
				'description' => __( 'Single, or comma-separated list of, achievement tag(s) to display.', 'badgeos' ),
				'type'        => 'text',
				'values'      => $achievement_tags,
                'default'     => 'all',
                ),
		),
	) );
}
add_action( 'init', 'badgeos_register_search_shortcode', 11 );

/**
 * Achievement List Shortcode.
 *
 * @since  1.0.0
 *
 * @param  array $atts Shortcode attributes.
 * @return string 	   HTML markup.
 */
function badgeos_search_shortcode( $atts = array () ){

	// check if shortcode has already been run
	if ( isset( $GLOBALS['badgeos_search'] ) )
		return '';

	global $user_ID;
	extract( shortcode_atts( array(
		'type'        => 'all',
		'limit'       => '10',
		'group_id'    => '0',
		'wpms'        => false,
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
		'meta_key'    => '',
		'meta_value'  => '',
        'layout'      => 'list',
		'tag'         => 'all'
	), $atts, 'badgeos_search' ) );

	wp_enqueue_style( 'badgeos-front' );
	wp_enqueue_script( 'badgeos-achievements' );

	$data = array(
		'ajax_url'    => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
		'type'        => $type,
		'limit'       => $limit,
		'group_id'    => $group_id,
		'wpms'        => $wpms,
		'orderby'     => $orderby,
		'order'       => $order,
		'meta_key'    => $meta_key,
		'meta_value'  => $meta_value,
        'layout'      => $layout,
		'tag'         => $tag
	);
	wp_localize_script( 'badgeos-achievements', 'badgeos', $data );

	// If we're dealing with multiple achievement types
	if ( 'all' == $type ) {
		$post_type_plural = __( 'achievements', 'badgeos' );
	} else {
		$types = explode( ',', $type );
		$post_type_plural = ( 1 == count( $types ) && !empty( $types[0] ) ) ? get_post_type_object( $types[0] )->labels->name : __( 'achievements', 'badgeos' );
	}

	$badges = '';

	$badges .= '<div id="badgeos-achievements-filters-wrap">';

	// Search
	$search = isset( $_POST['achievements_list_search'] ) ? $_POST['achievements_list_search'] : '';
	$badges .= '<div id="badgeos-achievements-search">';
		$badges .= '<form id="achievements_list_search_go_form" action="'. get_permalink( get_the_ID() ) .'" method="post">';
		$badges .= sprintf( __( 'Explore par mots-clés : %s', 'badgeos' ), '<input type="text" id="achievements_list_search" name="achievements_list_search" value="'. $search .'">' );
		$badges .= '<input type="submit" id="achievements_list_search_go" name="achievements_list_search_go" value="' . esc_attr__( 'Go', 'badgeos' ) . '">';
		$badges .= '</form>';
	$badges .= '</div>';

	$badges .= '</div><!-- #badgeos-achievements-filters-wrap -->';

	// Content Container
	$badges .= '<div id="badgeos-achievements-container"></div>';

	// Hidden fields and Load More button
	$badges .= '<input type="hidden" id="badgeos_achievements_offset" value="0">';
	$badges .= '<input type="hidden" id="badgeos_achievements_count" value="0">';
	$badges .= '<input type="button" id="achievements_list_load_more" value="' . esc_attr__( 'Load More', 'badgeos' ) . '" style="display:none;">';

	// Reset Post Data
	wp_reset_postdata();

	// Save a global to prohibit multiple shortcodes
	$GLOBALS['badgeos_search'] = true;
	return $badges;

}
