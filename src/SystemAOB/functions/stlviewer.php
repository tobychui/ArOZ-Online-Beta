<!DOCTYPE html>
<html lang="en">
	<head>
		<title>STLViewer</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body {
				font-family: Monospace;
				background-color: white;
				margin: 0px;
				overflow: hidden;
			}

			#info {
				color: #fff;
				position: absolute;
				top: 10px;
				width: 100%;
				text-align: center;
				z-index: 100;
				display:block;

			}

			a { color: skyblue }
			.button { background:#999; color:#eee; padding:0.2em 0.5em; cursor:pointer }
			.highlight { background:orange; color:#fff; }

			span {
				display: inline-block;
				width: 60px;
				text-align: center;
			}
			#infotab{
				position:fixed;
				z-index:999;
				right:10px;
				bottom:0px;
				max-width:480px;
				word-break: break-all;
			}
			p{
			    margin:5px !important;
			}
		</style>
	</head>
	<body>
        <?php
        if (isset($_GET['filename']) && $_GET['filename'] != "" && isset($_GET['filepath']) && $_GET['filepath'] != ""){
            $filename = $_GET['filename'];
            $filepath = $_GET['filepath'];
            if (file_exists($filepath) && strpos($filepath,"/meida") !== false){
                //Using absolute path from external storage. Add the handler in front of it
                $filename = "extDiskAccess.php?file=" . $filepath;
			}else if (strpos($filepath,"extDiskAccess.php?file=") !== false){
                //This file already being catched by extDiskAccess. Continue to progress it request
                $filepath = "../" . $filepath;
            }else if (!file_exists($filepath)){
                //This might be paths from AOR. Add relative dots in front and check if it exists or not
                $AOR = "../";
                $filepath = $AOR . $filepath;
                if (!file_exists($filepath)){
                    die("ERROR. File not exists. " . $filepath . " given.");
                }
            }
        }else{
            die("ERROR. Undefined filename or filepath parameter.");
        }
        
        function formatSizeUnits($bytes){
            if ($bytes >= 1073741824)
            {
                $bytes = number_format($bytes / 1073741824, 2) . ' GB';
            }
            elseif ($bytes >= 1048576)
            {
                $bytes = number_format($bytes / 1048576, 2) . ' MB';
            }
            elseif ($bytes >= 1024)
            {
                $bytes = number_format($bytes / 1024, 2) . ' KB';
            }
            elseif ($bytes > 1)
            {
                $bytes = $bytes . ' bytes';
            }
            elseif ($bytes == 1)
            {
                $bytes = $bytes . ' byte';
            }
            else
            {
                $bytes = '0 bytes';
            }
    
            return $bytes;
        }
        ?>
		<script src="../../script/threejs/build/three.js"></script>
		<script src="../../script/jquery.min.js"></script>
		<script src="../../script/threejs/STLLoader.js"></script>
		<script src="../../script/threejs/OrbitControls.js"></script>
		<script src="../../script/threejs/WebGL.js"></script>
		<script src="../../script/threejs/stats.min.js"></script>
		<script src="../../script/ao_module.js"></script>
		<div id="infotab">
			<p id="filename"><?php echo $filename;?></p>
			<p id="filepath"><?php echo $filepath;?></p>
			<p>{model_dimension}</p>
			<p id="filesize"><?php echo formatSizeUnits(filesize($filepath));?></p>
		</div>
		<script>
			ao_module_setWindowTitle("STLviewer - " + $("#filename").text().trim());
			ao_module_setWindowIcon("cube");
			ao_module_setGlassEffectMode();
			var objectSize;
			if ( WEBGL.isWebGLAvailable() === false ) {
				document.body.appendChild( WEBGL.getWebGLErrorMessage() );
			}

			var container, stats;
			var camera, cameraTarget, scene, renderer,controls;
			var filename = $("#filepath").text().trim();
			init();
			animate();
				
			function fillValue(tag,value){
				var newcontent = $("#infotab").html();
				newcontent = newcontent.split("{" + tag + "}").join(value);
				$("#infotab").html(newcontent);
			}
			
			function round(value){
				return Math.round(value * 100) / 100;
			}
			function init() {

				container = document.createElement( 'div' );
				document.body.appendChild( container );

				camera = new THREE.PerspectiveCamera( 35, window.innerWidth / window.innerHeight, 1, 1500 );
				camera.position.set( 0, 0, 30 );
				cameraTarget = new THREE.Vector3( 0, 0, 0 );

				scene = new THREE.Scene();
				scene.background = new THREE.Color( 0xf7f7f7 );
				//scene.fog = new THREE.Fog( 0xc9c9c9, 2, 15 );

				var loader = new THREE.STLLoader();
				loader.load( filename, function ( geometry ) {

					var material = new THREE.MeshPhongMaterial( { color: 0x545454, specular: 0x0c0c0c, shininess: 100 } );
					var mesh = new THREE.Mesh( geometry, material );
					const center = new THREE.Vector3();
					mesh.position.set( 0, 0, 0 );
					mesh.rotation.set( 0,  0, 0);
					mesh.scale.set( 0.1, 0.1, 0.1 );
					
					var box = new THREE.Box3().setFromObject(mesh);
					console.log( box.min, box.max, box.getSize(center) );
					objectSize = box.getSize(center);
					fillValue("model_dimension","W,D,H: " + round(objectSize.x * 10) + ", " + round(objectSize.z * 10) + ", " + round(objectSize.y * 10) + " mm");
					
					var helper = new THREE.Box3Helper( box, 0xffff00 );
					scene.add( helper );
					
					mesh.castShadow = true;
					mesh.receiveShadow = true;
					scene.add( mesh );
				} );


				// Lights
				scene.add( new THREE.HemisphereLight( 0x898989, 0x3f3f3f ) );
				addShadowedLight( 1, 1, 1, 0x898989, 1.35 );
				addShadowedLight( 0.5, 1, - 1, 0xcccccc, 1 );
				// renderer

				renderer = new THREE.WebGLRenderer( { antialias: true } );
				renderer.setPixelRatio( window.devicePixelRatio );
				renderer.setSize( window.innerWidth, window.innerHeight );

				renderer.gammaInput = true;
				renderer.gammaOutput = true;

				renderer.shadowMap.enabled = true;

				container.appendChild( renderer.domElement );

				window.addEventListener( 'resize', onWindowResize, false );
				controls = new THREE.OrbitControls( camera, renderer.domElement );
				controls.minDistance = 3;
				controls.maxDistance = 100;
			}

			function addShadowedLight( x, y, z, color, intensity ) {

				var directionalLight = new THREE.DirectionalLight( color, intensity );
				directionalLight.position.set( x, y, z );
				scene.add( directionalLight );

				directionalLight.castShadow = true;

				var d = 1;
				directionalLight.shadow.camera.left = - d;
				directionalLight.shadow.camera.right = d;
				directionalLight.shadow.camera.top = d;
				directionalLight.shadow.camera.bottom = - d;

				directionalLight.shadow.camera.near = 1;
				directionalLight.shadow.camera.far = 4;

				directionalLight.shadow.mapSize.width = 1024;
				directionalLight.shadow.mapSize.height = 1024;

				directionalLight.shadow.bias = - 0.002;

			}

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

			}

			function animate() {

				requestAnimationFrame( animate );

				render();

			}

			function render() {

				var timer = Date.now() * 0.0005;

				//camera.position.x = Math.cos( timer ) * 3;
				//camera.position.z = Math.sin( timer ) * 3;

				camera.lookAt( cameraTarget );
				renderer.render( scene, camera );

			}
			
			/*
			var fov = camera.fov, zoom = 1.0, inc = -0.05;
			$(document).bind('mousewheel DOMMouseScroll', function(event){
				if (event.originalEvent.wheelDelta > 0 || event.originalEvent.detail < 0) {
					// scroll up --> Zoom in
					if (camera.fov > 1){
						camera.fov = fov * zoom;
						camera.updateProjectionMatrix();
						zoom += inc;
					}else{
						//Cannot be zoomed in anymore!
					}
				}
				else {
					if (camera.fov < 100){
						// scroll down --> Zoom out
						camera.fov = fov * zoom;
						camera.updateProjectionMatrix();
						zoom -= inc;
					}else{
						//Cannot be zoomed out anymore!
					}
					
					
				}
			});
			*/
			
			 
		</script>
	</body>
</html>
