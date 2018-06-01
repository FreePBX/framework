<?php
namespace FreePBX\Dialplan;
class Speechdtmfterminator  extends Extension{
        var $digits;
        function __construct($terminator)  {
                $this->terminator = $terminator;
        }

        function output()  {
                return "Set(SPEECH_DTMF_TERMINATOR=".$this->terminator.")";
        }
}