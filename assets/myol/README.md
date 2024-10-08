Dominique92.myol
================
* This package adds many features to [openlayer maps](https://openlayers.org/)
* It is deployed on [refuges.info](https://www.refuges.info), [chemineur.fr](https://chemineur.fr) & [alpages.info](https://alpages.info)


INSTALL & BUILD
===============
Requires [node.js](https://nodejs.org/) to be installed

* Download the [full code & tools](https://github.com/Dominique92/myol/).


* Go to the myol repository :
```
cd ./myol
```
* Install the required node_modules :
```
npm install
```
* Build the package & generate ./dist/* files :
```
npm run build
```

Simple example
==============
This [Example](https://Dominique92.github.io/myol/examples/) implements a single map with the most current maps layers providers.
* You can download the [DISTRIBUTION ZIPPED PACKAGE](https://github.com/Dominique92/dev/archive/refs/heads/master.zip) and unzip it in your website FTP section.
* You can include the css & js sections of this example on your own page (adjust the include files path to your implementation)

Layer Switcher
==============
Simple layer switcher with base & vector layers.
See a [Layer Switcher example](https://Dominique92.github.io/myol/examples/?sample=layerSwitcher)

Tile layers
===========
Collection of base layers from different vendors.
See a [Tile layers example](https://Dominique92.github.io/myol/examples/?sample=tileLayer)
* OSM, OSM-FR, OpenTopo, CyclOsm, Maps.Refuges.Info
* ThunderForest Outdoors, OpenTopoMap, Cycles, Landscape, Transport, ...
* IGN France, TOP25, cadastre, satellite, ...
* SwissTopo, satellite
* Österreich Kompass
* Great Britain Ordnance Survey
* IDEE España, satellite
* IGM Italie
* Bing Microsoft, satellite
* Google maps, satellite
* Maxar, ArcGIS

Vector layers
=============
Collection of GeoJson ajax layers from different vendors with style, hover & click features.
See a [Vector layer example](https://Dominique92.github.io/myol/examples/?sample=vectorLayer)
* Mountain (refuges.info, pyrenees-refuges.com, camptocamp.org)
* Personal (chemineur.fr, alpages.info)
* OpenstreetMap vector points of interest

Misc controls
=============
Collection of miscellaneous controls and buttons.
See a [Control example](https://Dominique92.github.io/myol/examples/?sample=controls)
* Keep position, zoom & zoom on localStorage
* Geocoder
* GPX upload & download
* Off connexion GPS
* Print map
* Permalink
* Customisable help
* Line length display

Marker display & edit
=====================
Editable position marker with multi-projection position display
See a [Marker example](https://Dominique92.github.io/myol/examples/?sample=marker)

Lines & Polygons editor
=======================
Polylines & polygons editor.
See an [Editor example](https://Dominique92.github.io/myol/examples/?sample=editor)

Off line GPS
============
* Browser -> options -> add to the home screen
* Choose a map layer
* Place yourself at the starting point of your hike
* Zoom to the most detailed level you want to memorize
* Switch to full screen mode (also memorize the higher scales)
* Move along the path of your hike slowly enough to load all tiles
* Repeat with the layers of cards you want to memorize
* Go to the field and click on the "My GPS" icon
* If you have a .gpx file in your mobile, view it by clicking on ⇑
* All tiles viewed once will be kept in the explorer's cache for a few days
* This application does not record your track
* Works well on Android with Chrome, Edge & Samsung Internet, a little less well with Firefox & Safari

Layers keys
===========
If you want to use external providers layers, you must acquire free keys and replace them in the html (see source comment)
* French IGN : Get your own (free) IGN key at [https://geoservices.ign.fr/](https://geoservices.ign.fr/)
* OSM thunderforest : Get your own (free) THUNDERFOREST key at [https://manage.thunderforest.com](https://manage.thunderforest.com)
* Microsoft BING : Get your own (free) BING key at [https://www.microsoft.com](https://www.microsoft.com/en-us/maps/create-a-bing-maps-key)
* England Ordnance Survey : Get your own (free) key at [https://osdatahub.os.uk/](https://osdatahub.os.uk/)
* Austria kompass : Get your own (free) key at [http://www.kompass.de/livemap/](http://www.kompass.de/livemap/)
* Austria kompass : Get your own (free) key at [http://www.kompass.de/livemap/](http://www.kompass.de/livemap/)
* Maxar / Mapbox : Get your own (free) key at https://www.mapbox.com/
* SwissTopo : Register your domain in [https://shop.swisstopo.admin.ch/](https://shop.swisstopo.admin.ch/fr/products/geoservice/swisstopo_geoservices/WMTS_info)

Architecture
============
Just include myol.js & myos.css after ol/dist, proj4js & geocoder's js & css.
* See this [example](https://Dominique92.github.io/myol/examples/)
* Code & all tiled layers use EPSG:3857 spherical mercator projection

The coding rules are volontary simple & don't follow all openlayers's
* No JS classes, no jquery, no es6 modules, no nodejs build, no minification, no npm repository, ...
* Each adaptation is included in a single JS function that you can include separately (check dependencies if any)
* Feel free to use, modify & share as you want

Files
=====
* src/... : Source files
* dist/myol.* : Distribution files, for your website
* examples/... : Examples & visual tests
* gps/... : Off line example with GPS capabilities

Included packages
=================
* openlayers : Map display [Openlayers](https://openlayers.org/download/)
* ol-geocoder : Find a location by name [Geocoder](https://github.com/Dominique92/ol-geocoder/releases/latest)
* proj4 : Coordinate transformation software [Proj4](https://github.com/proj4js/proj4js/releases/latest)

Tested on
=========
* Windows 10 : Edge, FireFox, Chrome, Opera, Brave
* Android (Samsung) : Samsung Internet, FireFox, Chrome, Brave, DuckDuckGo
* Linux : FireFox
