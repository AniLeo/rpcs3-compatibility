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

				<div class="container-tx1-block">
					<span class="compat-text">Debug Panel</span>
					<?php echo getMenu(__FILE__); ?>
				</div>

			</div>

			<div class="debug-main">
					<?php
						// Print function list if $a_panel_categories and $a_panel config variable exist
						if (isset($a_panel_categories) && isset($a_panel))
						{
							foreach ($a_panel_categories as $id => $category)
							{
								echo "<div class=\"debug-main-title compat-text\">{$category}</div>";
								echo "<div class=\"debug-main-menu\">";

								foreach ($a_panel as $function => $data)
								{
									if ($data["category"] !== $id)
										continue;

									echo "<div class=\"debug-menu-button\"><a href=\"?a={$function}\">{$data['title']}</a></div>";
								}

								echo "</div>";
							}
						}
					?>

				<div class="debug-main-content">
					<?php
						if (isset($get) && $get['a'] !== 'checkInvalidThreads')
							checkInvalidThreads();
					?>
					<?php runFunctions(); ?>
				</div>
			</div>

		</div>

		<?php echo getFooter(); ?>

	</div>
</div>
