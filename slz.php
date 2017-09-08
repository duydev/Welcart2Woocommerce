<?php

require_once './wp-load.php';
require_once( USCES_PLUGIN_DIR . "/classes/memberList.class.php" );

//$s = new Welcart_Export();
//$d = new Woocommerce_Export();
/*
$d->clean_terms( $d->get_category_taxonomy() );
$d->clean_terms( $d->get_tag_taxonomy() );
$d->import_terms_from_string( $s->export_category_to_string() );
$d->import_terms_from_string( $s->export_tag_to_string() );
*/

//$s->export_user_to_string();



class Welcart_Export {

	public function get_category_taxonomy() {
		return 'category';
	}

	public function get_tag_taxonomy() {
		return 'post_tag';
	}

	public function get_root_term_id() {
		return defined( 'USCES_ITEM_CAT_PARENT_ID' ) ? USCES_ITEM_CAT_PARENT_ID : 0;
	}

	public function _export_terms( $term_id, $taxonomy, &$output = array(), $parent_slug = '' ) {
		$term_slug = '';
		$term      = get_term( $term_id, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			$term_slug = $term->slug;
			$item      = array(
				'taxonomy'    => $taxonomy,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_slug' => $parent_slug,
			);
			$output[]  = $item;
		}

		$child_terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'parent'     => $term_id,
		) );
		if ( $child_terms && ! is_wp_error( $child_terms ) ) {
			foreach ( $child_terms as $child_term ) {
				$this->_export_terms( $child_term->term_id, $taxonomy, $output, $term_slug );
			}
		}
	}

	public function export_category_to_string() {
		$output = array();
		$this->_export_terms( $this->get_root_term_id(), $this->get_category_taxonomy(), $output );

		return json_encode( $output, JSON_PRETTY_PRINT );
	}

	public function export_tag_to_string() {
		$output = array();
		$this->_export_terms( 0, $this->get_tag_taxonomy(), $output );

		return json_encode( $output, JSON_PRETTY_PRINT );
	}

    public function export_user_to_string() {
		global $wpdb;

		$table_user = $wpdb->prefix.'usces_member';
		$query = "SELECT * FROM $table_user";
		$result = $wpdb->get_results( $query, ARRAY_A );
		if( $result && ! is_wp_error( $result ) ) {
			var_dump( $result );
		}
    }

}

class Woocommerce_Export {

	public function get_category_taxonomy() {
		return 'product_cat';
	}

	public function get_tag_taxonomy() {
		return 'product_tag';
	}

	public function import_terms_from_string( $content ) {
		$items = json_decode( $content, true );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$taxonomy = $item['taxonomy'] == 'category' ? $this->get_category_taxonomy() : $this->get_tag_taxonomy();
				wp_insert_term( $item['name'], $taxonomy, array(
					'slug'        => $item['slug'],
					'description' => $item['description'],
					'parent'      => $this->term_slug_to_term_id( $item['parent_slug'], $taxonomy ),
				) );
			}
		}
	}

	public function term_slug_to_term_id( $term_slug, $taxonomy ) {
		$id   = 0;
		$term = get_term_by( 'slug', $term_slug, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			$id = $term->term_id;
		}

		return $id;
	}

	public function clean_terms( $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}
	}

}