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
if(!@include_once(__DIR__.'/../includes/inc.history.php')) throw new Exception("Compat: inc.history.php is missing. Failed to include inc.history.php");
?>
<div class="page-con-container">
	<div class="page-in-container">
		<div class="container-con-block darkmode-block">
			<div class="container-con-wrapper" style="padding-bottom:1px">

				<div class="container-tx1-block compat-title">
					<p id="title1">Compatibility List History</p>
					<?php
						Profiler::addData("Page: Get Menu");
						echo getMenu(__FILE__);
					?>
				</div>

				<div class="container-tx2-block compat-desc">
					<?php
						Profiler::addData("Page: Print Description");
						History::printDescription();

						Profiler::addData("Page: Print Options");
						History::printOptions();

						Profiler::addData("Page: Print Months");
						History::printMonths();
					?>
				</div>

			</div> <!-- container-con-wrapper -->

			<?php
			if (file_exists(__DIR__.'/../modules/mod.status.nocount.php')) {
				Profiler::addData("Page: Get Status Module");
				include(__DIR__.'/../modules/mod.status.nocount.php');
			} else {
				Profiler::addData("Page: Generate Status Module");
				echo generateStatusModule(false);
			}
			?>

		</div> <!-- container-con-block -->

		<?php
			Profiler::addData("Page: Print Content");
			History::printContent();

			Profiler::addData("End");
			echo getFooter();
		?>

	</div> <!-- page-in-container -->
</div> <!-- page-con-container -->
