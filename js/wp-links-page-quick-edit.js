(function($) {


jQuery(document).ready(function($) {


			// we create a copy of the WP inline edit post function
   var $wp_inline_edit = inlineEditPost.edit;

   // and then we overwrite the function with our own code
   inlineEditPost.edit = function( id ) {

      // "call" the original WP edit function
      // we don't want to leave WordPress hanging
      $wp_inline_edit.apply( this, arguments );

      // now we take care of our business

      // get the post ID
      var $post_id = 0;
      if ( typeof( id ) == 'object' )
         $post_id = parseInt( this.getId( id ) );

      if ( $post_id > 0 ) {

         // define the edit row
         var $edit_row = $( '#edit-' + $post_id );

         // get the link display
	 var $wplf_link_display = $( '#wplp_display_' + $post_id ).text();

	 // populate the link display
	 $edit_row.find( 'input[name="wplp_display"]' ).val( $wplf_link_display );

		// get the description
	 var $wplf_description = $( '#wplf_description_' + $post_id ).html();

	 // populate the description
	 $edit_row.find( 'textarea[name="wplf_description"]' ).val( $wplf_description );
	 console.log($wplf_description);


      }

   };





});


})(jQuery);
