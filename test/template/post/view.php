<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8" />
<link rel="icon" type="image/x-icon" href="/favicon.ico" />
<link rel="stylesheet" href="css/style.css" />
<meta name="description" content="Build software better, together." />
<script type="text/javascript" src="libs/highlight.js/highlight.pack.js"></script>
<link rel="stylesheet" type="text/css" href="libs/highlight.js/styles/monokai.css" />
<script>hljs.initHighlightingOnLoad();</script>
<title></title>
</head>

<body>

<?php $this->template('header.php'); ?>

<div class="contentBox">
	<div class="contentLeft">
    	<div class="articleNode">
            <div class="articleTitle">
            	<img class="mainTag" src="images/tags/php.png" border="0" />
                <i class="line"></i>
                <div class="titleRight">
                    <h3><a href="/test/index.php?action=post/view&uid=<?php echo $this->id;?>"><?php echo $this->title;?></a></h3>
                    <div class="articleTags">
                    	<?php foreach($this->tags as $tag){?><a href="/test/index.php?action=post/tag&uid=<?php echo $tag['uid'];?>"><?php echo $tag['tag'];?></a><span>/</span><?php }?><a class="created"><?php echo date('Y.m.d', $tihs->created);?></a>
                    </div>
                </div>
                <div class="commentNum">99</div>
                <div style="clear:both"></div>
            </div>
            <div class="articleContent">
                <?php echo $this->htmlContent;?>
            </div>
            <div class="articleInfo">
                <div class="reviews">
                	<div class="review-good">12</div>
                    <div class="review-bad">2</div>
                    <div style="clear:both"></div>
                </div>
                <div class="readmore">
                	<a href="/test/index.php?action=post/view&uid=<?php echo $this->id;?>">阅读全文</a>
                </div>
                <div style="clear:both"></div>
            </div>
    	</div>
    </div>
    
    <div class="contentRight">
        <h3 class="sideTitle">我的连接</h3>
        <div class="snsLinks">
        	<a href="http://www.weibo.com/samoay" target="_blank"><img src="images/sns/weibo-small.png" border="0" /></a>
            <a href="http://www.facebook.com/Samoay" target="_blank"><img src="images/sns/facebook-small.png" border="0" /></a>
            <a href="https://twitter.com/Samoay" target="_blank"><img src="images/sns/twitter-small.png" border="0" /></a>
            <a href="https://github.com/samoay" target="_blank"><img src="images/sns/github-small.png" border="0" /></a>
            <a href="https://plus.google.com/116034800456010545162" target="_blank"><img src="images/sns/google-plus-small.png" border="0" /></a>
            <div style="clear:both"></div>
        </div>
        
        <h3 class="sideTitle">标签云</h3>
        <div class="tagCloud">
        	<a href="#">PHP</a>
            <a href="#">Javascript</a>
            <a href="#">Java</a>
            <a href="#">MySQL</a>
            <a href="#">Objective-C</a>
            <a href="#">OOP</a>
            <a href="#">MongoDB</a>
            <a href="#">Apple</a>
            <a href="#">iOS</a>
            <a href="#">Android</a>
            <a href="#">JS</a>
            <a href="#">Node.js</a>
            <a href="#">Sqlite</a>
            <a href="#">SQL</a>
            <a href="#">nginx</a>
            <a href="#">Linux</a>
            <a href="#">MVC</a>
            <a href="#">Eclipse</a>
            <a href="#">Apache</a>
            <a href="#">php-fpm</a>
            <div style="clear:both"></div>
        </div>
        
        <h3 class="sideTitle">最近更新</h3>
        <div class="newList">
        	<ul>
                <li><a href="#">Nodejs擅长什么？</a></li>
                <li><a href="#">PHP 魔术方法详解</a></li>
                <li><a href="#">国外App Engine平台有哪些？</a></li>
                <li><a href="#">Linux命令行下SSH端口转发设定</a></li>
                <li><a href="#">Using Screen on Mac OS X</a></li>
                <li><a href="#">iOS图片拉伸技巧</a></li>
                <li><a href="#">iOS开发中常见问题集锦</a></li>
                <li><a href="#">优化UITableView性能</a></li>
            </ul>
        </div>
        
        <h3 class="sideTitle">热门排行</h3>
        <div class="hotList">
        	<ul>
                <li><a href="#">Nodejs擅长什么？</a></li>
                <li><a href="#">PHP 魔术方法详解</a></li>
                <li><a href="#">国外App Engine平台有哪些？</a></li>
                <li><a href="#">Linux命令行下SSH端口转发设定</a></li>
                <li><a href="#">Using Screen on Mac OS X</a></li>
                <li><a href="#">iOS图片拉伸技巧</a></li>
                <li><a href="#">iOS开发中常见问题集锦</a></li>
                <li><a href="#">优化UITableView性能</a></li>
            </ul>
        </div>
        
    </div>
    
    <div style="clear:both"></div>
</div>

<?php $this->template('footer.php'); ?>

</body>
</html>
