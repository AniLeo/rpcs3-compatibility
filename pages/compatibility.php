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
if (!@include_once(__DIR__.'/../inc.compat.php')) throw new Exception("Compat: inc.compat.php is missing. Failed to include inc.compat.php"); 
?>
<div id="page-con-container">
	<div id="page-in-container">
		<!--End -->
		<div id='featured-con-block'>
			<div id='featured-wrp-block'>
				<div id='featured-tx1-block' class="compat-title">
					<h2>RPCS3 Compatibility List</h2>
				</div>
				<div id='featured-tx2-block' class="compat-desc">
					<p>
						These are the current compatible games that have been tested with the emulator. This list is subject to change frequently. Be sure to check this page often to follow the latest progressions and possible regressions. <br>
						<a href="?h"><b>See compatibility history</b></a> • <a href="?b"><b>See RPCS3 builds history</b></a>
					</p>
				</div>
			</div>
			<!--End -->
			<div id="compat-hdr-right">
				<p>
					Sort By <?php echo getSortBy(); ?>
				</p>
			</div>
			<div id="compat-hdr-left">
				<p>
					Results per page <?php echo getResultsPerPage(); ?>
				</p>
			</div>
			<!--End -->
			<div id="compat-con-container">
				<?php echo getStatusDescriptions(); ?>
			</div>
			<!-- 
			<div style="background: url(https://rpcs3.net/compatbar); background-size: contain; height:30px; background-repeat: no-repeat; background-position: center; padding-top:4px; border-top: 1px solid rgba(0,0,0,.05);">
			</div>
			-->
			<!--End -->
			<div id='compat-con-searchbox'>
				<form method='get' id='game-search'>
					<div id='searchbox'>
						<?php 
							echo '<input id="searchbox-field" style ="background-color: transparent;" name="g" type="text" value="';
							if($get['g'] != "" && $scount[0] > 0) {	echo $get['g'];	} 
							echo '" placeholder="Game Title / Game ID" />'; 
						?>
					</div>
					<div id='submit'>
						<button type='submit' form='game-search' class='div-button'></button>
					</div>
				</form>
			</div>
			<!--End -->
			<table id="compat-con-search">
				<?php echo getCharSearch(); ?>
			</table>
			<!--End -->
		</div>
		
		<table class='compat-con-container'>
			<?php if ($scount[$get['s']] > 0) { echo compat_getTableHeaders(); } echo getTableContent(); ?>
		</table>
		<!--End -->
		<div id="compat-con-pages">
			<p class="div-pagecounter">
				<?php echo compat_getPagesCounter(); ?>
			</p>
		</div>
		<div id="compat-con-author">
			<div id="compat-tx1-author">
				<p>
					<?php echo getFooter($start); ?>
				</p>
			</div>
		</div>
		<!--End -->
	</div>
</div>