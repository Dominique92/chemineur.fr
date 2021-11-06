Dominique92.GeoBB
=================
- GeoBB is a map extension for PhpBB 3.3+ forums associating points, lines or surfaces on a map to a topic in a forum.
- Each forum groups elements of the same category (same icon on the map).
- Each topic or comment in the forum can be represented by an icon, lines or areas on the map.

DEMO
====
Website software of https://alpages.info

DEPENDENCIES
============
* This package includes : https://github.com/Dominique92/MyOl
* This package is tested with : PhpBB 3.3.1

FUNCTIONS
=========
Posts of each forum include a geographic features depending on the forum descriptor
* .line defines lines on the first post only
* :line defines lines on all posts (only one map on the top of the page)
* .poly defines lines on the first post only
* :poly defines lines on all posts (only one map on the top of the page)
* .point defines points on the first post only (the point's icon is the forum image)
* .point define spoints on all posts (only one map on the top of the page)