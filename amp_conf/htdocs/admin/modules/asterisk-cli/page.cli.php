<?php
/*
 *  Written by Diego Iastrubni <diego.iastrubni@xorcom.com>
 *  Copyright (C) 2005, Xorcom
 *
 *  All rights reserved.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  This code is derived from ASTLinux 0.3, from the file
 *  /var/www/admin/asterisk.php
 *
 *  The original author of AST linux is:
 *  Kristian Kielhofner - KrisCompanies, LLC - http://astlinux.org/
 */
?>

<h2>Asterisk CLI</h2>

<form action="config.php?type=tool&display=cli" method="POST" enctype="multipart/form-data" name="frmExecPlus">
	<table>
		<tr>
			<td class="label" align="right">Command:</td>
			<td class="type"><input name="txtCommand" type="text" size="70" value="<?=htmlspecialchars($_POST['txtCommand']);?>"></td>
		</tr>
		
		<tr>
			<td valign="top">   </td>
			<td valign="top" class="label">
				<input type="submit" class="button" value="Execute">
			</td>
		</tr>
		
		<tr>
			<td height="8"></td>
			<td></td>
		</tr>
	</table>
</form>

<p>
<?php if (isBlank($_POST['txtCommand'])): ?>
</p>
<?php endif; ?>
<?php if ($ulmsg) echo "<p><strong>" . $ulmsg . "</strong></p>\n"; ?>
<?php
function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }

if (!isBlank($_POST['txtCommand']))
{
	echo "<pre>";
	putenv("TERM=vt100");
	putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
	putenv("SCRIPT_FILENAME=" . strtok(stripslashes($_POST['txtCommand']), " "));  /* PHP scripts */
	$ph = popen(stripslashes("asterisk -rx \"" . $_POST['txtCommand'] . "\""), "r" );
	while ($line = fgets($ph))
		echo htmlspecialchars($line);
	pclose($ph);
	echo "</pre>";
}

?>

</div>