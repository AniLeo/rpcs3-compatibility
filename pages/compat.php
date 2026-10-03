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
if (!@include_once(__DIR__."/../includes/inc.compat.php")) throw new Exception("Compat: Failed to include includes/inc.compat.php");
?>
<div class="page-con-container">
    <div class="page-in-container">
        <div class="container-con-block darkmode-block">
            <div class="container-con-wrapper">

                <div class="container-tx1-block">
                    <span class="compat-text">Compatibility List</span>
                    <?php
                        Profiler::add_data("Page: Get Menu");
                        print(getMenu(__FILE__));
                    ?>
                </div>

                <div class="compat-desc">
                    <p>
                        These are the current compatible games that have been tested with the emulator. This list is subject to change frequently.
                        Be sure to check this page often to follow the latest updates.
                        <br>
                        <span id="jump"></span>
                        Clicking on a game's ID will redirect you to the respective forum thread, clicking the title will redirect you to the respective wiki page.
                        <br>
                        Online-only games and applications on Intro, Loadable and Nothing statuses are listed with a network online-only icon and not part of any game count.
                    </p>
                </div>

            </div> <!-- container-con-wrapper -->

            <?php Profiler::add_data("Page: Print Type Sort"); ?>
            <div class="compat-sort compat-text">
                <span>Application type:</span>
                <?php Compat::printTypeSort(); ?>
            </div>
            <div class="compat-sort compat-text">
                <span>Move support:</span>
                <?php Compat::printMoveSort(); ?>
            </div>
            <div class="compat-sort compat-text">
                <span>Requires network:</span>
                <?php Compat::printNetworkSort(); ?>
            </div>
            <div class="compat-sort compat-text">
                <?php Profiler::add_data("Page: Print Results Per Page"); ?>
                <span>Results per page:</span>
                <?php Compat::printResultsPerPage(); ?>
            </div>
            <div class="compat-hdr-right">
                <?php Profiler::add_data("Page: Print Status Sort"); ?>
                <?php Compat::printStatusSort(); ?>
            </div>

            <?php Profiler::add_data("Page: Print Status Module"); ?>
            <?php Compat::printStatusModule(); ?>	

            <?php Profiler::add_data("Page: Display Searchbox"); ?>
            <div class="compat-con-searchbox">
                <form method="get" id="game-search" action="#jump">
                    <input type="hidden" name="type" value="0"> 
                    <div class="searchbox">
                        <input name="g" type="text" value="<?php if (isset($get['g'])) print(htmlspecialchars($get['g'], ENT_QUOTES, 'UTF-8')); ?>" placeholder="Game Title / Game ID" />
                    </div>
                    <div class="compat-searchbox-div">
                        <button type="submit" form="game-search"></button>
                    </div>
                </form>
            </div>

            <?php
                Profiler::add_data("Page: Print Character Search");
                Compat::printCharSearch();
            ?>

        </div> <!-- container-con-block -->

        <?php
            Profiler::add_data("Page: Print Table");
            Compat::printTable();

            Profiler::add_data("Page: Print Pages Counter");
            Compat::printPagesCounter();

            Profiler::add_data("End");
            print(getFooter());
        ?>

    </div> <!-- page-in-container -->
</div> <!-- page-con-container -->
