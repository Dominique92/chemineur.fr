// Force au moins une couche chemineur
//TODO edit trace sans fond de trace
if (!localStorage.myol_selectchem && scriptName == 'index')
	localStorage.myol_selectchem = 'all';

// Activate the layer corresponding to the topic
if (typeof topic_category == 'string') {
	const ls = (localStorage.myol_selectchem || '').split(',');
	ls.push(topic_category);
	localStorage.myol_selectchem = [...new Set(ls)];
}

if (typeof map !== 'undefined')
	// Generate a key unique on the last 12 hours
	//TODO ? const version = (localStorage.lastPostingDate % 43200).toString(36);
	[new myol.layer.vector.WRI({
			selectName: 'select-wri',
		}),
		new myol.layer.vector.PRC({
			selectName: 'select-prc',
		}),
		new myol.layer.vector.C2C({
			selectName: 'select-c2c',
		}),
		new myol.layer.vector.Overpass({
			selectName: 'select-osm',
		}),
		new myol.layer.vector.Alpages({
			selectName: 'select-alpages',
		}),
	].forEach(l => map.addLayer(l));

if (document.URL.includes('posting'))
	localStorage.lastPostingDate = Math.floor(Date.now() / 1000); // In seconds epoch

// Resize map
if (jQuery.ui)
	$(map.getTargetElement()).resizable({
		handles: 's,w,sw', // 2 sides and 1 corner

		resize: function(event, ui) {
			ui.position.left = ui.originalPosition.left; // Reste à droite de la page
			map.updateSize(); // Repost all new <div>
		},
	});