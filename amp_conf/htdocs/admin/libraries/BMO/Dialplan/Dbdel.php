<?php
namespace FreePBX\Dialplan;
class Dbdel extends Extension{
        function output() {
            return 'Noop(Deleting: '.$this->data.' ${DB_DELETE('.$this->data.')})';
        }
}