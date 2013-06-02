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
    previewParserPath:  '~/sets/markdown/preview.php',
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
});
</script>
</head>

<body>
<!--[if lte IE 8]>
    <div class="error chromeframe">您正在使用<strong>漏洞百出</strong>的浏览器，为了正常地访问本网站，请升级您的浏览器 <a target="_blank" href="http://browsehappy.com">立即升级</a></div>
<![endif]-->

<div class="headerBox">
	<div class="headerContent">
        <div class="logoBox"></div>
        <input type="text" class="searchInput" placeholder="请输入关键词按回车键搜索" />
        <div class="tabsBox">
            <ul class="tabs">
                <li><a href="index.html" title="首页"><span class="comm">//</span>go(<span class="cons">HOME</span>);</a></li>
                <li class="on"><a href="#" title="技术"><span class="comm">//</span>go(<span class="cons">TECH</span>);</a></li>
                <li><a href="#" title="生活"><span class="comm">//</span>go(<span class="cons">LIFE</span>);</a></li>
                <li><a href="#" title="关于"><span class="comm">//</span>go(<span class="cons">ABOUT</span>);</a></li>
                <li><a href="login.html" title="登录"><span class="comm">//</span>go(<span class="cons">LOGIN</span>);</a></li>
            </ul>
        </div>
        <div style="clear:both"></div>
    </div>
</div>

<div class="contentBox">
	<div class="newpostBox">
	<form method="post" class="newpostForm">
    	<div class="newpostFormHeader">
        	<h2>Add New Post</h2>
        </div>
    	<div class="newpostFormBody">
        	<label for="title">Title</label>
            <input autocapitalize="off" autofocus="autofocus" class="input-block" id="login_field" name="login" tabindex="1" type="text">
            <label for="tags">Tags</label>
            <input autocapitalize="off" autofocus="autofocus" class="input-block" id="login_field" name="login" tabindex="1" type="text">
            <label for="content">Content</label>
            <textarea id="content"></textarea>
            <a href="#" class="newpostButton">Submit</a>
        </div>
    </form>
    </div>
</div>

<div class="footerBox">
	<div class="copyright">samoay.me © 20[1-5]+[0-9]+</div>
</div>

</body>
</html>
