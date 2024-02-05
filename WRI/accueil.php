<!doctype html>
<html lang="fr">
	<head>
		<title>Carte et informations sur les refuges, cabanes et abris de montagne</title>
		<link rel="icon" type="image/svg+xml" href="/images/icones/favicon.svg">
		<meta name="robots" content="all" />
		<meta name="robots" content="index,follow" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />

		<script>
			var mapKeys = {"ign":"sl4yl7uaazsqdjn8lkgzj0ju","thunderforest":"23e2a2c890144e418ea89a5cc0555afe","bing":"ArLngay7TxiroomF7HLEXCS7kTWexf1_1s1qiF7nbTYs2IkD3XLcUnvSlKbGRZxt"};
		</script>

		<link type="text/css" rel="stylesheet" href="/vues/style.css.php" />
		<link type="text/css" rel="stylesheet" href="/MyOl/ol/ol.css" />
		<link type="text/css" rel="stylesheet" href="/MyOl/myol.css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
	</head>

<style>
.menu123 {
	display: flex;
}
.menu123 > li { /* Première ligne */
	margin: 0 auto;
	height: 1em;
	text-align: center;
	z-index: 1;
}
.menu123 > li > p { /* Titre de la première ligne */
	display: inline-block;
	padding: 0.2em;
}
.menu123 p {
	margin: 0;
}
.menu123 > li > ul { /* Menu déroulant */
	padding: 0;
	background: white;
}
.menu123 ul li { /* Toutes les lignes sauf la première */
	height: 1.2em;
	margin: 0;
	padding: 0.1em;
	text-align: left;
}
.menu123 > li > ul > li:hover { /* Ligne du menu déroulant */
	border-top: 1px solid;
	border-bottom: 1px solid;
	background: #ddd;
}
.menu123 > li > ul > li:hover ul {
	border-top: 1px solid;
	border-right: 1px solid;
	background: #ddd;
}
.menu123 ul ul { /* Sous menu à droite */
	position: relative;
	top: -1.3em;
	left: calc(100%);
	padding: 0;
}
.menu123 ul > li > p { /* Ligne du sous-menu */
	max-width: 20vw;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden
}
.menu123 ul ul li { /* Ligne du sous-menu */
	max-width: 20vw;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden
}
.menu123 ul li p:hover,
.menu123 ul li:hover {
	background: #bbb;
}
/* Masque les lignes */
.menu123 > li:not(:hover) > ul,
.menu123 ul > li:not(:hover) > ul {
	position: relative;
	top: -200vh;
}
</style>

<h1>h1</h1>

<ul class="menu123">
	<li><p><a href=".">refuges.info</a></p><li>
	<li><p>Carte</p>
		<ul>
			<li><p>Titre 1 1 055555 055555</p>
				<ul>
					<li>Item 1 1 1</li>
					<li>Item 1 1 2</li>
					<li>Item 1 1 3</li>
				</ul>
			</li>
			<li><p>Titre 2 1</p>
				<ul>
					<li>Item 1 2 1</li>
					<li>Item 1 2 2 5555 5555</li>
					<li>Item 1 2 3</li>
				</ul>
			</li>
		</ul>
	</li>
	<li><p>Nouvelles</p>
		<ul>
			<li><p>Titre 2 1 ***** ---</p></li>
			<li><p>Titre 2 3</p></li>
			<li><p>Titre 2 3</p></li>
		</ul>
	</li>
	<li><p>A propos</p>
		<ul>
			<li><p><a href="formulaire_exportations">Exportation</a></p></li>
			<li><p><a href="wiki/index">Présentation</a></p></li>
			<li><p><a href="">Licence</a></p></li>
			<li><p><a href="wiki/prudence">Prudence</a></p></li>
			<li><p><a href="wiki/qui_est_refuges.info">Qui sommes nous ?</a></p></li>
			<li><p><a href="wiki/liens">Liens</a></p></li>
			<li><p><a href="api/doc">API</a></p></li>
			<li><p><a href="wiki/mentions-legales">Mentions légales</a></p></li>
		</ul>
	</li>
</ul>

<hr/>
xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
			<option>A propos</option>
			<option value="/"></option>
			<option value=""></option>
			<option value="wiki/licence"></option>
			<option value=""></option>
			<option value=""></option>
			<option value=""></option>
			<option value="/"></option>
			<option value="/"></option>
			<option value=""></option>

