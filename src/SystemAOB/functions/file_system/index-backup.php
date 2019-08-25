<?php
include '../../../auth.php';
?>
<!DOCTYPE html>
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=0.6, maximum-scale=0.6"/>
<html>
<head>
	<meta charset="UTF-8">
	<script type='text/javascript' charset='utf-8'>
		// Hides mobile browser's address bar when page is done loading.
		  window.addEventListener('load', function(e) {
			setTimeout(function() { window.scrollTo(0, 1); }, 1);
		  }, false);
	</script>
    <link href="../../../script/tocas/tocas.css" rel='stylesheet'>
	<script src="../../../script/jquery.min.js"></script>
    <title>AOB File Explorer</title>
    <style type="text/css">
        body {
            padding-top: 2em;
            background-color: rgb(250, 250, 250);
            overflow: scroll;
        }
    </style>
</head>
<body>
	<?php
	$mode = "folder";
	$permissionLevel = 0;
	$dir = "";
	$moduleName = "";
	$returnPath = "";
	$embedded = false;
	$filename = "unknown";
	//PHP Script for modifying editing modes
	function mv($var){
		if (isset($_GET[$var]) !== false && $_GET[$var] != ""){
			return $_GET[$var];
		}else{
			return null;
		}
	}
	
	//1. Select File or Folder Mode
	//File mode can only modify filesize
	//Directory mode can modify folder as well as files
	if (mv("mode") != null){
		$mode = mv("mode");
		if ($mode != "file" && $mode != "folder"){
			die("ERROR. Mode only support 'file' or 'folder'. ");
		}
		if ($mode == "file"){
			if (mv("filename") != null){
				$filename = mv("filename");
			}else{
				die("ERROR. File Mode require 'filename' variable.");
			}
		}
	}else{
		//Continue with file mode
	}
	
	
	//2. Allow functions of copy & paste, cut & paste, delete, move
	//Read only: Lv 0
	//Read and Write: Lv 1
	//Read, Write (Move) and Delete Lv 2
	if (mv("controlLv") != null){
		$clv = mv("controlLv");
		if ($clv < 0 || $clv > 2){
			die("ERROR. Unknown Control Level Setting ('controlLv' error)");
		}
		$permissionLevel = $clv;
	}else{
		//Continue with read only mode
	}
	
	
	//3. Select Starting Directory Path
	if (mv("dir") != null){
		$edir = "../../../" . mv("dir");
		$requireDir = mv("dir");
		if (file_exists($edir) == false){
			//This might be a realpath filepath. Check if it is real or not.
			list($scriptPath) = get_included_files();
			$relativePath = getRelativePath(realpath($scriptPath),mv("dir"));
			if (file_exists(mv("dir")) == false){
				die("ERROR. dir '$relativePath' not exists.");
			}
			$dir = $relativePath;
			
		}else{
			$dir = $edir;
		}
		
	}else{
		$dir = ".";
		//Continue with current functional directory
	}
	
	//4. Identify module name
	if (mv("moduleName") != null){
		$mn = mv("moduleName");
		if ($mn == null || file_exists("../../../" . $mn) == false){
			die("ERROR. Module not exists. Leave empty for non-modular operation but permission level will be set to READ ONLY.");
		}
		$moduleName = $mn;
	}else{
		//Continue with current functional directory and Read Only Mode
		$moduleName = ".";
		
	}
	//5. Check if the dir is inside of the module. If not, reject access
	if (strpos(realpath($dir),realpath("../../../" . $moduleName)) !== False){
		//This path is inside of the installed module
		
	}else{
		//This path is not inside of the module, reject connections
		if ($mode != "file"){
			//Bypass file as files might be placed outside / located under an uncertained module
			die("ERROR. You don't have permission to access that file.");
		}else if ($mode == "file" && substr(str_replace("//","",str_replace("../","",$dir)),0,6) == "media/"){
			//Only allow access to files under /media/storage1 , /media/storage2 or other self mounted drives
		}else{
			die("ERROR. You don't have permission to access that file.");
		}
		
	}
	
	//6. (Optional) Finishing Path, when operation finish, return to this path. Use "embedded" if require no return path
	if (mv("finishing") != null){
		$returnPath = mv("finishing");
	}else{
		//If no return path, try to return to the module
		if ($moduleName != ""){
			$returnPath = "../../../" . $moduleName;
		}else{
			$returnPath = "../../../index.php";
		}
	}
	
	//7. Not allow exit or redirect, when using as integrated / full embedded mode, this can be helpful
	if (mv("integrated") != null){
		$embedded = mv("integrated");
		if ($embedded == "true"){
			$embedded = true;
		}else if ($embedded == "false"){
			$embedded = false;
		}else{
			$embedded = false;
		}
	}else{
		$embedded = false;
	}
	
	
	?>
    <div class="ts container">
        <!-- Menu -->
        <div class="ts breadcrumb">
            <a id="returnSC" href="<?php echo $returnPath;?>" class="section">ArOZβ Files</a>
            <div class="divider">/</div>
            <a id="moduleName" href="" class="section"><?php
			if ($moduleName == ""){
				echo 'Unknown Module (READ ONLY)';
			}elseif ($moduleName == "."){
				echo "Admin @ Root" . "(Mode: $permissionLevel)";
			}else{
				echo $moduleName . "(Mode: $permissionLevel)";
			}
			?></a>
            <div class="divider">/</div>
            <div class="active section">
                <i class="folder icon"></i><?php 
				if ($dir == "."){
					if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
						echo realPath("../../../") . "\\";
					}else{
						echo realPath("../../../") . "/";
					}
				}else{
					if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
						echo realpath($dir) . '\\';
					}else{
						//Not windows. There will be a lot of ../ if accessing external storage
						//This code is used to remove those dots if external storage
						if (strpos($dir,"../../../../") !== false){
							//It is outside of the web root
							echo str_replace("../../../../","",realpath($dir)) . '/';
						}else{
							//It is inside the web root
							echo realpath($dir) . '/';
						}
					}
				}
				
				?>
            </div>
        </div>

        <br><br>

        <div class="ts grid">
            <div id="fileViewPanel" class="eleven wide column" <?php
			if ($embedded){
				echo 'style="width:100%;"';
			}
			?>>
				<div id="sortedFolderList" class="ts selection segmented list">
				<div id="controls" class="item">
					<?php
					if ($permissionLevel >= 0){
						echo '<button id="backBtn" class="ts labeled mini icon disabled button" onClick="backClicked();" style="display:">
								<i class="reply icon"></i>
									Back
							  </button>
							  <button class="ts labeled mini icon button" onClick="openClicked();">
								<i class="folder open outline icon"></i>
									Open
							  </button>
							  <button id="downloadbtn" class="ts labeled mini icon button" onClick="downloadFile();">
								<i class="download icon"></i>
									Download
							  </button>';
					}
					if($permissionLevel >= 1){
						echo '<button id="copybtn" onClick="copy();" class="ts labeled mini icon button">
							<i class="copy icon"></i>
								Copy
							</button>
							<button id="move" onClick="paste();" class="ts labeled mini icon button">
							<i class="paste icon"></i>
							Paste
							</button>
							<button id="newfolder" class="ts labeled mini icon button" onClick="newFolder();">
								<i class="folder outline icon"></i>
								New Folder
							</button>
							<button id="upload" class="ts labeled mini icon button" onClick="prepareUpload();">
								<i class="upload icon"></i>
								Upload
							</button>
							';
					}
					if($permissionLevel >= 2){
						echo '
						<button class="ts labeled mini icon button" onClick="cut();">
							<i class="cut icon"></i>
							Cut
						</button>
						<button class="ts labeled mini icon button" onClick="rename();">
							<i class="text cursor icon"></i>
							Rename
						</button>
						<button id="nameConvert" class="ts labeled mini icon button" onClick="convertFileName();">
							<i class="exchange outline icon"></i>
							Filename Convert
						</button>
						<button id="delete" class="ts labeled mini icon button" onClick="ConfirmDelete();">
							<i class="trash outline icon"></i>
							Delete
						</button>';
					}
					?>	
                    </div>
				</div>
                <div id="sortedFileList" class="ts selection segmented list">
				<!-- Function Bar for file management-->
				<br><br><br><br><br><br>
					    <div class="ts active inverted dimmer">
							<div class="ts text loader">Loading...</div>
						</div>
                </div>
            </div>

            <div id="sideControlPanel" class="five wide column" style="position:fixed;right:5px;">
                <div class="ts card">
                    <div class="secondary very padded extra content">
                        <div id="fileicon" class="ts icon header">
                            <i class="file outline icon"></i>
                        </div>
                    </div>

                    <div class="extra content">
                        <div id="filename" class="header">No selected file</div>
                    </div>

                    <div class="extra content">
                        <div class="ts list">
                            <div class="item">
                                <i class="folder outline icon"></i>
                                <div class="content">
                                    <div class="header">Full Path</div>
                                    <div id="thisFilePath" class="description">N/A</div>
                                </div>
                            </div>

                            <div class="item">
                                <i class="file outline icon"></i>
                                <div class="content">
                                    <div class="header">File Size</div>
                                    <div id="thisFileSize" class="description">N/A</div>
                                </div>
                            </div>

                            <div class="item">
                                <i class="code icon"></i>
                                <div class="content">
                                    <div class="header">MD5</div>
                                    <div id="thisFileMD5" class="description">N/A</div>
                                </div>
                            </div>
                        </div>
						<?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
							//This section of the script only runs on Linux, debian Jessie to be more accurate
							echo '<div id="scl" class="ts horizontal divider">Shortcuts</div>';
							echo '<select id="shortCutMenu" class="ts basic fluid dropdown">';
							echo '<option onClick="ChangeCurrentDirectory($(this).text());">Internal Storage</option>';
							//Since the 17-7-2018 version, unlimited storage directory is supported
							$storages = glob("/media/*");
							foreach ($storages as $storage){
								echo '<option onClick="ChangeCurrentDirectory($(this).text());">' . basename($storage) . "</option>";
							}
							echo '</select>';
						}
						?>
						<br><br>
						<a href="<?php echo $returnPath;?>" class="ts basic primary fluid button hideEM">Done</a>
                    </div>
                </div>
                <div class="ts horizontal right floated middoted link list">
                    <a class="item" onClick="UpdateFileList(currentPath);">Refresh</a>
                    <a href="<?php echo $returnPath;?>" class="item hideEM">Cancel</a>
                    <a href="" class="item">ArOZβ File Explorer</a>
                </div>
            </div>
        </div>
    </div>
	<!-- Sorting Buffer -->
	<div id="folderList" style="background-color:#b1cdf9;display:none;">
		
	</div>
	<div id="fileList" style="background-color:#b1efb7;display:none;">
		
	</div>
	<!-- Notice Box-->
	<div id="noticeCell" class="ts active bottom right snackbar" style="display:none;">
		<div id="noticeContent" class="content">
			Loading...
		</div>
	</div>
	
	<!-- Delete Confirm Box -->
	<dialog id="delConfirm" class="ts fullscreen modal" style="position:fixed;top:10%;left:30%;width:40%;max-height:70%">
		<h5 class="ts fluid header" style="color:#ff778e;">
			<div class="content" style="color:#ff778e;">
				<i class="trash icon"></i>Delete Confirm
				<div class="sub header" style="color:#ff778e;">This file will be removed. This action CANNOT BE UNDONE.</div>
			</div>
		</h5>
		<div class="content" style="width:100%;height:200px;overflow-y:scroll;overflow-wrap: break-word;">
			<p id="dname">Loading...</p>
			<p id="drname" >Loading...</p>
			<p id="dfpath">Loading...</p>
		</div>
		<div class="actions">
			<button class="ts deny basic button" onClick="$('#delConfirm').fadeOut('fast');deleteConfirmInProgress = false;">
				Cancel
			</button>
			<button class="ts negative basic button" onClick="deleteFile();">
				Confirm
			</button>
		</div>
	</dialog>
	
	<!-- New Folder Option -->
	<div id="newFolderWindow" class="ts primary raised segment" style="position:fixed; top:10%;left:30%; right:30%;display:none;">
		<h5 class="ts header">
			<i class="folder outline icon"></i>
			<div class="content">
				New Folder
				<div class="sub header">Filename must only contain Alphabets, Numbers and Space.<br> Please tick the "Encoded Foldername" option for other special characters.</div>
			</div>
		</h5>
		<div class="ts container">
			<div class="ts checkbox">
				<input type="checkbox" id="efcb">
				<label for="efcb">Encoded Foldername (Foldername will be stored in hex format for system encoding compatibility)</label>
			</div><br><br>
			<div class="ts fluid input">
				<input id="newfoldername" type="text" placeholder="New Folder Name">
			</div><br><br>
			<button class="ts right floated positive basic button" onClick="CreateNewFolder();">Confirm</button>
			<button class="ts right floated negative basic button" onClick="$('#newFolderWindow').fadeOut('fast');enableHotKeys=true;">Cancel</button>
		</div>
	</div>
	
	<!-- Rename File Option -->
	<div id="renameFileWindow" class="ts primary raised segment" style="position:fixed; top:10%;left:20%; right:20%;display:none;">
		<h5 class="ts header">
			<i class="file outline icon" id="renameIcon"></i>
			<div class="content" id="renameTitle">
				Rename File
				<div class="sub header">Filename must only contain Alphabets, Numbers and Space.<br> Please tick the "Encoded Filename" option for other special characters.</div>
			</div>
		</h5>
		<div class="ts container">
			<div class="ts checkbox">
				<input type="checkbox" id="efcbr">
				<label for="efcbr">Encoded Filename (Filename will be stored in hex format for system encoding compatibility)</label>
			</div><br>
			<label><mark>Changing file / folder naming format within modules may results in module error or system failure.</mark></label>
			<br><br>
			<label>Original Filename</label>
			<div class="ts fluid input">
				
				<input id="oldRenameFileName" type="text" placeholder="Original Filename" readonly>
			</div><br><br>
			<label>New Filename</label>
			<div class="ts fluid input">
				<input id="renameFileName" type="text" placeholder="New File / Folder Name">
			</div><br><br>
			<button class="ts right floated positive basic button" onClick="confirmRename();">Confirm</button>
			<button class="ts right floated negative basic button" onClick="$('#renameFileWindow').fadeOut('fast');enableHotKeys=true;">Cancel</button>
		</div>
	</div>
	
	<!-- Upload New Files Window-->
	<div id="uploadFileWindow" class="ts primary raised segment" style="position:fixed; top:10%;left:20%; right:20%;display:none;">
		<h5 class="ts header">
			<i class="upload icon"></i>
			<div class="content">
				Upload Files
				<div class="sub header">All uploaded files will be in hex encoded format following the Upload Manager FIle Naming (UMFN) format.</div>
			</div>
		</h5>
		<div class="ts container">
			<p id="msg"></p>
			<div class="ts form">
			<div class="field">
			<label>Uplaod Target</label>
				<input type="text" id="uploadTarget" class="ts fluid input" name="utarget" value="" readonly>
			</div>
			<div class="field">
			<label>Selected Files</label>
			<input type="file" id="multiFiles" name="files[]" multiple="multiple"/>
			</div>
			<div class="ts mini buttons">
				<button class="ts basic negative button" onClick="closeUploadWindow();$('#uploadFileWindow').fadeOut('fast');">Cancel</button>
				<button class="ts basic button" onClick="previewUplaodFileList();">Preview File List</button>
				<button class="ts basic positive button" id="uploadFilesBtn">Upload</button>
			</div>
			</div>
			<div id="ulFileList" class="ts segment" style="display:none;">
			<h5>Upload Pending File List</h5>
			<div id="ulFileListItems" class="ts ordered list">
			</div>
			</div>
		</div>
	</div>
	
	<script>
	//AOB File Management System Alpha
	//This file management system is like Windows Explorer, user can do whatever they want.
	//Use with care if your module is using this explorer and remind user of the risk of system damage.
	var controlsTemplate = "";
	var PermissionMode = <?php echo $permissionLevel;?>;
	var startingPath = "<?php echo $dir;?>";
	var webRoot = "<?php echo $_SERVER['DOCUMENT_ROOT'];?>";
	var currentPath = startingPath;
	var homedir = startingPath;
	var lastClicked = -1;
	var globalFilePath = [];
	var dirs = [];
	var files = [];
	var zipping = 0; //Check the number if zipping in progress
	var uploading = 0;//Check if it is uploading.
	var clipboard = ""; //Use for copy and paste
	var ctrlDown = false; //Use for Ctrl C and Ctrl V in copy and paste of files
	var deletePendingFile = "";//Delete Pending file, delete while delete confirm is true
	var deleteConfirmInProgress = false; // Record if delete confirm is in progress, then bind to suitable key press
	var hexFolderName = false; //New folder naming method 
	var newFolderPath = currentPath;//The directory where the new folder will be created
	var isFunctionBar = !(!parent.isFunctionBar); //Check if currently in embedded mode
	var finishingPath = "<?php echo $returnPath;?>";
	var enableHotKeys = true;
	var multiSelectMode = false; //Check if multi-selecting
	var cutting = false;//Ctrl-X, Not much to explains :)
	var ExternalStorage = false; //Use extDiskAccess.php for accessing the resources
	var renamingFolderID = -1; //Hold the renaming folder id when under renaming operation
	var prepareUplaodPath = ""; //Hold the temperary folder path for upload when the user press on the upload button
	var embeddedMode = <?php echo $embedded ? 'true' : 'false';?>;
	var viewMode = "<?php echo $mode;?>";
	var filename = "<?php echo $filename;?>";
	
	//Clone the file controls into js after the page loaded
	$( document ).ready(function() {
		
		if (viewMode == "file"){
			//Opening a given file path
			OpenFileFromRealPath(startingPath,filename);
			if (isFunctionBar){
				window.location.href="../killProcess.php";
			}
			
		}

		controlsTemplate = $('#controls').html();
		if (startingPath == "."){
			//Launching from no variables at all
			startingPath = "../../../.";
			currentPath = startingPath;
		}
		UpdateFileList(startingPath);
		
		if ($("#efcb").is(":checked") == true){
			hexFolderName = true;
			$('#newfoldername').css('background-color','#caf9d1');
		}
		
		if (isFunctionBar && finishingPath == "embedded"){
			//Remove all unecessary items if the window is in embedded mode
			$('#returnSC').attr('href','');
			$('.hideEM').hide();
		}
		
		if (currentPath.includes("../../../../../../..") == true){
			//Hide all shortcut as it is in /media/* directory
			//$('#sd1').hide(); 
			//$('#sd2').hide(); 
			//$('#eusb').hide(); 
			//$('#isd').hide(); 
			//$('#scl').hide(); //scl must appears as this section of code is only used in linux system
			
			//Updated to unlimited storage selection menu
			$("#shortCutMenu").hide(); 
		}else{
			//InitiateShorcuts();
		}
		
		if (embeddedMode){
			//This windows is open under iframe / embedded mode. Hide all return path and side bar
			$("#returnSC").remove();
			$("#sideControlPanel").hide();
			$("#fileViewPanel").removeClass("eleven").addClass("twelve");
		}
		
		//Reset the drop down menu of current directory as some mobile browser might change its value when init
		$('#shortCutMenu').val('Internal Storage');
	});
	
	$("#shortCutMenu").change(function () {
        var text = this.value;
        ChangeCurrentDirectory(text);
    });
	
	
	$(document).keydown(function(e) {
        if (e.keyCode == 17 || e.keyCode == 91) ctrlDown = true;
		if (enableHotKeys == false){return;}
		if (e.keyCode == 67){
			//Key C
			if (ctrlDown == true){
				//Ctrl + C is pressed
				copy();
			}
		}else if (e.keyCode == 86){
			//Key V
			if (ctrlDown == true){
				//Ctrl + V is pressed
				paste();
			}
		}else if (e.keyCode == 88){
			//Key X
			if (ctrlDown == true){
				//Ctrl + X is pressed
				cut();
			}
		}else if (e.keyCode == 46){
			//Delete Button
			ConfirmDelete();
		}else if (e.keyCode == 27 && deleteConfirmInProgress == true){
			//ESC pressed, Cancel Delete
			$('#delConfirm').fadeOut('fast');
			deleteConfirmInProgress = false;
		}else if (e.keyCode == 13 && deleteConfirmInProgress == true){
			//Enter pressed, Confirm Delete
			deleteFile();
		}
		
	}).keyup(function(e) {
        if (e.keyCode == 17 || e.keyCode == 91) ctrlDown = false;
		
	});
	
	function ChangeCurrentDirectory(name){
		if (name == "Internal Storage"){
			JumpToDir('');
		}else{
			JumpToDir('/media/' + name,true);
		}
	}
	
	/* Deprecated function
	function InitiateShorcuts(){
		//No longer usable since 17-7-2018 update (Replaced by the unlimited storage unit implementation)
		//PLEASE DO NOT USE THIS SECTION OF CODE, Thanks :)
		$.get( "file_exists.php?file=/media/storage1", function( data ) {
		  if (data.includes("DONE") && data.includes("TRUE")){
			$('#sd1').show();
		  }else{
			 $('#sd1').hide(); 
		  }
		});
		$.get( "file_exists.php?file=/media/storage2", function( data ) {
		  if (data.includes("DONE") && data.includes("TRUE")){
			 $('#sd2').show();
		  }else{
			 $('#sd2').hide(); 
		  }
		});
		$.get( "file_exists.php?file=/media/pi", function( data ) {
		  if (data.includes("DONE") && data.includes("TRUE")){
			 $('#eusb').show();
		  }else{
			 $('#eusb').hide(); 
		  }
		});
		$.get( "file_exists.php?file=/var/www/html/AOB", function( data ) {
		  if (data.includes("DONE") && data.includes("TRUE")){
			  $('#isd').show();
		  }else{
			 $('#isd').hide(); 
		  }
		});
	}
	*/
	
	function backClicked(){
		ParentDir();
		if (currentPath == startingPath){
			$('#backBtn').addClass("disabled");
		}else{
			$('#backBtn').removeClass("disabled");
		}
	}
	
	
	function JumpToDir(directory,extStorage=false){
		if (directory != ""){
			startingPath = directory;
			currentPath = directory;
			UpdateFileList(currentPath);
		}else{
			startingPath = homedir;
			if (startingPath == "."){
				//Launching from no variables at all
				startingPath = "../../../.";
				currentPath = startingPath;
			}
			UpdateFileList(currentPath);
		}
		ExternalStorage = extStorage;
	}
	function AppendControls(){
		//Append the controls back to the Filelist after reloading the filelist
		$('#sortedFolderList').append('<div id="controls" class="item">' + controlsTemplate + '</div>');
	}
	
	function LoadingErrorTest(){
		if ($('#sortedFileList').html().includes("<br><br><br><br><br><br><div")){
			//Something went wrong while loading the page
			$('#sortedFileList').html('<br><br><br><br><br><br><div class="ts active inverted dimmer"><div class="ts text loader">Seems something went wrong.<br>Try <a href="">refreshing</a> the page?</div></div>');
		}
	}
	
	function SortFolder(){
		$('#sortedFolderList').html("");
		AppendControls();
		//This function was added to sort the ids from buffer zone to corrisponding divs
		for (var i =-2 ; i < dirs.length; i++){
			if (checkIdExists(i) == true){
				$('#' + i ).clone().appendTo('#sortedFolderList');
			}
		}
	}
	
	function SortFiles(){
		$('#sortedFileList').html("");
		//AppendControls();
		//This function was added to sort the ids from buffer zone to corrisponding divs
		for (var i =dirs.length ; i < globalFilePath.length; i++){
			if (checkIdExists(i) == true){
				$('#' + i ).clone().appendTo('#sortedFileList');
			}
		}
	}
	
	function ClearSortBuffer(){
		$('#sortedFolderList').html("");
		$('#sortedFileList').html("");
	}
	
	function checkIdExists(id){
		if ($("#" + id).length == 0){
			return false;
		}else{
			return true;
		}
	}
	function UpdateFileList(directory){
		ClearSortBuffer();
		setTimeout(LoadingErrorTest,15000);
		$('#sortedFileList').html('<br><br><br><br><br><br><div class="ts active inverted dimmer"><div class="ts text loader">Loading...</div></div>');
		$('#folderList').html("");
		lastClicked = -1;
		$.ajax({
			url:"listdir.php?dir=" + directory,  
			success:function(data) {
				//console.log(data);
				PhraseFileList(data); 
			}
		  });
	}
	
	function PhraseFileList(json){
		$('#fileList').html("");
		$('#folderList').html("");
		globalFilePath = [];
		AppendControls();
		dirs = json[0];
		files = json[1];
		var templatef = '<div id="%NUM%" class="item" ondblclick="openFolder(%NUM%);" onClick="ItemClick(%NUM%);" style="overflow: hidden;"><i class="folder outline icon"></i>%FILENAME%</div>';
		var template = '<div id="%NUM%" class="item" ondblclick="openClicked();" onClick="ItemClick(%NUM%);" style="overflow: hidden;"><i class="%ICON% icon"></i>%FILENAME%</div>';
		var totalCount = 0;
		if (currentPath != startingPath){
			if (currentPath.includes("../../../../../../..")){
				//The directory is outside the web root.
				$('#folderList').append('<div id="-1" class="item" ondblclick="ParentDir();" style="overflow: hidden;"><i class="folder outline icon"></i>' + currentPath.replace("../../../../../../../","External_Storage >/") +'</div>');
			}else{
				//The directory is inside the web root
				$('#folderList').append('<div id="-1" class="item" ondblclick="ParentDir();" style="overflow: hidden;"><i class="folder outline icon"></i>' + currentPath.replace("../../","") +'</div>');
			}
		}
		SortFolder();
		for(var i = 0; i < dirs.length;i++){
			//Append all the folders into the list
			var dirname = dirs[i].replace(currentPath + "/","");
			AppendHexFolderName(dirname,totalCount,templatef);
			globalFilePath[totalCount] = dirs[i];
			totalCount++;
			/*
			$('#fileList').append(templatef.replace("%NUM%",totalCount).replace("%NUM%",totalCount).replace("%NUM%",totalCount).replace("%FILENAME%",dirname));
			totalCount++;
			*/
		}
		for(var i = 0; i < files.length;i++){
			//Append all the files into the list
			var filename = files[i].replace(currentPath + "/","");
			var ext = GetFileExt(filename);
			var fileicon = GetFileIcon(ext);
			var thistemplate = template.replace("%ICON%",fileicon);
			if (filename.substring(0, 5) == "inith"){
				//This is a file with encoded filename
				AppendUMFileName(filename,totalCount,thistemplate);
				globalFilePath[totalCount] = files[i];
				totalCount++;
			}else{
				//This is not a file uploaded with UM
				$('#fileList').append(thistemplate.replace("%NUM%",totalCount).replace("%NUM%",totalCount).replace("%FILENAME%",filename));
				globalFilePath[totalCount] = files[i];
				totalCount++;
				SortFiles();
			}
			
		}
		SortFiles();
		ToggleBackBtn();
	}

	function ToggleBackBtn(){
		if (currentPath == startingPath){
			$('#backBtn').addClass("disabled");
		}else{
			$('#backBtn').removeClass("disabled");
		}
	}
	
	function AppendUMFileName(rawname,id,template){
		$.get( "um_filename_decoder.php?filename=" + rawname, function( data ) {
		  $('#fileList').append(template.replace("%NUM%",id).replace("%NUM%",id).replace("%FILENAME%",data));
		  $('#' + id).css("background-color","#d8f0ff");
		  SortFiles();
		  ToggleBackBtn();
		});
	}
	
	
	function AppendHexFolderName(rawname,id,template){
		$.get( "hex_foldername_decoder.php?dir=" + rawname, function( data ) {
			$('#folderList').append(template.replace("%NUM%",id).replace("%NUM%",id).replace("%NUM%",id).replace("%FILENAME%",data));
		  if (data == rawname){
			  //The file isn't encoded into hex
		  }else{
			 $('#' + id).css("background-color","#caf9d1"); 
		  }
		  SortFolder();
		  ToggleBackBtn();
		});
	}
	
	function GetFileIcon(ext){
		if (ext == "txt"){
			return "file text outline";
		}else if (ext == "pdf"){
			return "file pdf outline";
		}else if (ext == "png" || ext == "jpg" || ext == "psd" || ext == "jpeg" || ext == "ttf" || ext == "gif"){
			return "file image outline";
		}else if (ext == "7z" || ext == "zip" || ext == "rar" || ext == "tar"){
			return "file archive outline";
		}else if (ext == "flac" || ext == "mp3" || ext == "aac" || ext == "wav"){
			return "file audio outline";
		}else if (ext == "mp4" || ext == "avi" || ext == "mov" || ext == "webm"){
			return "file video outline";
		}else if (ext == "php" || ext == "html" || ext == "exe" || ext == "js"){
			return "file code outline";
		}else if (ext == "db"){
			return "file";
		}else if (ext.substring(0,1) == "/"){
			return "folder open outline";
		}else{
			return "file outline";
		}
	}
	function GetFileExt(filename){
		return filename.split('.').pop();
	}
	
	function ItemClick(num){
		//What to do when the user click on a file
		if (ctrlDown == false){
			if (multiSelectMode == true){
				//Clear all the previous selected items
				for (var k =0; k < lastClicked.length;k++){
					$('#'+lastClicked[k]).removeClass("active");
				}
				lastClicked = -1;
				multiSelectMode = false;
			}
			//Select a single file / folder only
			$('#'+lastClicked).removeClass("active");
			$('#'+num).addClass("active");
			$('#thisFilePath').html(rtrp(globalFilePath[num]));
			var ext = GetFileExt(globalFilePath[num]);
			var fileicon = GetFileIcon(ext);
			if (fileicon == "file image outline" && ext != "psd"){
				if (ExternalStorage){
					$('#fileicon').html('<img class="ts small rounded image" src="../extDiskAccess.php?file=/'+globalFilePath[num]+'">');
				}else if (currentPath.includes("../../../../")){
					$('#fileicon').html('<img class="ts small rounded image" src="../extDiskAccess.php?file=/'+globalFilePath[num]+'">');
				}else{
					$('#fileicon').html('<img class="ts small rounded image" src="'+globalFilePath[num]+'">');
				}
			}else{
				$('#fileicon').html('<i class="'+ fileicon +' icon"></i>');
			}
			$('#filename').html($('#' + num).html());
			getMD5(globalFilePath[num]);
			getFilesize(globalFilePath[num]);
			lastClicked = num;
			
			//Check if it is a file or folder. Change the buttons if needed
			if (lastClicked == -1){
				//Something gone wrong :(
			}else if(lastClicked < dirs.length){
				//The user clicked on a folder
				//Change download button to zip and download
				$('#downloadbtn').html('<i class="zip icon"></i>Zip&Down');
			}else{
				//The user clicked on a file
				//Change download button to download
				$('#downloadbtn').html('<i class="download icon"></i>Download');
			}
		}else{
			//Performing multi-selection
			if (multiSelectMode == false){
				//Start a new multi select mode
				multiSelectMode = true;
				var tmp = lastClicked;
				lastClicked = [];
				lastClicked.push(tmp)
			}
			lastClicked.push(num);
			$('#'+num).addClass("active");
			
		}
	}
	
	function ShowMultSelectMenu(bool){
		if (bool == true){
			//Use multi selection menu
		}else{
			//Use normal menu
		}
	}
	
	function getMD5(filepath){
		$.get("md5.php?file=" + filepath, function( data ) {
		  $('#thisFileMD5').html(data);
		});
	}
	
	function getFilesize(filepath){
		$('#thisFileSize').html("Calculating...");
		$.get("filesize.php?file=" + filepath, function( data ) {
		  $('#thisFileSize').html(data);
		});
	}
	
	
	function rtrp(path){
		return path.replace("../../../","");
	}
	function ParentDir(){
		var tmp = currentPath.split("/");
		tmp.pop();
		currentPath = tmp.join('/');
		UpdateFileList(currentPath);
	}
	
	
	//Buttons interface handlers
	function openClicked(){
		if (lastClicked != -1){
			if (lastClicked < dirs.length){
				//The user click to open a folder
				currentPath = globalFilePath[lastClicked];
				if (currentPath.includes(startingPath)){
					UpdateFileList(currentPath);
				}
			}else{
				OpenFileFromRealPath(globalFilePath[lastClicked],$('#' + lastClicked).html().split('</i>').pop().replace("</div>"));
			}
		}
	}
	
	function OpenFileFromRealPath(realPath,filename){
		if (isFunctionBar){ //&& finishingPath == "embedded"
			//The user click to open a file in function bar mode
			var file = realPath.replace("../../","");
			if (file.includes("../../../../../")){
				file = htmlEncode(file);
				file = file.replace("../../../../../","../SystemAOB/functions/extDiskAccess.php?file=/");
			}else if (ExternalStorage == true){
				file = htmlEncode(file);
				file = "../SystemAOB/functions/extDiskAccess.php?file=" + file;
			}
			var ext = GetFileExt(file);
			ext = ext.toLowerCase();
			if (ext == "mp3" || ext == "wav" || ext == "aac" || ext == "flac"){
				//Open with Audio module
				LaunchUsingEmbbededFloatWindow('Audio',file,filename,'music','audioEmbedded',640,170,undefined,undefined,false);
			}else if (ext == "mp4" || ext == "webm"){
				//Open with Video Module
				LaunchUsingEmbbededFloatWindow('Video',file,filename,'video','videoEmbedded',720,480);
			}else if (ext == "php" || ext == "html"){
				window.open("../../" + file); 
			}else if (ext == "pdf"){
				//Opening pdf with browser build in pdf viewer
				//parent.newEmbededWindow(file,'PDF Viewer','file pdf outline','pdfViewerEmbedded');
				window.open("../../" + file); 
			}else if (ext == "png" || ext == "jpg" || ext == "gif" || ext == "jpeg"){
				//Opening png with browser build in image viewer
				//window.open("../../" + file); 
				//parent.newEmbededWindow(file.replace("../",""),filename,'file image outline','imgViewer');
				LaunchUsingEmbbededFloatWindow('Photo',file,filename,'file image outline','imgViewer',720,480,undefined,undefined,undefined,true);
			}else if (ext == "txt" || ext == "md"){
				LaunchUsingEmbbededFloatWindow('Document',file,filename,'file text outline','textView');
			}else{
				//Update on 7-8-2018
				//if the file extension is not found in the list above, search for already installed webApps for launching
				
			}
			
		}else{
			//The user click to open a file in stand alone mode
			var file = realPath.replace("../../","");
			if (file.includes("../../../../../")){
				file = file.replace("../../../../../","../SystemAOB/functions/extDiskAccess.php?file=/");
			}else if (ExternalStorage == true){
				file = "../SystemAOB/functions/extDiskAccess.php?file=" + file;
			}
			var ext = GetFileExt(file);
			console.log(ext);
			if (ext == "mp3"){
				//Open with Audio module
				window.location.href=("../../../Audio/?share=" + file + "&display=" + filename + "&id=-1 "); 
			}else if (ext == "mp4"){
				//Open with Video Module
				window.location.href=("../../../Video/vidPlay.php?src=" + file); 
			}else if (ext == "php" || ext == "html"){
				window.location.href=("../../" + file); 
			}else if (ext == "pdf"){
				//Opening pdf with browser build in pdf viewer
				window.location.href=("../../" + file); 
			}else if (ext == "png" || ext == "jpg" || ext == "gif"){
				//Opening png with browser build in image viewer
				window.location.href=("../../" + file); 
			}else if (ext == "txt" || ext == "md"){
				window.location.href=("../../" + file);
			}
		}
	}
	
	
	
	function LaunchUsingEmbbededFloatWindow(moduleName, file, filename, iconTag, uid, ww=undefined, wh=undefined,posx=undefined,posy=undefined,resizable=true ){
		var url = moduleName + "/embedded.php?filepath=" + file + "&filename=" + filename;
		parent.newEmbededWindow(url,filename,iconTag,uid,ww,wh,posx,posy,resizable);
	}
	
	function downloadFile(){
		if (lastClicked != -1){
			if (lastClicked < dirs.length){
				//The user want to download a folder
				var file = globalFilePath[lastClicked];
				var filename = $('#' + lastClicked).html().split('</i>').pop().replace("</div>");
				ShowNotice("<i class='caution circle icon'></i>File zipping may take a while...");
				zipping += 1;
					$.get( "zipFolder.php?folder=" + file + "&foldername=" + filename, function(data) {
					  if (data.includes("ERROR") == false){
						  //The zipping suceed.
						  ShowNotice("<i class='checkmark icon'></i>The zip file is now ready.");
						  window.open("download.php?file_request=" + "export/" + data + "&filename=" + data); 
						  zipping -=1 ;
					  }else{
						  //The zipping failed.
						  ShowNotice("<i class='checkmark icon'></i>Folder zipping failed.");
						  zipping -=1 ;
					  }
					});
			}else{
				//The user want to download a file
				var file = globalFilePath[lastClicked];
				var filename = $('#' + lastClicked).html().split('</i>').pop().replace("</div>");
				var ext = GetFileExt(file);
				if (ext == "php" || ext == "js"){
					ShowNotice("<i class='caution sign icon'></i>ERROR! System script cannot be downloaded.");
				}else{
					window.open("download.php?file_request=" + file + "&filename=" + filename); 
				}
			}
		}
	}
	
	window.onbeforeunload = function(){
		if (zipping > 0){
			return 'Your zipping progress might not be finished. Are you sure you want to leave?';
		}else if (uploading > 0){
			return 'Your upload task is still in progress. Are you sure you want to leave?';
		}else{
			
		}
	  
	};
	
	function copy(){
		if (lastClicked != -1){
			if (PermissionMode == 0){
				ShowNotice("<i class='paste icon'></i>Permission Denied.");
				return;
			}
			if (lastClicked < dirs.length){
				//This is a folder
				//ShowNotice("<i class='copy icon'></i>Folder copying is not supported.");
				//Folder copy is now supported with "copy_folder.php"
				var file = globalFilePath[lastClicked];
				clipboard = file;
				ShowNotice("<i class='paste icon'></i>Folder copied.");
				cutting = false;
			}else{
				//This is a file
				var file = globalFilePath[lastClicked];
				var ext = GetFileExt(file);
				if (ext == "php" || ext == "js"){
					ShowNotice("<i class='paste icon'></i>System script cannot be copied via this interface.");
				}else{
					clipboard = file;
					ShowNotice("<i class='paste icon'></i>File copied.");
					cutting = false;
				}
				
			}
		}else{
			//When the page just initiate
			ShowNotice("<i class='copy icon'></i>There is nothing to copy.");
		}
		
	}
	
	function cut(){
		if (lastClicked != -1){
			if (PermissionMode == 0){
				ShowNotice("<i class='cut icon'></i>Permission Denied.");
				return;
			}
			if (lastClicked < dirs.length){
				//This is a folder
				//ShowNotice("<i class='copy icon'></i>Folder copying is not supported.");
				//Folder copy is now supported with "copy_folder.php"
				var file = globalFilePath[lastClicked];
				clipboard = file;
				ShowNotice("<i class='cut icon'></i>Folder ready to move.");
				cutting = true;
				
			}else{
				//This is a file
				var file = globalFilePath[lastClicked];
				var ext = GetFileExt(file);
				if (ext == "php" || ext == "js"){
					ShowNotice("<i class='cut icon'></i>System script cannot be cut via this interface.");
				}else{
					clipboard = file;
					ShowNotice("<i class='cut icon'></i>File ready to move.");
					cutting = true;
				}
				
			}
		}else{
			//When the page just initiate
			ShowNotice("<i class='copy icon'></i>There is nothing to cut.");
		}
	}
	
	function paste(){
		if (PermissionMode == 0){
			return;
		}
		var finalPath = currentPath;
		var cutted = cutting;
		cutting = false;
		if (clipboard == ""){
			ShowNotice("<i class='paste icon'></i>There is nothing to paste.");
		}else if (GetFileExt(GetFileNameFrompath(clipboard)).trim() == GetFileNameFrompath(clipboard)){
			//If the paste target is a folder instead
			var target = finalPath + "/" + GetFileNameFrompath(clipboard);
			ShowNotice("<i class='paste icon'></i>Pasting in progress...");
			$.get( "copy_folder.php?from=" + clipboard + "&target=" + target, function(data) {
				if (data.includes("DONE")){
					ShowNotice("<i class='paste icon'></i>Folder pasted. Refershing...");
					UpdateFileList(currentPath);
					if (cutted == true){
					//Remove the original folder if it is a cut operation
					$.get( "delete.php?filename=" + clipboard, function(data) {
						if (data.includes("ERROR") == false){
							UpdateFileList(currentPath);
						}else{
							ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
						}
					});
					}
				}else{
					console.log(data);
					ShowNotice("<i class='paste icon'></i>Paste Error. Error Message: <br>" + data.replace("ERROR.",""));
				}
				
			});
			
		}else{
			var target = finalPath + "/" + GetFileNameFrompath(clipboard);
			$.get( "copy.php?from=" + clipboard + "&copyto=" + target, function(data) {
				if (data.includes("DONE")){
					ShowNotice("<i class='paste icon'></i>File pasted. Refershing...");
					UpdateFileList(currentPath);
					if (cutted == true){
						//Remove the original file if it is a cut operation
						$.get( "delete.php?filename=" + clipboard, function(data) {
							if (data.includes("ERROR") == false){
								UpdateFileList(currentPath);
							}else{
								ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
							}
						});
					}
				}else{
					console.log(data);
					ShowNotice("<i class='paste icon'></i>Paste Error. Error Message: <br>" + data.replace("ERROR.",""));
				}
				
			});
		}
	}
	
	function ConfirmDelete(){
		if (lastClicked != -1 && PermissionMode == 2){
			deleteConfirmInProgress = true;
			if (lastClicked < dirs.length){
				//It is a dir
				var file = globalFilePath[lastClicked].replace("../../../","");
				var filename = $('#' + lastClicked).html().split('</i>').pop().replace("</div>");
				$('#dname').html("Folder Name: " + filename);
				$('#drname').html("Storage Name: " + file.replace(currentPath.replace("../../../","") + "/",""));
				$('#dfpath').html("Full Path: " + file);
				deletePendingFile = globalFilePath[lastClicked];
				$('#delConfirm').fadeIn('fast');
			}else{
				//It is a file
				var file = globalFilePath[lastClicked].replace("../../../","");
				var filename = $('#' + lastClicked).html().split('</i>').pop().replace("</div>");
				var ext = GetFileExt(file);
				$('#dname').html("File Name: " + filename);
				$('#drname').html("Storage Name: " + file.replace(currentPath.replace("../../../","") + "/",""));
				$('#dfpath').html("Full Path: " + file);
				deletePendingFile = globalFilePath[lastClicked];
				$('#delConfirm').fadeIn('fast');
			}
		}
	}
	
	function deleteFile(){
		if (PermissionMode < 2){
			return;
		}
		deleteConfirmInProgress = false;
		$('#delConfirm').fadeOut('fast');
		if (deletePendingFile != ""){
			//Delete the path
			$.get( "delete.php?filename=" + deletePendingFile, function(data) {
				if (data.includes("ERROR") == false){
					ShowNotice("<i class='checkmark icon'></i> File removed.");
					UpdateFileList(currentPath);
				}else{
					ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
				}
			});
		}
	}
	
	function GetFileNameFrompath(path){
		var basename = path.replace(/\\/g,'/').replace(/.*\//, '');
		return basename;
	}
	
	function ShowNotice(text){
		$('#noticeCell').stop();
		$('#noticeContent').html(text);
		$('#noticeCell').fadeIn("slow").delay(3000).fadeOut("slow");
	}
	
	function openFolder(id){
		currentPath = globalFilePath[id];
		if (currentPath.includes(startingPath)){
			UpdateFileList(currentPath);
		}
	}
	
	//New Folder Naming Monitoring 
	$("#efcb").change(function() {
		if(this.checked) {
			//use hex encoding
			$('#newfoldername').css('background-color','#caf9d1');
			hexFolderName = true;
		}else{
			//use normal encoding
			$('#newfoldername').css('background-color','white');
			hexFolderName = false;
			$('#newfoldername').val($('#newfoldername').val().replace(/[^a-z0-9]/gmi, " ").replace(/\s+/g, " "));
		}
	});
	
	//Rename File Naming Monitoring
	$("#efcbr").change(function() {
		if(this.checked) {
			//use hex encoding
			if (renamingFolderID < dirs.length){
				$('#renameFileName').css('background-color','#caf9d1');
			}else{
				$('#renameFileName').css('background-color','#D8F0FF');
			}
			
			hexFolderName = true;
		}else{
			//use normal encoding
			$('#renameFileName').css('background-color','white');
			hexFolderName = false;
			$('#renameFileName').val($('#renameFileName').val().replace(/[^a-z0-9]/gmi, " ").replace(/\s+/g, " "));
		}
	});
	
	$('#newfoldername').on('input', function() {
		if (!hexFolderName){
			$('#newfoldername').val($('#newfoldername').val().replace(/[^a-z0-9]/gmi, " ").replace(/\s+/g, " "));
		}
	});
	
	$('#newfoldername').on('keypress', function (e) {
        if(e.which === 13){
			CreateNewFolder();
		}
	});
	 
	$(window).scroll(function(e){
		var pos = $(this).scrollTop();
		if (pos > 200){
			//Fix the menu bar to the top of the window
			$("#controls").css("position","fixed");
			$("#controls").css("top","0px");
			$("#controls").css("left","5px");
			$("#controls").css("right","5px");
			$("#controls").css("z-index","99");
			$("#controls").css("background-color","white");
			if (!embeddedMode){
				$("#sideControlPanel").css("top",$("#controls").position().top + $("#controls").outerHeight(true));
			}
		}else{
			//Let go the menu bar
			$("#controls").css("position","");
			$("#controls").css("background-color","");
			$("#controls").css("top","");
			$("#controls").css("left","");
			$("#controls").css("right","");
			if (!!embeddedMode){
				$("#sideControlPanel").css("bottom","");
				$("#sideControlPanel").css("top","9%");
			}
			
		}
	});
	 
	function newFolder(){
		enableHotKeys = false;
		$('#newFolderWindow').fadeIn('fast');
		$('#newfoldername').val("");
		newFolderPath = currentPath;
	}
	
	function CreateNewFolder(){
		var foldername = $('#newfoldername').val();
		var bin2hex = $("#efcb").is(":checked");
		//alert(newFolderPath + "/" + foldername + " bin2hex=" + $("#efcb").is(":checked"));
		$.post( "newFolder.php", { folder: newFolderPath, foldername: foldername, hex: bin2hex}).done(function( data ) {
			if (data.includes("DONE")){
				UpdateFileList(currentPath);
				$('#newFolderWindow').fadeOut('fast');
				enableHotKeys = true;
			}else{
				ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
			}
		});
	}
	
	function stripHTML(id){
		return $("#" + id).clone().children().remove().end().text();
	}
	
	function rename(){
		if (lastClicked != -1){
			if (PermissionMode < 2){
				ShowNotice("<i class='text cursor icon'></i>Permission Denied.");
				return;
			}
			var useSpecialEncoding = false;
			var warning = '<div class="sub header">Filename must only contain Alphabets, Numbers and Space.<br> Please tick the "Encoded Filename" option for other special characters.</div>';
			var selectedFilename = stripHTML(lastClicked);
			$('#oldRenameFileName').val(selectedFilename);
			$('#renameFileName').val(selectedFilename);
			$('#oldRenameFileName').css("background-color",$('#' + lastClicked).css('background-color'));
			if ($('#' + lastClicked).css('background-color') != "rgb(233, 233, 233)"){
				//This might be file using UMformat or folder using bin2hex format.
				$('#efcbr').prop('checked',true);
				useSpecialEncoding = true;
			}else{
				$('#efcbr').prop('checked',false);
				$('#renameFileName').css('background-color','#E9E9E9')
			}
			if (lastClicked < dirs.length){
				//This is a folder
				enableHotKeys = false;
				$('#renameFileWindow').fadeIn('fast');
				$('#renameTitle').html("Rename Folder" + warning);
				$('#renameIcon').removeClass('file').addClass('folder');
				if (useSpecialEncoding) $('#renameFileName').css('background-color','#caf9d1');
			}else{
				//This is a file
				enableHotKeys = false;
				$('#renameFileWindow').fadeIn('fast');
				$('#renameTitle').html("Rename File" + warning);
				$('#renameIcon').removeClass('folder').addClass('file');
				if (useSpecialEncoding) $('#renameFileName').css('background-color','#D8F0FF');
			}
			renamingFolderID = lastClicked;
		}else{
			//When the page just initiate
			ShowNotice("<i class='text cursor  icon'></i>There is nothing to rename.");
		}
	}
	
	function confirmRename(){
		var renameFile = globalFilePath[renamingFolderID];
		var newFileName = currentPath + "/" + $('#renameFileName').val();
		var isHex = $('#efcbr').prop('checked');
		console.log(renameFile,newFileName,isHex);
		if (isHex){
			$.get( "rename.php?file=" + renameFile + "&newFileName=" + newFileName + "&hex=true", function(data) {
				if (data.includes("DONE")){
					UpdateFileList(currentPath);
					$('#renameFileWindow').fadeOut('fast');
					enableHotKeys = true;
				}else{
					ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
				}
			});
		}else{
			$.get( "rename.php?file=" + renameFile + "&newFileName=" + newFileName + "&hex=false", function(data) {
				if (data.includes("DONE")){
					UpdateFileList(currentPath);
					$('#renameFileWindow').fadeOut('fast');
					enableHotKeys = true;
				}else{
					ShowNotice("<i class='remove icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
				}
			});
		}
	}
	
	$('#renameFileName').on('keypress', function (e) {
         if(e.which === 13){
			confirmRename();
		 }
	 });
	 
	 function convertFileName(){
		 if (lastClicked != -1){
			if (PermissionMode < 2){
				ShowNotice("<i class='text cursor icon'></i>Permission Denied.");
				return;
			}else{
				//This function convert the filename to hex or hex back to bin
				 $.get( "filename_switch.php?filename=" + globalFilePath[lastClicked], function(data) {
					 console.log("filename_switch.php?filename=" + globalFilePath[lastClicked]);
					if (data.includes("DONE")){
						UpdateFileList(currentPath);
					}else{
						ShowNotice("<i class='exchange outline icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
					}
				});
			}
		 }
	 }
	 
	 function previewUplaodFileList(){
		var inp = document.getElementById('multiFiles');
		$('#ulFileList').toggle();
		$('#ulFileListItems').html("");
		for (var i = 0; i < inp.files.length; ++i) {
		  var filename = inp.files.item(i).name;
		  $('#ulFileListItems').append('<div class="item">' + filename + "</div>");
		}
		if (inp.files.length == 0){
			$('#ulFileListItems').append('<div class="item">' + "No selected files" + "</div>");
		}
	 }
	 
	 $('#multiFiles').on("change", function(){
		var inp = document.getElementById('multiFiles');
		$('#ulFileListItems').html("");
		for (var i = 0; i < inp.files.length; ++i) {
		  var filename = inp.files.item(i).name;
		  $('#ulFileListItems').append('<div class="item">' + filename + "</div>");
		}
		if (inp.files.length == 0){
			$('#ulFileListItems').append('<div class="item">' + "No selected files" + "</div>");
		}
	 });
	 
	 function prepareUpload(){
		 if (uploading > 0){
			 ShowNotice("<i class='upload icon'></i>Another upload task is running.<br>Please wait until the previous one is finished.");
			 return;
		 }
		 $('#uploadFileWindow').fadeIn('fast');
		 prepareUplaodPath = currentPath.replace("../../../../../../../","/").replace("../../../","AOB/");
		 $('#uploadTarget').val(prepareUplaodPath);
		 enableHotKeys = false;
	 }
	 
	 function closeUploadWindow(){
		 prepareUplaodPath = "";
		 enableHotKeys = true;
	 }
	 
	 $('#uploadFilesBtn').on('click', function () {
                    var form_data = new FormData();
                    var ins = document.getElementById('multiFiles').files.length;
                    for (var x = 0; x < ins; x++) {
                        form_data.append("files[]", document.getElementById('multiFiles').files[x]);
                    }
					 $('#uploadFileWindow').fadeOut('fast');
					 ShowNotice("<i class='upload icon'></i>The upload will be processed in the background.<br>Please wait until the process is finished.");
					 uploading++;
                    $.ajax({
                        url: 'filesUploadHandler.php?path=' + prepareUplaodPath, 
                        dataType: 'text', 
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: form_data,
                        type: 'post',
                        success: function (data) {
                            //Sucess
							uploading--;
							if (data.includes("DONE")){
								closeUploadWindow();
								ShowNotice("<i class='upload icon'></i>File upload suceed.");
								UpdateFileList(currentPath);
							}else{
								//Php return error code
								ShowNotice("<i class='upload icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
							}
							
                        },
                        error: function (data) {
                            ShowNotice("<i class='upload icon'></i> Something went wrong. Error Message: <br>" + data.replace("ERROR.",""));
							uploading--;
                        }
                    });
	 });
	 
	 function htmlEncode(value){
	  return $('<div/>').text(value).html();
	}

	function htmlDecode(value){
	  return $('<div/>').html(value).text();
	}
	</script>
	
	<?php
	function getRelativePath($from, $to)
	{
		// some compatibility fixes for Windows paths
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from     = explode('/', $from);
		$to       = explode('/', $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}
	
	?>
</body>
</html>