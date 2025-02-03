# RPCS3's Compatibility List
Source code for [RPCS3.net/compatibility](https://rpcs3.net/compatibility), a small but powerful compatibility list for RPCS3.

## Modules
- **Compat:** The main compatibility list.
- **History:** The history for the compatibility list.
- **Builds:** A build history system for RPCS3's Windows, Linux and macOS builds.
- **Debug:** A control panel to run several verification and update scripts.

## Features
- **Sorting:** Search listed games by status, first character, media type, last test date or by searching the game's Title or ID.
- **Ordering:** Order listed games by Game Title, Status or Last Test date (ASC and DESC).
- **Extra details:** Click the pull request to visit it, the Game ID to visit the forum thread about it, or the Game Title to visit its wiki page.
- **Better searching:** You don't need to search exactly by a game's title to find it. Levenshtein string comparisons assures the closest game is returned when no results are found and initials search allows one to search by games using their initials.
- **History:** See changes made to the games' statuses on a month-to-month basis with the History feature.
- **Builds:** Visit the whole build history since AppVeyor artifacts were added to the project with information about the pull request and its author, added and removed lines of code, build's date, artifact download link, file size and sha-256 hash for checksum purposes.
- **RSS:** Subscribe to the RSS feed to keep track on new game additions, updates on existent ones or new builds information.
- **Forum Sync:** The list is able to be synced with MyBB based forums and automatically updated via the debug control panel after a review on the new submissions is done.
- **Wiki Sync:** The list is able to automatically fetch Mediawiki pages for the respective Game ID entries and link them on the list, as well as fetching game patches from the wiki.

## Requirements
- [PHP 8.2+](https://secure.php.net/downloads.php)
- [PHP PECL YAML Extension 2.2.3+](https://pecl.php.net/package/yaml)
- [libyaml 0.2.5+](https://pyyaml.org/wiki/LibYAML)
- [MySQL 8.2](https://dev.mysql.com/downloads/mysql/8.2.html) or [MariaDB 10.11](https://downloads.mariadb.org/mariadb/)
- [RPCS3.net](https://rpcs3.net) [(Source Code)](https://github.com/DAGINATSUKO/www-rpcs3) - Place files inside lib/compat directory on the main website's source.

## License
This project is licensed under the GNU GPLv2 license. This software may be modified/distributed for commercial or private use but changes to the source code must be published under the same license, containing a copy of the license and a copyright notice.
<br>Developed by [AniLeo](https://github.com/AniLeo) at ani-leo@outlook.com (C) 2017-2025.
