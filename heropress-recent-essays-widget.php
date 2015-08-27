<?php
/*
Plugin Name: HeroPress Recent Essays Widget
Description: Creates a widget which shows the recent essays from <a href="http://heropress.com">HeroPress.com</a>.
Author: Topher
Author URI: http://topher1kenobe.com
Version: 1.1
Text Domain: heropress-recent-essays-widget
License: GPL
*/

/**
 * Provides a WordPress widget that renders recent essay from HeroPress.com
 *
 * @package Heropress_Recent_Essays_Widget
 * @since   Heropress_Recent_Essays_Widget 1.0
 * @author  Topher
 */

/**
 * Adds Heropress_Recent_Essays_Widget widget.
 *
 * @class   Heropress_Recent_Essays_Widget
 * @version 1.0.0
 * @since   1.0
 * @package Heropress_Recent_Essays_Widget
 * @author  Topher
 */
class Heropress_Recent_Essays_Widget extends WP_Widget {

	/**
	* Holds the source URL for the data
	*
	* @access private
	* @since  1.0
	* @var    string
	*/
	private $heropress_data_url = null;

	/**
	* Sets the number of items to be pulled from the remote end point
	*
	* @access public
	* @since  1.0
	* @var    object
	*/
	private $heropress_data_limit = null;

	/**
	* Holds the data retrieved from the remote server
	*
	* @access private
	* @since  1.0
	* @var    object
	*/
	private $heropress_data = null;

	/**
	* Heropress_Recent_Essays_Widget Constructor, sets up Widget, gets data
	*
	* @access public
	* @since  1.0
	* @return void
	*/
	public function __construct() {

		//  Build out the widget details
		parent::__construct(
			'heropress-recent-essays-widget',
			__( 'HeroPress Most Recent Essay', 'heropress-recent-essays-widget' ),
			array( 'description' => __( 'Renders recent essays from HeroPress.com.', 'heropress-recent-essays-widget' ), )
		);

		// assign the data source URL
		$this->heropress_data_url = 'http://heropress.com/essays/feed/';

		$this->heropress_data_limit = 5;

	}

	/**
	* Data fetcher
	*
	* Runs at instantiation, gets data from remote server.  Caching built in.
	*
	* @access private
	* @since  1.0
	* @return void
	*/
	private function data_fetcher( $instance ) {

		$rss = fetch_feed( $this->heropress_data_url );

		if ( $instance['heropress-essay-count'] != '' ) {
			$this->heropress_data_limit = $instance['heropress-essay-count'];
		}

		// Checks that the object is created correctly
		if ( ! is_wp_error( $rss ) ) {

			// Figure out how many total items there are, but limit it to 5.
			$maxitems = $rss->get_item_quantity( absint( $this->heropress_data_limit ) );

			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $rss->get_items( 0, $maxitems );

		}

		// store the data in an attribute
		$this->heropress_data = $rss_items;

	}

	/**
	* Data render
	*
	* Parse the data in $this->heropress_data and turn it into HTML for front end rendering
	*
	* @access private
	* @since  1.0
	* @return string
	*/
	private function data_render( $instance = '' ) {

		// go get the data and store it in $this->heropress_data
		$this->data_fetcher( $instance );

		// instantiate $output
		$output = '';

		// see if we have data
		if ( 0 < count( $this->heropress_data ) ) {

			// start an unordered list
			$output .= '<ul>' . "\n";

			// Loop through each feed item and display each item as a hyperlink.
			foreach ( $this->heropress_data as $item ) {

				$author = $item->get_authors();

				$output .= '<li>' . "\n";

				$enclosure = $item->get_enclosure();

				if ( $enclosure != '' && $instance['heropress-show-banner'] == 1 ) {
					// start the link
					$output .= '<a class="heropress_essay_title" href="' . esc_url( $item->get_permalink() ) . '">';

					$output .= '<img src="' . esc_url( $enclosure->get_link() ) . '">' . "\n";

					// end the link
					$output .= '</a>' . "\n";
				}

				if ( $instance['heropress-show-title'] == 1 ) {
					// start the link
					$output .= '<a class="heropress_essay_title" href="' . esc_url( $item->get_permalink() ) . '">';

					// print the news headline
					$output .= esc_html( $item->get_title() ) . "\n";

					// end the link
					$output .= '</a>' . "\n";
				}

				if ( $instance['heropress-show-author'] == 1 ) {
					$output .= '<div class="heropress_contributor">' . $author[0]->name . '</div>';
				}

				if ( $instance['heropress-show-pubdate'] == 1 ) {
					$output .= '<div class="heropress_essay_pubdate"><span class="heropress_essay_pubdate_prefix">' . __( 'Posted', 'heropress-recent-essays-widget' ) . '</span> ' . $item->get_date( 'j F Y' ) . '</div>' . "\n";
				}

				$output .= '</li>' . "\n";

			}

			$output .= '</ul>' . "\n\n";

		}

		return $output;
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see   WP_Widget::widget()
	 *
	 * @param array $args	  Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		// instantiate $output
		$output = '';

		// filter the title
		$title	= apply_filters( 'widget_title', $instance['title'] );

		// go get the news
		$output .= $this->data_render( $instance );

		// echo the widget title
		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}

