<!--
    RPCS3.net Compatibility List (https://github.com/AniLeo/rpcs3-compatibility)
    Copyright (C) 2017 AniLeo
    https://github.com/AniLeo or ani-leo@outlook.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
-->
<?php 
if(!@include_once(__DIR__.'/../inc.history.php')) throw new Exception("Compat: inc.history.php is missing. Failed to include inc.history.php"); 
?>
<div id='page-con-container'>
	<div id='page-in-container'>
	<!-- featured-con-block -->
		<div id='featured-con-block'>
		
			<!-- featured-wrp-block -->
			<div id='featured-wrp-block' style='padding-bottom:1px'>
				<div id='featured-tx1-block' class='compat-title'>
					<p id='title1'>RPCS3 Compatibility List History</p>
					<?php echo getMenu(true, false, true, true); ?>
				</div>
				<div id='featured-tx2-block' class='compat-desc'>
					<?php echo getHistoryDescription(); ?>
					<br>
					<?php 
						echo getHistoryMonths();
						echo getHistoryOptions();	
					?>	
				</div>
			</div> <!-- featured-wrp-block -->
			
			<div id='compat-con-container'>
				<?php echo getStatusDescriptions(false); ?>
			</div>
		</div> <!-- featured-wrp-block -->
		
		<?php echo getHistoryContent(); ?>
		
		<div id='compat-con-author'>
			<div id='compat-tx1-author'>
				<p>
					<?php echo getFooter($start); ?>
				</p>
			</div>
		</div>
	</div> <!-- featured-con-block -->
</div>