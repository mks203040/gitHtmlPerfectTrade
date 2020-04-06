<?php 
// photo gallery settings
$mainFolder    = 'albums';   // folder where your albums are located - relative to root
$albumsPerPage = '12';       // number of albums per page
$itemsPerPage  = '12';       // number of images per page    
$thumb_width   = '120';      // width of thumbnails
$thumb_height  = '85';         // height of thumbnails
$extensions    = array(".jpg",".png",".gif",".JPG",".PNG",".GIF"); // allowed extensions in photo gallery

// create thumbnails from images
function make_thumb($folder,$src,$dest,$thumb_width) {

	$source_image = imagecreatefromjpeg($folder.'/'.$src);
	$width = imagesx($source_image);
	$height = imagesy($source_image);
	
	$thumb_height = floor($height*($thumb_width/$width));
	
	$virtual_image = imagecreatetruecolor($thumb_width,$thumb_height);
	
	imagecopyresampled($virtual_image,$source_image,0,0,0,0,$thumb_width,$thumb_height,$width,$height);
	
	imagejpeg($virtual_image,$dest,100);
	
}

// display pagination
function print_pagination($numPages,$urlVars,$currentPage) {
        
   if ($numPages > 1) {
      
	   echo 'Page '. $currentPage .' of '. $numPages;
	   echo '&nbsp;&nbsp;&nbsp;';
   
       if ($currentPage > 1) {
	       $prevPage = $currentPage - 1;
	       echo '<a href="?'. $urlVars .'p='. $prevPage.'">&laquo;&laquo;</a> ';
	   }	   
	   
	   for( $e=0; $e < $numPages; $e++ ) {
           $p = $e + 1;
       
	       if ($p == $currentPage) {	    
		       $class = 'current-paginate';
	       } else {
	           $class = 'paginate';
	       } 
	       

		       echo '<a class="'. $class .'" href="?'. $urlVars .'p='. $p .'">'. $p .'</a>';
		  	  
	   }
	   
	   if ($currentPage != $numPages) {
           $nextPage = $currentPage + 1;	
		   echo ' <a href="?'. $urlVars .'p='. $nextPage.'">&raquo;&raquo;</a>';
	   }	  	 
   
   }

}

//[linudaar] method returns caption of an album or the truncated album folder name
function getCaption($album, $mainFolder)
{
	return file_exists($mainFolder.'/'.$album.'/.caption') ? file_get_contents($mainFolder.'/'.$album.'/.caption') : substr($album,0,20);
}

//[linudaar] method returns album description, if exists.
function getDescription($album, $mainFolder)
{
	return file_exists($mainFolder.'/'.$album.'/.description') ? file_get_contents($mainFolder.'/'.$album.'/.description') : "";
}

//[linudaar] method returns image captions, if exists.
function getImageCaptions($album, $mainFolder)
{
	$imageCaptions = array();
	if (file_exists($mainFolder.'/'.$album.'/.image-captions'))
	{	 
		$handle = fopen($mainFolder.'/'.$album.'/.image-captions','r') or die("Failed to load image captions!");
		while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
			$imageCaptions[$row[0]]['short'] = $row[1];
			$imageCaptions[$row[0]]['long'] = $row[2] == null ? $row[1] : $row[2];
		}
		fclose($handle);
	}
	return $imageCaptions;
}

