<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8" />
<link rel="icon" type="image/x-icon" href="/favicon.ico" />
<link rel="stylesheet" href="css/style.css" />
<meta name="description" content="Build software better, together." />
<title></title>
<script type="text/javascript" src="js/jquery-1.10.1.min.js"></script>
<script type="text/javascript" src="libs/markitup/jquery.markitup.js"></script>
<link rel="stylesheet" type="text/css" href="libs/markitup/skins/markitup/style.css" />
<link rel="stylesheet" type="text/css" href="libs/markitup/sets/markdown/style.css" />
<script type="text/javascript" >
var mySettings = {
    nameSpace:          'markdown', // Useful to prevent multi-instances CSS conflict
    previewParserPath:  '/test/index.php?action=post/preview',
    onShiftEnter:       {keepDefault:false, openWith:'\n\n'},
    markupSet: [
        {name:'First Level Heading', key:"1", placeHolder:'Your title here...', closeWith:function(markItUp) { return miu.markdownTitle(markItUp, '=') } },
        {name:'Second Level Heading', key:"2", placeHolder:'Your title here...', closeWith:function(markItUp) { return miu.markdownTitle(markItUp, '-') } },
        {name:'Heading 3', key:"3", openWith:'### ', placeHolder:'Your title here...' },
        {name:'Heading 4', key:"4", openWith:'#### ', placeHolder:'Your title here...' },
        {name:'Heading 5', key:"5", openWith:'##### ', placeHolder:'Your title here...' },
        {name:'Heading 6', key:"6", openWith:'###### ', placeHolder:'Your title here...' },
        {separator:'---------------' },        
        {name:'Bold', key:"B", openWith:'**', closeWith:'**'},
        {name:'Italic', key:"I", openWith:'_', closeWith:'_'},
        {separator:'---------------' },
        {name:'Bulleted List', openWith:'- ' },
        {name:'Numeric List', openWith:function(markItUp) {
            return markItUp.line+'. ';
        }},
        {separator:'---------------' },
        {name:'Picture', key:"P", replaceWith:'![[![Alternative text]!]]([![Url:!:http://]!] "[![Title]!]")'},
        {name:'Link', key:"L", openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', placeHolder:'Your text to link here...' },
        {separator:'---------------'},    
        {name:'Quotes', openWith:'> '},
        {name:'Code Block / Code', openWith:'(!(\t|!|`)!)', closeWith:'(!(`)!)'},
        {separator:'---------------'},
        {name:'Preview', call:'preview', className:"preview"}
    ]
}

$(document).ready(function() {
	$("#content").markItUp(mySettings);
	$(".newpostButton").click(function(){
		$(".newpostForm").submit();
	});
});
</script>
</head>

<body>

<?php $this->template('header.php'); ?>

<div class="contentBox">
	<div class="newpostBox">
	<form name="NewpostForm" method="post" class="newpostForm" action="/test/index.php?action=post/newpost">
    	<div class="newpostFormHeader">
        	<h2>Add New Post</h2>
        </div>
    	<div class="newpostFormBody">
        	<label for="title">Title</label>
            <input autocapitalize="off" autofocus="autofocus" class="input-block" id="title" name="login" tabindex="1" type="text">
            <label for="tags">Tags</label>
            <input autocapitalize="off" autofocus="autofocus" class="input-block" id="tags" name="login" tabindex="2" type="text">
            <label for="content">Content</label>
            <textarea id="content" tabindex="3"></textarea>
            <a href="#" class="newpostButton">Submit</a>
        </div>
    </form>
    </div>
</div>

<?php $this->template('footer.php'); ?>

</body>
</html>
