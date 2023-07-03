<?php

/*** 
 * in php 8+ version htmlspecialchars() will not accept null argument
 * so this function is replacements to check null values
*/
function freepbx_htmlspecialchars($str)
{
    return htmlspecialchars($str ?? '');
}

/*** 
 * in php 8+ version trim() will not accept null argument
 * so this function is replacements to check null values
*/
function freepbx_trim($str)
{
    return trim($str ?? '');
}
