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
<div class="page-con-container">
	<div class="page-in-container">
		<!--End -->
		<div class="featured-con-block darkmode-block">
			<div class='featured-wrp-block'>
				<div class="featured-tx1-block compat-title">
					<p id='title1'>RPCS3 Compatibility List</p>
					<?php prof_flag("Page: Get Menu"); ?>
					<?php echo getMenu(false, true, true, true, true); ?>
				</div>
				<div class='featured-tx2-block compat-desc'>
					<p>
						These are the current compatible games that have been tested with the emulator. This list is subject to change frequently. Be sure to check this page often to follow the latest progressions and possible regressions.
					</p>
				</div>
			</div>
			<!--End -->
			<div id="compat-hdr-left">
				<p>
					<?php prof_flag("Page: Get Results Per Page"); ?>
					Results per page <?php echo Compat::getResultsPerPage(); ?>
				</p>
			</div>
			<div id="compat-hdr-right">
				<p>
					<?php prof_flag("Page: Get Sort By"); ?>
					Sort By <?php echo Compat::getSortBy(); ?>
				</p>
			</div>

			<!--End -->
			<?php
			prof_flag("Page: Get Status Module");
			if (file_exists(__DIR__.'/../cache/mod.status.count.php')) {
				include(__DIR__.'/../cache/mod.status.count.php');
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
				<?php echo Compat::getCharSearch(); ?>
			</table>
			<!--End -->
		</div>

		<?php echo Compat::getTableMessages(); ?>
		<div class="divTable compat-table">
			<?php
				if ($scount[0][0] > 0) {
					prof_flag("Page: Display Table Headers");
					echo Compat::getTableHeaders();
				}
				prof_flag("Page: Display Table Content");
				echo Compat::getTableContent();
			?>
		</div>
		<!--End -->
		<?php prof_flag("Page: Pages Counter"); ?>
		<div id="compat-con-pages">
			<p class="div-pagecounter">
				<?php echo Compat::getPagesCounter(); ?>
			</p>
		</div>
		<?php prof_flag("End"); ?>
		<?php echo getFooter($start); ?>
		<!--End -->
	</div>
</div>
