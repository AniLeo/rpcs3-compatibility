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
if(!@include_once(__DIR__.'/../includes/inc.builds.php')) throw new Exception("Compat: inc.builds.php is missing. Failed to include inc.builds.php");
?>
<div class="page-con-container">
	<div class="page-in-container">
		<!--End -->
		<div class="container-con-block darkmode-block">
			<div class="container-con-wrapper">
				<div class="container-tx1-block compat-title">
					<p id="title1">RPCS3 Builds History <a href="compatibility?b&rss">(RSS)</a></p>
					<?php prof_flag("Page: Get Menu"); ?>
					<?php echo getMenu(true, true, false, true, true); ?>
				</div>
				<div class="container-tx2-block compat-desc">
					<p>
						This is the history of all RPCS3 master builds made per pull request after AppVeyor artifacts were firstly added to the project.
					</p>
				</div>
			</div>
			<div id="builds-hdr-left">
				<p>
					<?php prof_flag("Page: Get Results Per Page"); ?>
					Results per page <?php echo Builds::getResultsPerPage(); ?>
				</p>
			</div>
			<!--
			<div id="compat-hdr-right">
				<p>
					Right
				</p>
			</div>
			-->
		</div>

		<?php
			echo Builds::getTableMessages();
			if (is_null($error)) echo "<div class=\"divTable builds-table\">";

			prof_flag("Page: Display Table Headers");
			echo Builds::getTableHeaders();
			prof_flag("Page: Display Table Content");
			echo Builds::getTableContent();

			if (is_null($error)) echo "</div>";
		?>

		<div id="compat-con-pages">
			<p class="div-pagecounter">
				<?php
					prof_flag("Page: Pages Counter");
					echo Builds::getPagesCounter();
				?>
			</p>
		</div>

		<?php prof_flag("End"); ?>
		<?php echo getFooter($start); ?>
		<!--End -->
	</div>
</div>