<style>
.new_boutons {
	display: flex;
	margin: 0.5em;
}
.new_boutons > div,
.new_boutons > p {
	margin: 0 auto;
	font-weight: bold;
}
.new_boutons > select {
}
.new_boutons > p {
	wmargin: 0 auto;
}
.new_boutons > select,
.new_boutons > div,
.new_boutons > p {
	wz-index: 1;
}
</style>

<div class="new_boutons">
	<p><a href="./">refuges.info</a><p>

	<div>
		<select id="new_carte_select" onchange="menuSelectCarte(this)">
		</select>
	</div>

	<div>
		<select>
			<option value="c">Carte</option>
			<option value="352">Alpes</option>
			<option value="5085">Armoricain</option>
			<option class="rr" value="3309">Atlas</option>
			<option value="5087">Baléares</option>
			<option value="4">-&nbsp;Vercors</option>
			<option value="46">Diois</option>
			<option value="18">Ecrins</option>
			<option value="14">Bauges</option>
		</select>
	</div>

	<div>
		<select>
			id="new_news"
			onchange="menuSelect(this)">
			<option value="&type=points,commentaires,forums">Nouvelles</option>
			<option value="&type=refuges">Cabanes & refuges</option>
			<option value="&type=points">Nouveaux points</option>
			<option value="&type=commentaires">Commentaires</option>
			<option value="&type=avec_photo=1&avec_texte">Photos</option>
			<option value="&type=forums&ids_forum=4">Forums des refuges</option>
			<option value="&type=forums&ids_forum=1">Forum La vie du site</option>
			<option value="&type=forums&ids_forum=6">Forum Emplois</option>
			<option value="&type=forums&ids_forum=2">Forum Logiciel</option><!-- //BEST bizare pas de posts -->
			<option value="&type=forums&ids_forum=5">Forum Divers</option>
			<option value="&type=forums">Tous les forums</option>
		</select>
	</div>

	<div>
		<select onchange="menuSelectAPropos(this)">
			<option>A propos</option>
			<option value="formulaire_exportations/">Exportation</option>
			<option value="wiki/index">Présentation</option>
			<option value="wiki/licence">Licence</option>
			<option value="wiki/prudence">Prudence</option>
			<option value="wiki/qui_est_refuges.info">Qui sommes nous ?</option>
			<option value="wiki/liens">Liens</option>
			<option value="api/doc/">API</option>
			<option value="wiki/mentions-legales/">Mentions légales</option>
			<option value="">Gestion</option>
		</select>
	</div>
</div>

<script>
const zones = {
		0: 'Cartes',
		352: 'Alpes',
		351: 'Pyrénées',
	},
	massifs = {
		0: {},
		351: { // Pyrénées
			361: 'Andorre',
			368: 'Bigorre',
			5066: 'Lleida',
		},
		352: { // Alpes
			4: 'Vercors',
			46: 'Diois',
			18: 'Ecrins',
			14: 'Bauges',
		},
	};

function menuSelectCarte(el) {
	const value = el.value,
		divTmp = document.createElement('div');

	// Vide le sélect de toutes ses options
	el.replaceChildren();

	for (const z in zones) {
		divTmp.innerHTML = '<option value="' + z + '"' +
			(z == value ? ' selected="selected"' : '') +
			'>' + zones[z] + '</option>';
		el.appendChild(divTmp.firstChild);

		if (z == value || massifs[z][value]) {
			for (const m in massifs[z]) {
				divTmp.innerHTML = '<option value="' + m + '"' +
					(m == value ? ' selected="selected"' : '') +
					'>&#11177; ' + massifs[z][m] + '</option>';
				el.appendChild(divTmp.firstChild);
			}
		}
	}
}
menuSelectCarte(document.getElementById('new_carte_select')); //BEST protéger contre non trouvé
</script>

<hr/>
xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx


<ul class="menu_accueil">
</ul>



<style>
/*
	.smenu {
	}
	.smenu .smenu_select {
		font-weight: bold;
	}
	.smenu :not(.smenu_select) {
		cursor: pointer;
	}
	.smenu:not(:hover) :not(.smenu_select){
		display: none;
	}
	*/
</style>

