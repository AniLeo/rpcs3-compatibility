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
if (!@include_once(__DIR__.'/../includes/inc.compat.php')) throw new Exception("Compat: inc.compat.php is missing. Failed to include inc.compat.php"); 
?>
<div id="page-con-container">
	<div id="page-in-container">
		<!--End -->
		<div id='featured-con-block' class="lightmode-block">
			<div id='featured-wrp-block'>
				<div id='featured-tx1-block' class="compat-title">
					<p id='title1'>RPCS3 Compatibility List</p>
					<?php prof_flag("Page: Get Menu"); ?>
					<?php echo getMenu(false, true, true, true, true); ?>
				</div>
				<div id='featured-tx2-block' class="compat-desc">
					<p>
						These are the current compatible games that have been tested with the emulator. This list is subject to change frequently. Be sure to check this page often to follow the latest progressions and possible regressions.						
					</p>
				</div>
			</div>
			<!--End -->
			<div id="compat-hdr-left">
				<p>
					<?php prof_flag("Page: Get Results Per Page"); ?>
					Results per page <?php echo getResultsPerPage(); ?>
				</p>
			</div>
			<div id="compat-hdr-right">
				<p>
					<?php prof_flag("Page: Get Sort By"); ?>
					Sort By <?php echo getSortBy(); ?>
				</p>
			</div>
			
			<!--End -->
			<?php 
			prof_flag("Page: Get Status Module");
			if (file_exists(__DIR__.'/../modules/mod.status.count.php')) {
				include(__DIR__.'/../modules/mod.status.count.php');
			} else {
				echo generateStatusModule();
			}
			?>
			<!--End -->
			<div id='compat-con-searchbox'>
				<?php prof_flag("Page: Display Searchbox"); ?>
				<form method='get' id='game-search'>
					<div id='searchbox'>
						<?php 
							echo '<input id="searchbox-field" style ="background-color: transparent;" name="g" type="text" value="';
							if($get['g'] != "" && $scount[0] > 0) {	echo $get['g'];	} 
							echo '" placeholder="Game Title / Game ID" />'; 
						?>
					</div>
					<div id='compat-searchbox-div'>
						<button id='compat-searchbox-button' type='submit' form='game-search'></button>
					</div>
				</form>
			</div>
			<!--End -->
			<table id="compat-con-search">
				<?php prof_flag("Page: Get Character Search"); ?>
				<?php echo getCharSearch(); ?>
			</table>
			<!--End -->
		</div>
		
		<table class='compat-con-container'>
			<?php 
				if ($scount[0][0] > 0) { 
					prof_flag("Page: Display Table Headers");
					echo compat_getTableHeaders(); 
				} 
				prof_flag("Page: Display Table Content");
				echo getTableContent(); 
			?>
		</table>
		<!--End -->
		<?php prof_flag("Page: Pages Counter"); ?>
		<div id="compat-con-pages">
			<p class="div-pagecounter">
				<?php echo compat_getPagesCounter(); ?>
			</p>
		</div>
		<?php prof_flag("End"); ?>
		<?php echo getFooter($start); ?>
		<!--End -->
	</div>
</div>