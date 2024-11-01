<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Dejan Markovic
 * Date: 10/22/13
 * Time: 3:25 PM
 * To change this template use File | Settings | File Templates.
 */
/**
 * returns  custom posts
 *
 * @param  $output
 *
 * @return $custom_posts array() of names or objects
 */
function tocp_get_custom_posts( $output, $public = true ) {
	/** The built-in public post types are post, page, and attachment.
	 * By setting '_builtin' to false, we will exclude them and show only the custom public post types.
	 */
	$args = array(
		'public'   => $public,
		'_builtin' => false
	);
	$operator = 'and'; // 'and' or 'or'
	return $custom_posts = get_post_types( $args, $output, $operator );
}

/**
 * returns  post taxonomies
 *
 * @param  $output
 *
 * @return $post_taxonomies array() of names or objects
 */
function tocp_get_post_taxonomies( $custom_posts, $output ) {
	//if there is more than one item than it's array of objects
    $post_taxonomies = array();
	if ( is_array( $custom_posts ) ) {
		foreach ( $custom_posts as $custom_post ) {
			$post_taxonomies[ $custom_post->name ] = get_object_taxonomies( $custom_post->name, $output );
		}
	} //othervise it's a sinlge object
	else {
		$labels = $custom_posts->labels;
		//object name
		$labels_name                     = strtolower( $labels->name );
		$post_taxonomies[ $labels_name ] = get_object_taxonomies( $labels_name, $output );
	}

	return array_filter( $post_taxonomies );
}

/**
 * returns  post labels
 *
 * @param  $output
 *
 * @return $post_labels array() of names or objects
 */
function tocp_get_post_labels( $custom_posts, $output ) {
	//if there is more than one item than it's array of objects
	$post_labels = array();
	if ( is_array( $custom_posts ) ) {
		foreach ( $custom_posts as $custom_post ) {
			$post_labels[ $custom_post->name ] = $custom_post->label;
		}
	} //othervise it's a sinlge object
	else {
		$labels                    = $custom_posts->labels;
		$post_name                 = $custom_posts->name;
		$post_labels[ $post_name ] = $labels->name;
	}

	return array_filter( $post_labels );
}

/**
 * gets category (hierarchical) taxomies
 *
 * @param $post_taxonomies array of post taxonomies
 *
 * @return $category_names array
 */
function tocp_get_category_taxonomies( $post_taxonomies ) {
	//single records are objects(multi records are arrays)
	$category_names ='';
	$count = tocp_countdim( $post_taxonomies );
	if ( $count > 1 ) {
		foreach ( $post_taxonomies as $name => $value ) {
			foreach ( $value as $val ) {
				if ( $val->hierarchical == 1 ) {
					$category_names[ $name ] = $val->name;
				}
			}
		}
	} else {
		foreach ( $post_taxonomies as $name => $value ) {
			if ( $value->hierarchical == 1 ) {
				$category_names[] = $value->name;
			}
		}
	}

	return $category_names;
}

/**
 * gets tag (non-hierarchical) taxomies
 *
 * @param $post_taxonomies array of post taxonomies
 *
 * @return $tag_names array
 */
function tocp_get_tag_taxonomies( $post_taxonomies ) {
	foreach ( $post_taxonomies as $name => $value ) {
		foreach ( $value as $val ) {
			if ( $val->hierarchical != 1 ) {
				$tag_names[] = $val->name;
			}
		}

		return $tag_names;
	}
}

/**
 * separates hierarchical and non-hierarchical taxomies
 *
 * @param $post_taxonomies array of post taxonomies
 *
 * @return $hierarchical_names and $non_hierarchical_names arrays
 */
function tocp_get_taxonomy_checklist( $taxonomies, $omitCustCats ) {
	if ( count( $taxonomies ) == 1 ) {
		$args = array(
			'descendants_and_self' => 0,
			'selected_cats'        => $omitCustCats,
			'popular_cats'         => false,
			'walker'               => null,
			'taxonomy'             => "$taxonomies",
			'checked_ontop'        => false
		);
		wp_terms_checklist( 0, $args );
	} else {
		foreach ( $taxonomies as $taxonomy ) {
			$args = array(
				'descendants_and_self' => 0,
				'selected_cats'        => $omitCustCats,
				'popular_cats'         => false,
				'walker'               => null,
				'taxonomy'             => "$taxonomy",
				'checked_ontop'        => false
			);
			wp_terms_checklist( 0, $args );
		}
	}
}

//associative array implode
function tocp_multi_implode( $array, $glue ) {
	$ret = '';
	foreach ( $array as $item ) {
		if ( is_array( $item ) ) {
			$ret .= tocp_multi_implode( $item, $glue ) . $glue;
		} else {
			$ret .= $item . $glue;
		}
	}
	$ret = substr( $ret, 0, 0 - strlen( $glue ) );

	return $ret;
}

//check if array is associative
function tocp_is_assoc( $array ) {
	return (bool) count( array_filter( array_keys( $array ), 'is_string' ) );
}

//count Array dimensions
function tocp_countdim( $array ) {
	if ( is_array( reset( $array ) ) ) {
		$return = tocp_countdim( reset( $array ) ) + 1;
	} else {
		$return = 1;
	}

	return $return;
}