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
if (isset($get['a'])) { if (!@include_once(__DIR__.'/../includes/inc.panel.php')) throw new Exception("Compat: inc.panel.php is missing. Failed to include inc.panel.php"); }
?>
<div class="page-con-container">
	<div class="page-in-container">

		<div class="container-con-block darkmode-block">
			<div class="container-con-wrapper">

				<div class="container-tx1-block compat-title">
					<p id="title1">Debug Panel</p>
					<?php echo getMenu(true, true, true, true, false); ?>
				</div>

				<div id="debug-left" class="debug-main">
					<ul>
						<li><a href="?a=updateBuildCache">Update Build Cache</a></li>
						<li><a href="?a=updateBuildCacheFull">Update Build Cache (Full)</a></li>
						<li><a href="?a=updateInitialsCache">Update Initials Cache</a></li>
						<li><a href="?a=updateLibraryCache">Update Library Cache</a></li>
						<li><a href="?a=updateStatusModule">Update Status Module</a></li>
						<li><a href="?a=updateRoadmapCache">Update Roadmap Cache</a></li>
						<li><a href="?a=updateCommitCache">Update Commit Cache</a></li>
						<li><a href="?a=updateCountCache">Update Count Cache</a></li>
						<li><a href="?a=compareThreads">Compare Threads</a></li>
						<li><a href="?a=updateCompatibility">Update Compatibility</a></li>
						<li><a href="?a=recacheContributors">Recache Contributors</a></li>
						<li><a href="?a=updateWikiIDsCache">Update Wiki IDs Cache</a></li>
						<!-- <li><a href="?a=getNewTests">Get New Tests</a></li> -->
					</ul>
				</div>

				<div id="debug-right" class="debug-main">
					<form action="?a=generatePassword" method="post">
						<p style="font-size: 12px;">
							<b>Generate secure password:</b>&nbsp;
							<input class="compat-debugpw" type="password" name="pw" size="16" maxlength="32" />
							<br>
						</p>
					</form>
					<?php
						checkInvalidThreads();
						if ($get['a'] == 'compareThreads') {
							compareThreads();
						}
						if ($get['a'] == 'updateCompatibility') {
							compareThreads(true);
						}
						/*
						if ($get['a'] == 'getNewTests') {
							getNewTests();
						}
						*/
					?>
				</div>

			</div>
			<!--
			<div id="compat-hdr-right">
			</div>
			<div id="compat-hdr-left">
			</div>
			-->
		</div>

		<?php if (isset($message)) { echo $message; } ?>

		<?php echo getFooter(); ?>

	</div>
</div>
