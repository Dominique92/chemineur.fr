if (typeof map !== 'undefined')
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