if (!isset($_GET['album'])) {

    // display list of albums
    $folders = scandir($mainFolder, 0);
    $ignore  = array('.', '..', 'thumbs');
		  
	$albums = array();
	$captions = array();
	$random_pics = array();
	  
    foreach($folders as $album) {
         
	    if(!in_array($album, $ignore)) {    
			 
			array_push( $albums, $album );
			
			//[linudaar] added album caption
			$caption = getCaption($album, $mainFolder);
			array_push( $captions, $caption );
			 
			$rand_dirs = glob($mainFolder.'/'.$album.'/thumbs/*.*', GLOB_NOSORT);
			$rand_pic  = $rand_dirs[array_rand($rand_dirs)];
			array_push( $random_pics, $rand_pic );
		  
		 }
		  
	 }

  
     if( count($albums) == 0 ) {
  
        echo 'There are currently no albums.';     
  
     } else {
  
		$numPages = ceil( count($albums) / $albumsPerPage );

        if(isset($_GET['p'])) {
      
	        $currentPage = $_GET['p'];
            if($currentPage > $numPages) {
               $currentPage = $numPages;
            }

         } else {
            $currentPage=1;
         } 
 
         $start = ( $currentPage * $albumsPerPage ) - $albumsPerPage;
		
		//[linudaar] slightly changed the look of the tile
	     echo '<div class="titlebar">
                 <div class="float-left"><span class="title">Albums ('.count($albums).')</span></div>
			     <div class="float-right"></div>
              </div>';
	  
         echo '<div class="clear"></div>';
	  	  			 
	     for( $i=$start; $i<$start + $albumsPerPage; $i++ ) {
	  
	        if( isset($albums[$i]) ) {
			 		 			 
			    echo '<div class="thumb-album">
				        
		                <a href="'.$_SERVER['PHP_SELF'].'?album='. urlencode($albums[$i]) .'">
			              <img src="'. $random_pics[$i] .'" width="'.$thumb_width.'" height="'.$thumb_height.'" alt="" />
						</a>	
					    
						<div class="clear"></div>
					
						<a href="'.$_SERVER['PHP_SELF'].'?album='. urlencode($albums[$i]) .'">
						  <span class="thumb-album-caption">'. $captions[$i] .'</span>
			            </a>
		            
					  </div>';
				  
		     }		  	  

	      }
	  
	      echo '<div class="clear"></div>';
  
          echo '<div align="center" class="paginate-wrapper">';
        	 
                 $urlVars = "";
                 print_pagination($numPages,$urlVars,$currentPage);
  
          echo '</div>';	   
   
     }
   

} else {

     // display photos in album
     $src_folder = $mainFolder.'/'.$_GET['album'];
     $src_files  = scandir($src_folder);

     $files = array();
     foreach($src_files as $file) {
        
		$ext = strrchr($file, '.');
        if(in_array($ext, $extensions)) {
          
		   array_push( $files, $file );
		  
		   
		   if (!is_dir($src_folder.'/thumbs')) {
              mkdir($src_folder.'/thumbs');
              chmod($src_folder.'/thumbs', 0777);
              //chown($src_folder.'/thumbs', 'apache'); 
           }
		   
		   $thumb = $src_folder.'/thumbs/'.$file;
           if (!file_exists($thumb)) {
              make_thumb($src_folder,$file,$thumb,$thumb_width); 
          
		   }
        
		 }
      
	  }
 

   if ( count($files) == 0 ) {

      echo 'There are no photos in this album!';
   
   } else {
   
      $numPages = ceil( count($files) / $itemsPerPage );

      if(isset($_GET['p'])) {
      
	     $currentPage = $_GET['p'];
         if($currentPage > $numPages) {
            $currentPage = $numPages;
         }

      } else {
         $currentPage=1;
      } 

   $start = ( $currentPage * $itemsPerPage ) - $itemsPerPage;
	
	//[linudaar] added album caption and album description to titlebar
	$album = $_GET['album'];
	$caption = getCaption($album, $mainFolder);
	$description = getDescription($album, $mainFolder);
	//[linudaar] try to load image captions
	$imageCaptions = getImageCaptions($album, $mainFolder);
	
   echo '<div class="titlebar">
           <div class="float-left"><span class="title">'. $caption .'</span> - <a href="'.$_SERVER['PHP_SELF'].'">View All Albums</a></div>
           <div class="float-right">'.count($files).' images</div>
		   <div class="clear"></div>
		   <div class="description">'.$description.'</div>
         </div>';
   echo '<div class="clear"></div>';

	
   for( $i=$start; $i<$start + $itemsPerPage; $i++ ) {
		  
		  if( isset($files[$i]) && is_file( $src_folder .'/'. $files[$i] ) ) { 
		    
			//[linudaar] put the thumb into a container that includes the image caption under the image 
			if(isset($imageCaptions[$files[$i]]['long'])) { $longImageCaption = $imageCaptions[$files[$i]]['long']; }
			if(isset($imageCaptions[$files[$i]]['short'])) { $shortImageCaption = $imageCaptions[$files[$i]]['short']; }
			
			echo '<div class="thumb-container">
				  <div class="thumb">
	                <a href="'. $src_folder .'/'. $files[$i] .'" class="albumpix" rel="albumpix" title="'.$longImageCaption.'">
				      <img src="'. $src_folder .'/thumbs/'. $files[$i] .'" width="'.$thumb_width.'" height="'.$thumb_height.'" alt="" />
				    </a>	
			      </div>
				  <div class="thumb-caption">'.$shortImageCaption.'</div>
				  </div>'; 
      
	      } else {
		    if( isset($files[$i]) ) { echo $files[$i]; }
		  }
     
    }
	   

     echo '<div class="clear"></div>';
  
     echo '<div align="center" class="paginate-wrapper">';
        	 
        $urlVars = "album=".urlencode($_GET['album'])."&amp;";
        print_pagination($numPages,$urlVars,$currentPage);
  
     echo '</div>';
	 
	 
   } // end else	 

}
?>	