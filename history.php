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
<div id="page-con-container">
	<div id="page-in-container">
	<!-- featured-con-block -->
		<div id='featured-con-block'>
		
			<!-- featured-wrp-block -->
			<div id='featured-wrp-block' style="padding-bottom:1px">
				<div id='featured-tx1-block' class="compat-title">
					<h2>RPCS3 Compatibility List History</h2>
				</div>
				<!-- <div id="compat-hdr-right">
					<p>
						<a href="?h&rss"><b>RSS Feed</b></a>
					</p>
				</div> -->
				<div id='featured-tx2-block' class="compat-desc">
					<?php echo getHistoryOptions();	?>	
				</div>
			</div> <!-- featured-wrp-block -->
			
			<div id="compat-con-container">
				<?php echo getStatusDescriptions(); ?>
			</div>
		</div> <!-- featured-wrp-block -->
		
		<?php echo getHistory(); ?>
		
		<div id="compat-con-author">
			<div id="compat-tx1-author">
				<p>
					<?php
					// Finish: Microtime after the page loaded
					$finish = getTime();
					$total_time = round(($finish - $start), 4);
					?>
					Compatibility list developed and mantained by <a href='https://github.com/AniLeo' target="_blank">AniLeo</a>
					&nbsp; - &nbsp;
					<?php echo "Page loaded in {$total_time} seconds"; ?>
				</p>
			</div>
		</div>
	</div> <!-- featured-con-block -->
</div>
	