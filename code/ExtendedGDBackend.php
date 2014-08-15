<?php
 
/**
 * extended_image/code/ExtendedGD.php
 * 
 * Can merge Images with Backgroundimages
 * 
 */
 
class ExtendedGDBackend extends DataExtension {
    
    /**
     * Merge two Images together
     */
    public function merge(GDBackend $image){
        
        imagealphablending($this->owner->getImageResource(), false);
	imagesavealpha($this->owner->getImageResource(), true);
        
        imagealphablending($image->getImageResource(), false);
	imagesavealpha($image->getImageResource(), true);
        
        $srcX = 0;
        $srcY = 0;
        $srcW = $image->getWidth();
        $srcH = $image->getHeight();
        $dstX = round(($this->owner->getWidth() - $srcW)/2);
        $dstY = round(($this->owner->getHeight() - $srcH)/2);
        $dstW = $image->getWidth();
        $dstH = $image->getHeight();
        
        imagecopyresampled($this->owner->getImageResource(), $image->getImageResource(), $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
        
        $output = clone $this->owner;
	$output->setImageResource($this->owner->getImageResource());
	return $output;
    }
    
    /**
     * blur the image
     */
    public function blur($intensity) {
        $image = $this->owner->getImageResource();
        
        switch($intensity){
            case 'light':
                for ($x=1; $x<=10; $x++)
                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            break;
            
            case 'strong':
                for ($x=1; $x<=40; $x++)
                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            break;
            
            case 'normal':
            default:
                for ($x=1; $x<=25; $x++)
                    imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
            break;
        }
        
        $output = clone $this->owner;
	$output->setImageResource($image);
	return $output;
    }
}