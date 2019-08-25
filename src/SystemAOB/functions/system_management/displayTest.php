<?php
include_once("../../../auth.php");

?>
<html>
    <head>
        <title>ArOZ Online Display Test</title>
        <link rel="stylesheet" href="../../../script/tocas/tocas.css">
    	<script src="../../../script/tocas/tocas.js"></script>
    	<script src="../../../script/jquery.min.js"></script>
		<style>
			body{
				background-color:#f9f9f9;
			}
			.fs{
				position:fixed;
				left:0px;
				top:0px;
				width:100%;
				height:100%;
				background-color:black;
			}
		</style>
	</head>
	<?php
	if (isset($_GET['mode']) && $_GET['mode'] == "test"){
	?>
	<body>
		<div class="fs" onClick="nextColor(this);">
			
		</div>
		<script>
			var colorList = ["black","red","blue","green","yellow","purple","white"];
			var code = 0;
			function nextColor(object){
				requestFullScreen(object);
				code = code + 1;
				if (code > colorList.length - 1){
					code = 0;
				}
				$(".fs").css("background-color",colorList[code]);
			}
			
			function requestFullScreen(element) {
				// Supports most browsers and their versions.
				var requestMethod = element.requestFullScreen || element.webkitRequestFullScreen || element.mozRequestFullScreen || element.msRequestFullScreen;

				if (requestMethod) { // Native full screen.
					requestMethod.call(element);
				} else if (typeof window.ActiveXObject !== "undefined") { // Older IE.
					var wscript = new ActiveXObject("WScript.Shell");
					if (wscript !== null) {
						wscript.SendKeys("{F11}");
					}
				}
			}

		</script>
	</body>
	<?php
	}else{
	?>
	<body>
	<br><br>
		<div class="ts container">
			<div class="ts raised segment">
				<div class="ts header">
					Display Testing
					<div class="sub header">This is a function for user to quick test their display.</div>
				</div>
				<p>This function will popup a new window. Please enter full screen with the new window and click anywhere on screen to change color.<br>
				Click the button below to start the test.</p>
				<button class="ts primary button" onClick='startTest();'>Start Test</button>
			</div>
		</div>
		<script>
			function startTest(){
				window.open("displayTest.php?mode=test");
			}
		</script>
	</body>
	<?php
	}
	?>
</html>