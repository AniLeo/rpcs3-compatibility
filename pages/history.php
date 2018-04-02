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
$History = new History();
?>
<div class='page-con-container'>
	<div class='page-in-container'>
	<!-- featured-con-block -->
		<div class="featured-con-block darkmode-block">

			<!-- featured-wrp-block -->
			<div class='featured-wrp-block' style='padding-bottom:1px'>
				<div class='featured-tx1-block compat-title'>
					<p id='title1'>RPCS3 Compatibility List History</p>
					<?php echo getMenu(true, false, true, true, true); ?>
				</div>
				<div class='featured-tx2-block compat-desc'>
					<?php echo $History->getHistoryDescription(); ?>
					<br>
					<?php
						echo $History->getHistoryMonths();
						echo $History->getHistoryOptions();
					?>
				</div>
			</div> <!-- featured-wrp-block -->

			<?php
			if (file_exists(__DIR__.'/../modules/mod.status.nocount.php')) {
				include(__DIR__.'/../modules/mod.status.nocount.php');
			} else {
				echo generateStatusModule(false);
			}
			?>
		</div> <!-- featured-wrp-block -->

		<?php echo $History->getHistoryContent(); ?>

		<?php echo getFooter($start); ?>
	</div> <!-- featured-con-block -->
</div>