		// echo the widget content
		echo wp_kses_post( $output );

		// echo the after_widget html
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Back-end widget form.
	 *
	 * @see   WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// check to see if we have a title, and if so, set it
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = '';
		}

		// check to see if we have a count, and if so, set it
		if ( isset( $instance['heropress-essay-count'] ) ) {
			$heropress_essay_count = $instance['heropress-essay-count'];
		} else {
			$heropress_essay_count = '';
		}

		// make the form for the title field in the admin
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'heropress-recent-essays-widget' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<?php
		// make the form for the count in the admin
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'heropress-essay-count' ) ); ?>"><?php _e( 'Show how many:', 'heropress-recent-essays-widget' ); ?></label>
            <select name="<?php echo esc_attr( $this->get_field_name( 'heropress-essay-count' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'heropress-essay-count' ) ); ?>" class="widefat">
				<?php
				$count = 1;
				while ( $count <= 5 ) {
					echo '<option value="' . absint( $count ) . '" id="heropress-count-' . absint( $count ) . '"' . esc_attr( selected( $instance['heropress-essay-count'], $count ) ) .  '>' . absint( $count ) .  '</option>';
					$count++;
				}
				?>
            </select>

		</p>

		<?php
			// set up some defaults
			if ( $instance['heropress-show-banner'] == '' ) {
				$instance['heropress-show-banner'] = 1;
			}

			if ( $instance['heropress-show-title'] == '' ) {
				$instance['heropress-show-title'] = 1;
			}

			if ( $instance['heropress-show-author'] == '' ) {
				$instance['heropress-show-author'] = 1;
			}

			if ( $instance['heropress-show-pubdate'] == '' ) {
				$instance['heropress-show-pubdate'] = 1;
			}
		?>

		<h4><?php _e( 'Show', 'heropress-recent-essays-widget' ); ?>:</h4>
		<ul>
			<li>
				<input id="<?php echo $this->get_field_id( 'heropress-show-banner' ); ?>" name="<?php echo $this->get_field_name( 'heropress-show-banner' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['heropress-show-banner'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'heropress-show-banner' ); ?>"> <?php _e( 'Image', 'heropress-recent-essays-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'heropress-show-title' ); ?>" name="<?php echo $this->get_field_name( 'heropress-show-title' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['heropress-show-title'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'heropress-show-title' ); ?>"> <?php _e( 'Title', 'heropress-recent-essays-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'heropress-show-author' ); ?>" name="<?php echo $this->get_field_name( 'heropress-show-author' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['heropress-show-author'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'heropress-show-author' ); ?>"> <?php _e( 'Author', 'heropress-recent-essays-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'heropress-show-pubdate' ); ?>" name="<?php echo $this->get_field_name( 'heropress-show-pubdate' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['heropress-show-pubdate'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'heropress-show-pubdate' ); ?>"> <?php _e( 'Publish Date', 'heropress-recent-essays-widget' ); ?></label>
			</li>

		</ul>
		
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see    WP_Widget::update()
	 *
	 * @param  array $new_instance Values just sent to be saved.
	 * @param  array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// set up current instance to hold old_instance data
		$instance = $old_instance;

		// set instance to hold new instance data
		$instance['title']                  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['heropress-essay-count']  = absint( $new_instance['heropress-essay-count'] );
		$instance['heropress-show-banner']  = absint( $new_instance['heropress-show-banner'] );
		$instance['heropress-show-title']   = absint( $new_instance['heropress-show-title'] );
		$instance['heropress-show-author']  = absint( $new_instance['heropress-show-author'] );
		$instance['heropress-show-pubdate'] = absint( $new_instance['heropress-show-pubdate'] );

		return $instance;
	}

} // class Heropress_Recent_Essays_Widget


// register Heropress_Recent_Essays_Widget widget
function register_heropress_recent_essays_widget() {
	register_widget( 'Heropress_Recent_Essays_Widget' );
}
add_action( 'widgets_init', 'register_heropress_recent_essays_widget' );
