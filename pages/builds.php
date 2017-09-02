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
<div id="page-con-container">
	<div id="page-in-container">
		<!--End -->
		<div id='featured-con-block' class="lightmode-block">
			<div id='featured-wrp-block'>
				<div id='featured-tx1-block' class="compat-title">
					<p id='title1'>RPCS3 Builds History <a href='compatibility?b&rss'>(RSS)</a></p>
					<?php echo getMenu(true, true, false, true, true); ?>
				</div>
				<div id='featured-tx2-block' class="compat-desc">
					<p>
						This is the history of all RPCS3 builds made per pull request after AppVeyor artifacts were firstly added to the project.
					</p>
				</div>
			</div>
			<!--
			<div id="compat-hdr-right">
				<p>
					Right
				</p>
			</div>
			<div id="compat-hdr-left">
				<p>
					Left
				</p>
			</div>
			-->
		</div>
		
		<table class='compat-con-container'>
			<?php 
				echo builds_getTableHeaders();
				echo builds_getTableContent(); 
			?>
		</table>
		
		<div id="compat-con-pages">
			<p class="div-pagecounter">
				<?php echo builds_getPagesCounter(); ?>
			</p>
		</div>
		
		<?php echo getFooter($start); ?>
		<!--End -->
	</div>
</div>