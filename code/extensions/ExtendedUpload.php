<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class ExtendedUpload extends Extension {
    
    public function onAfterLoad(){
        // check for existing Method onAfterLoad and run it, if available
        if($this->owner->file->hasMethod('onAfterLoad')) $this->owner->file->onAfterLoad();
    }
}