<script>
//*DCMM*/{var _r=' ',_v=document.cookie;if(typeof _v=='array'||typeof _v=='object'){for(let _i in _v)if(typeof _v[_i]!='function'&&_v[_i])_r+=_i+'='+typeof _v[_i]+' '+_v[_i]+' '+(_v[_i]&&_v[_i].CLASS_NAME?'('+_v[_i].CLASS_NAME+')':'')+"\n"}else _r+=_v;console.log(_r)}
//*DCMM*/{var _r=' ',_v=location;if(typeof _v=='array'||typeof _v=='object'){for(let _i in _v)if(typeof _v[_i]!='function'&&_v[_i])_r+=_i+'='+typeof _v[_i]+' '+_v[_i]+' '+(_v[_i]&&_v[_i].CLASS_NAME?'('+_v[_i].CLASS_NAME+')':'')+"\n"}else _r+=_v;console.log(_r)}

function menuSelectAPropos(el) {
	location = location.origin+'/'+el.value;

//*DCMM*/{var _r=' ',_v=;if(typeof _v=='array'||typeof _v=='object'){for(let _i in _v)if(typeof _v[_i]!='function'&&_v[_i])_r+=_i+'='+typeof _v[_i]+' '+_v[_i]+' '+(_v[_i]&&_v[_i].CLASS_NAME?'('+_v[_i].CLASS_NAME+')':'')+"\n"}else _r+=_v;console.log(_r)}
}

function menuSelect(el) {
	loadNews(el.value);
/*DCMM*/{var _r=' ',_v=el.value;if(typeof _v=='array'||typeof _v=='object'){for(let _i in _v)if(typeof _v[_i]!='function'&&_v[_i])_r+=_i+'='+typeof _v[_i]+' '+_v[_i]+' '+(_v[_i]&&_v[_i].CLASS_NAME?'('+_v[_i].CLASS_NAME+')':'')+"\n"}else _r+=_v;console.log(_r)}

	// Mem the selection in cookie
	const expires = new Date();
	expires.setTime(expires.getTime() + (7*24*3600*1000));
	document.cookie = el.id + '=' + el.value + ';path=/;expires='+ expires.toUTCString();
}
</script>

<div class="new_page">
	<h1><a href="./">REFUGES.INFO</a></h1>
	<hr/>


<div id="news_detail"></div>

<script>
const newsEl = document.getElementById('news_detail'); // Bloc où on affiche les news
//BEST protéger contre non trouvé
var contributions = []; // Les contributions reçues de l'API contribution

// Tente d'afficher une new à la fin du bloc des news
function loadNextNews() {
	let c;
	do {
		const newsEls = newsEl.childNodes; // Les news déjà affichées
		c = contributions[newsEls.length]; // La contribution à afficher (s'il en reste)

		// La new sera-t-elle visible ?
		let bottomNews = 0;
		if (newsEls.length) {
			const lastEl = newsEls[newsEls.length - 1];

			if (lastEl.offsetTop + lastEl.offsetHeight > // Bas de l'affichage (y compris la partie scrollée)
				window.scrollY + window.innerHeight) // Bas de la fenêtre (y compris la partie scrollée)
				c = null; // On ne l'affichera pas
		}

		if (c) {
/*DCMM*/{var _r= ' = ',_v=Object.keys(c);if(typeof _v=='array'||typeof _v=='object'){for(let _i in _v)if(typeof _v[_i]!='function'&&_v[_i])_r+=_i+'='+typeof _v[_i]+' '+_v[_i]+' '+(_v[_i]&&_v[_i].CLASS_NAME?'('+_v[_i].CLASS_NAME+')':'')+"\n"}else _r+=_v;console.log(_r)}

			// On crée un div pour afficher la new
			const div = document.createElement('div');

			// On popule le div avec les infos reçues de l'API contribution
			div.innerHTML =
				'<hr/><b>' + c.date_formatee + '</b> ' + c.texte +
				'<p>' + (c.remarques || c.commentaire|| '') + '</p>' +
				(c.photo ?
					'<img height="200" src="https://www.refuges.info/photos_points/' +
					c.id_commentaire + '.jpeg" alt="photo miniature">' :
					'');

			// On rattache le div au bloc des news
			newsEl.appendChild(div);
		}
	} while (c);
}

// On tente d'afficher d'autres news si on a scrollé
window.addEventListener('scroll', loadNextNews);

// Affiche les news correspondant aux critères sélectionnés
function loadNews(params) {
	fetch(location.origin + '/api/contributions?' +
			'format_texte=html&nombre=100&avec_texte=1' +
			(typeof params == 'string' ? params : ''))
		.then(res => res.text())
		.then(text => {
			contributions = JSON.parse(text);

			newsEl.replaceChildren();
			loadNextNews();
		});
}

loadNews(); // On affiche une fois à l'init

</script>
