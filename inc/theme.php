<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package Activist_Network_Theme
 */

/**
 * Count Widgets in Sidebar
 * Used to add classes to widget areas so widgets can be displayed one, two, three or four per row
 * https://gist.github.com/slobodan/6156076
 */
function anp_network_main_count_widgets( $sidebar_id ) {
  // If loading from front page, consult $_wp_sidebars_widgets rather than options to see if wp_convert_widget_settings() has made manipulations in memory.
  global $_wp_sidebars_widgets;

  if ( empty( $_wp_sidebars_widgets ) ) :
    $_wp_sidebars_widgets = get_option( 'sidebars_widgets', array() );
  endif;
  
  $sidebars_widgets_count = $_wp_sidebars_widgets;
  
  if ( isset( $sidebars_widgets_count[ $sidebar_id ] ) ) :

    $widget_count = count( $sidebars_widgets_count[ $sidebar_id ] );
    $widget_classes = 'widget-count-' . count( $sidebars_widgets_count[ $sidebar_id ] );
    if ( $widget_count % 4 == 0 || $widget_count > 6 ) :
      // Four widgets er row if there are exactly four or more than six
      $widget_classes .= ' per-row-4';
    elseif ( $widget_count >= 3 ) :
      // Three widgets per row if there's three or more widgets 
      $widget_classes .= ' per-row-3';
    elseif ( 2 == $widget_count ) :
      // Otherwise show two widgets per row
      $widget_classes .= ' per-row-2';
    endif;

    return $widget_classes;

  endif;

}

/**
 * Custom Event Meta
 */
if( !function_exists( 'anp_get_event_meta_list' ) ) {

  function anp_get_event_meta_list( $event_id = 0 ) {

    $event_id = (int) ( empty( $event_id ) ? get_the_ID() : $event_id);

    if( empty( $event_id ) ){ 
      return false;
    }

    $html  = '<div class="entry-meta event-meta">';
    $venue = get_taxonomy( 'event-venue' );

    if( get_the_terms( $event_id, 'event-category' ) ) {
      $html .= get_the_term_list( $event_id, 'event-category', '<ul class="category event-category"><li>','</li><li>', '</li></ul>' );
    }

    if( get_the_terms( $event_id, 'event-tag' ) && !is_wp_error( get_the_terms( $event_id, 'event-tag' ) ) ) {
      $html .= get_the_term_list( $event_id, 'event-tag', '<ul class="event-tags tags"><li>','</li><li>', '</li></ul>' );
    }

    $html .='</div>';

    return $html;
  }

}

/**
 * BuddyPress Cover Image Callback
 *
 * @see bp_legacy_theme_cover_image() to discover the one used by BP Legacy
 */
function anp_cover_image_callback( $params = array() ) {
    if ( empty( $params ) ) {
        return;
    }
 
    return '
        /* Cover image - Do not forget this part */
        #buddypress #header-cover-image {
            height: ' . $params["height"] . 'px;
            background-image: url(' . $params['cover_image'] . ');
        }
    ';
}

/**
 * BuddyPress Cover Image Callback
 *
 * @see bp_legacy_theme_cover_image() to discover the one used by BP Legacy
 */
function anp_buddypress_cover_image_css( $settings = array() ) {
    /**
     * If you are using a child theme, use bp-child-css
     * as the theme handle
     */
    $theme_handle = 'bp-parent-css';
 
    $settings['theme_handle'] = $theme_handle;
 
    $settings['callback'] = 'anp_cover_image_callback';
     
    return $settings;
}
add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', 'anp_buddypress_cover_image_css', 10, 1 );
add_filter( 'bp_before_groups_cover_image_settings_parse_args', 'anp_buddypress_cover_image_css', 10, 1 );
 

/**
 * Custom Jetpack Support
 */
remove_theme_support( 'jetpack-testimonial' );


/**
 * Event Organiser Modifications
 */
remove_action( 'eventorganiser_additional_event_meta', 'bpeo_add_ical_link_to_eventmeta', 50 );

remove_action( 'eventorganiser_additional_event_meta', 'bpeo_list_connected_groups' );

remove_action( 'eventorganiser_additional_event_meta', 'bpeo_list_author' );

