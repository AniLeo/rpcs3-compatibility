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
if(!@include_once(__DIR__.'/../includes/inc.library.php')) throw new Exception("Compat: inc.library.php is missing. Failed to include inc.library.php");
?>
<div class="page-con-container">
	<div class="page-in-container">
	<!-- container-con-block -->
		<div class="container-con-block darkmode-block" style="padding-bottom:12px;">

			<!-- container-con-wrapper -->
			<div class="container-con-wrapper">
				<div class="container-tx1-block">
					<p>PS3 Game Library</p>
					<?php
						Profiler::addData("Page: Get Menu");
						echo getMenu(__FILE__);
					?>
				</div>
				<div class="container-tx2-block compat-desc">
					<p>
						The list of the whole PS3's game library known to mankind can be found at <a target='_blank' href='http://www.gametdb.com/PS3/List'>GameTDB</a>.
						<br>

						<?php Profiler::addData("Page: Get Game Counts"); ?>
						There are currently <span style="color: #27ae60;"><b><?php echo Library::getGameCount('all'); ?></b> tested Game IDs (<b><?php echo Library::getGameCount('tested'); ?></b> listed here) </span>
						and <span style="color: #e74c3c;"><b><?php echo Library::getGameCount('untested'); ?></b> untested Game IDs</span>.
						<br>

						<i>Keep in mind this list doesn't have some of the Game IDs tested so far in our compatibility list.</i>
						<br>
						<br>

						<?php Profiler::addData("Page: Generate Filters"); ?>
						Filter by region:
						<?php if ($get['f'] === 'a') { echo '<b>'; } ?>
						<a href='?l&f=a&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>Asia</a>
						<?php if ($get['f'] === 'a') { echo '</b>'; } ?>
						•
						<?php if ($get['f'] === 'e') { echo '<b>'; } ?>
						<a href='?l&f=e&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>Europe</a>
						<?php if ($get['f'] === 'e') { echo '</b>'; } ?>
						•
						<?php if ($get['f'] === 'h') { echo '<b>'; } ?>
						<a href='?l&f=h&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>Hong Kong</a>
						<?php if ($get['f'] === 'h') { echo '</b>'; } ?>
						•
						<?php if ($get['f'] === 'k') { echo '<b>'; } ?>
						<a href='?l&f=k&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>Korea</a>
						<?php if ($get['f'] === 'k') { echo '</b>'; } ?>
						•
						<?php if ($get['f'] === 'j') { echo '<b>'; } ?>
						<a href='?l&f=j&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>Japan</a>
						<?php if ($get['f'] === 'j') { echo '</b>'; } ?>
						•
						<?php if ($get['f'] === 'u') { echo '<b>'; } ?>
						<a href='?l&f=u&<?php echo combinedSearch(false, false, false, false, false, true, false, false); ?>'>USA</a>
						<?php if ($get['f'] === 'u') { echo '</b>'; } ?>
						<br>
						Filter by media:
						<?php if ($get['t'] === 'b') { echo '<b>'; } ?>
						<a href='?l&t=b&<?php echo combinedSearch(false, false, false, false, true, false, false, false); ?>'>Blu-Ray</a>
						<?php if ($get['t'] === 'b') { echo '</b>'; } ?>
						•
						<?php if ($get['t'] === 'n') { echo '<b>'; } ?>
						<a href='?l&t=n&<?php echo combinedSearch(false, false, false, false, true, false, false, false); ?>'>Network (PSN)</a>
						<?php if ($get['t'] === 'n') { echo '</b>'; } ?>
					</p>
				</div>
			</div> <!-- container-con-wrapper -->

			<div id="compat-hdr-left2">
				<p>
					<?php Profiler::addData("Page: Results per Page"); ?>
					Results per page <?php echo Library::getResultsPerPage(); ?>
				</p>
			</div>

		</div> <!-- container-con-wrapper -->

		<?php
			Profiler::addData("Page: Get Tested Contents");
			Library::getTestedContents();
		?>

		<div class="compat-con-pages">
			<p class="div-pagecounter">
				<?php
					Profiler::addData("Page: Get Pages Counter");
					echo Library::getPagesCounter();
				?>
			</p>
		</div>
		<?php
			Profiler::addData("End");
			echo getFooter();
		?>
	</div> <!-- container-con-block -->
</div>
