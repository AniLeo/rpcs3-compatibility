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
		<div class="container-con-block darkmode-block">
			<div class="container-con-wrapper">

				<div class="container-tx1-block compat-title">
					<p id="title1">Compatibility List</p>
					<?php
						Profiler::addData("Page: Get Menu");
						echo getMenu(__FILE__);
					?>
				</div>

				<div class="container-tx2-block compat-desc">
					<p>
						These are the current compatible games that have been tested with the emulator. This list is subject to change frequently.
						Be sure to check this page often to follow the latest updates.
					<br>
						Clicking on a game's ID will redirect you to the respective forum thread, clicking the title will redirect you to the respective wiki page.
					</p>
				</div>
			</div>

			<div id="compat-hdr-left">
				<?php Profiler::addData("Page: Print Results Per Page"); ?>
				<p>Results per page <?php Compat::printResultsPerPage(); ?></p>
			</div>
			<div id="compat-hdr-right">
				<?php Profiler::addData("Page: Print Status Sort"); ?>
				<p>Sort By <?php echo Compat::printStatusSort(); ?></p>
			</div>

			<?php
			if (file_exists(__DIR__.'/../cache/mod.status.count.php')) {
				Profiler::addData("Page: Include Status Module");
				include(__DIR__.'/../cache/mod.status.count.php');
			} else {
				Profiler::addData("Page: Generate Status Module");
				echo generateStatusModule();
			}
			?>

			<?php Profiler::addData("Page: Display Searchbox"); ?>
			<div id="compat-con-searchbox">
				<form method="get" id="game-search">
					<div id="searchbox">
						<input id="searchbox-field" name="g" type="text" value="<?php if($get['g'] != "" && $scount[0] > 0) echo $get['g']; ?>" placeholder="Game Title / Game ID" />
					</div>
					<div id="compat-searchbox-div">
						<button id="compat-searchbox-button" type="submit" form="game-search"></button>
					</div>
				</form>
			</div>

			<?php
				Profiler::addData("Page: Print Character Search");
				Compat::printCharSearch();
			?>

		</div> <!-- container-con-block -->

		<?php
			Profiler::addData("Page: Print Messages");
			Compat::printMessages();

			Profiler::addData("Page: Print Table");
			Compat::printTable();

			Profiler::addData("Page: Print Pages Counter");
			Compat::printPagesCounter();

			Profiler::addData("End");
			echo getFooter();
		?>

	</div> <!-- page-in-container -->
</div> <!-- page-con-container -->
