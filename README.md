# ompd-mod
PHP/JS based MPD-web-client which is a fork of ompd.pl which is a fork of netjukebox.pl

codebase is a mess but look&feel is nice

##SUGGESTIONS
###Genral
- omnipresent tiny player with currently played trackinfo in header
- alternative dark theme for whole frontend
- add a browser player (jPlayer?) as an alternative

###Album-View
- "Play Album link" should append to current playlist and play instead of replacing current playlist


###Search-Results-View
- Add a "Play-all" in each section header which replaces or append to current playlist

###Playlists
- save current playlist as a custom playlist
- add tracks or albums or search results to custum playlist


###Architecture
- proper routing through http://www.slimframework.com/
- replace href=javascript:XXX with event-listeners
- implement http://sphinxsearch.com/ as an optional feature to increase search-performance on large collections
- allow assignment of multiple genres/styles to tracks/albums
- tree schemed genre-basis fur genres/subgenres/subsubgenres/styles
- posibility to assign a country to track/album
 

###404 Request
create these files or remove urls from markup/css/js
		favicon.ico
		image/o_lg.png
		skin/ompd_default/img/small_back.png
		skin/ompd_default/img/small_delete.png
		skin/ompd_default/img/small_edit.png
		skin/ompd_default/img/small_hide.png
		skin/ompd_default/img/small_show.png