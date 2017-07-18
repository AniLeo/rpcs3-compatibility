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
<div id="page-con-container">
	<div id="page-in-container">
		
		<div id='featured-con-block'>
			<div id='featured-wrp-block'>
			
				<div id='featured-tx1-block' class="compat-title">
					<p id='title1'>Debug Panel</p>
					<?php echo getMenu(true, true, true, true, false); ?>
				</div>
			
				<div id='featured-tx2-block' class="compat-desc">
					<p style="font-size: 12px;">
						<a href="?a=updateCommitCache">Update Commit Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateBuildCache">Update Build Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateInitialsCache">Update Initials Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateLibraryCache">Update Library Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateThreadsCache">Update Threads Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateRoadmapCache">Update Roadmap Cache</a>
						&nbsp;•&nbsp;
						<a href="?a=updateStatusModuleCount">Update Status Module [Count]</a>
						&nbsp;•&nbsp;
						<a href="?a=updateStatusModuleNoCount">Update Status Module [No Count]</a>
					</p>
					
					<form action="?a=generatePassword" method="post">
						<p style="font-size: 12px;">
							<b>Generate secure password:</b>&nbsp;
							<input class="compat-debugpw" type="password" name="pw" size="16" maxlength="32" />
							<br>
						</p>
					</form>
					
					<?php checkInvalidThreads(); ?>
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

		<?php echo getFooter($start); ?>
		
	</div>
</div>