if( function_exists( 'bpeo_get_the_ical_link' ) ) {

  function anp_add_ical_link_to_eventmeta() {
    // do not show for drafts
    if ( 'draft' === get_post( get_the_ID() )->post_status ) {
      return;
    }
  ?>
    <li><?php
      printf(
      __( "%sSave to Calendar%s", 'bp-event-organiser' ),
      '<a class="add-to-calendar button" href="' . bpeo_get_the_ical_link( get_the_ID() ) . '">',
      '</a>'
    ); ?></li>

  <?php
  }
  //add_action( 'eventorganiser_additional_event_meta', 'anp_add_ical_link_to_eventmeta', 50 );

/**
 * Display a list of connected groups on single event pages.
 */
if( function_exists( 'bpeo_get_event_groups' ) ) {
  function anp_list_event_groups() {
    $event_group_ids = bpeo_get_event_groups( get_the_ID() );

    if ( empty( $event_group_ids ) ) {
      return;
    }

    $event_groups = groups_get_groups( array(
      'include' => $event_group_ids,
      'show_hidden' => true, // We roll our own.
    ) );

    $markup = array();
    foreach ( $event_groups['groups'] as $eg ) {
      // Remove groups that the current user should not have access to.
      if ( 'public' !== $eg->status && ! current_user_can( 'bp_moderate' ) && ! groups_is_user_member( bp_current_user_id(), $eg->id ) ) {
        continue;
      }

      $markup[] = sprintf(
        '<a href="%s">%s</a>',
        esc_url( bpeo_get_group_permalink( $eg ) ),
        esc_html( stripslashes( $eg->name ) )
      );
    }

    if ( empty( $markup ) ) {
      return;
    }

    $count = count( $markup );
    $base = _n( '<span class="meta-label">Group</span> %s', '<span class="meta-label">Groups</span> %s', $count, 'anp-network-main' );

    echo sprintf( '<li>' . wp_filter_kses( $base ) . '</li>', implode( ', ', $markup ) );
  }
  add_action( 'eventorganiser_additional_event_meta', 'anp_list_event_groups' );
  }

}

/**
 * Display the event author on single event pages.
 */
if( function_exists( 'bp_core_get_userlink' ) ) {
  function anp_event_author() {
    $event = get_post( get_the_ID() );
    $author_id = $event->post_author;

    $base = __( '%s', 'anp-network-main' );

    echo sprintf( '<div class="entry-author"><span class="meta-label">Author</span> ' . wp_filter_kses( $base ) . '</div>', bp_core_get_userlink( $author_id ) );
  }
  add_action( 'eventorganiser_additional_event_meta', 'anp_event_author' );

}

/**
 * Display BuddyPress Attribution
 */
if( !function_exists( 'anp_buddypress_attribution' ) ) {

  function anp_buddypress_attribution() {

    echo '<div class="buddypress-attribution"><a href="https://buddypress.org/" target="_blank" rel="author">Powered By BuddyPress</a></div>';

  }

  add_action( 'anp_buddypress_main_bottom', 'anp_buddypress_attribution' );

}

/**
 * Display Post Filter on Archive Pages
 * Remove action in order to not display on archive pages
 * @link https://codex.wordpress.org/Function_Reference/remove_action
 * Redeclare `anp_archive_post_filter()` {function} in child theme in order to modify
 */
if( !function_exists( 'anp_archive_post_filter' ) ) {

  function anp_archive_post_filter() {

    if(! is_archive() && ! is_home() ) {

      return;

    } 

    if( is_home() ) {

      anp_taxonomy_filter();

    } elseif( is_post_type_archive( 'event' ) ) {

      anp_taxonomy_filter( 'event-category' );

    } elseif( is_post_type_archive( 'meeting' ) ) {

      anp_taxonomy_filter( 'meeting_type' );

      anp_taxonomy_filter( 'meeting_tag' );

    } elseif( is_post_type_archive( 'network_directory' ) ) {
      
      anp_taxonomy_filter( 'subsite_category' );
      
    } else {

      return;
      
    }

  }

  add_action( 'anp_network_main_page_header_bottom', 'anp_archive_post_filter' );

}


/**
 * Change Default Group Icon
 * @link https://premium.wpmudev.org/blog/how-to-add-a-custom-default-avatar-for-buddypress-members-and-groups/
 * @link https://codex.buddypress.org/themes/guides/customizing-buddypress-avatars/
 */
if( !function_exists( 'anp_buddypress_group_icon' ) ) {

  if( function_exists( 'bp_core_avatar_default' ) ) {

    function anp_buddypress_group_icon( $avatar ) {
      global $bp, $groups_template;

      if( strpos( $avatar,'group-avatars' ) ) {
        return $avatar;
      }
      else {
        $custom_avatar = get_template_directory_uri() .'/dist/images/buddypress-group.png';

        if( $bp->current_action == "" ) {
          return '<img class="avatar" alt="' . attribute_escape( $groups_template->group->name ) . '" src="' . $custom_avatar . '" width="'. BP_AVATAR_THUMB_WIDTH .'" height="'.BP_AVATAR_THUMB_HEIGHT .'" />';
        }
        else {
          return '<img class="avatar" alt="' . attribute_escape( $groups_template->group->name ) . '" src="' . $custom_avatar . '" width="'. BP_AVATAR_FULL_WIDTH .'" height="'. BP_AVATAR_FULL_HEIGHT . '" />';
        }
      }
    }
    add_filter( 'bp_get_group_avatar', 'anp_buddypress_group_icon' );

    /**
     * Change the BuddyPress Avatar size to 100 x 100
     *
     * @link https://codex.buddypress.org/themes/guides/customizing-buddypress-avatars/
     */
    define ( 'BP_AVATAR_THUMB_WIDTH', 100 );
    define ( 'BP_AVATAR_THUMB_HEIGHT', 100 );


  }

}

