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
					Compatibility list developed and mantained by <a href='https://github.com/AniLeo' target="_blank">AniLeo</a>
					&nbsp; - &nbsp;
					<?php echo 'Page generated in '.$total_time.' seconds'; ?>
				</p>
			</div>
		</div>
	</div> <!-- featured-con-block -->
</div>
	