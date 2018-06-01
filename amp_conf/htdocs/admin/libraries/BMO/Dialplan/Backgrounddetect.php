<?php
namespace FreePBX\Dialplan;
class Backgrounddetect extends Extension{
        var $filename;
        var $silence;
        var $min;
        var $max;
        function __construct($filename,$silence=null,$min=null,$max=null)  {
                $this->filename = $filename;
                $this->silence = $silence;
                $this->min = $min;
                $this->max = $max;
        }
        function output() {
                return 'BackgroundDetect(' .$this->filename.($this->silence ? ',' .$this->silence : '' )
						.($this->min ? ',' .$this->min : '' ).($this->max ? ',' .$this->max : '' ). ')' ;
        }
}