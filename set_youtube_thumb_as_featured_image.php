/* 
Instructions:
1. Add this to your theme's functions.php file
2. Add a "Video URL" Url field to your desired post
3. Create a new post, enter and youtube video URL into the field, publish/update the post
Voila! YouTube thumbnail is now Featured Image
*/

//GET VIDEO MEDIA YOUTUBE THUMBNAIL
function bshawp_set_youtube_thumb_as_featured_image( $post_id ) {
	
	$video_url = get_field('video_url',$post_id);
	
  if(!$video_url) return; // Skip if no video_url
	if(has_post_thumbnail( $post_id )) return; //Skip if post thumbnail already set
	
  //Filter video ID out of URL
	preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match);
	$youtube_id = $match[1];
	
	$key = 'your_youtube_api_key';
			
	$video_snippet = wp_remote_get( 'https://www.googleapis.com/youtube/v3/videos/?part=snippet&id='.$youtube_id.'&key='.$key );
	
	if($video_snippet['response']['code'] == '200' ) {
		
		$video_snippet = wp_remote_retrieve_body( $video_snippet );
		$video_snippet = json_decode($video_snippet);
		
		$video_thumbnail = $video_snippet->items[0]->snippet->thumbnails->standard->url;
				
		$image_data = file_get_contents( $video_thumbnail );
		
    //New filename based on post ID
		$filename = 'youtubethumb-'.$post_id . '.jpg';
		
		$upload_dir = wp_upload_dir();
		
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		}
		else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}
		
		file_put_contents( $file, $image_data );
		
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( $filename, null );
		
		
		// Prepare an array of post data for the attachment.
		$attachment = array(
		  'guid' => $upload_dir['url'] . '/' . $filename,
		  'post_mime_type' => $filetype['type'],
		  'post_title' => sanitize_file_name( $filename ),
		  'post_content' => '',
		  'post_status' => 'inherit'
		);
		
		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		
		set_post_thumbnail( $post_id, $attach_id );		
		
	}

}
add_action('acf/save_post', 'bshawp_set_youtube_thumb_as_featured_image', 1);
