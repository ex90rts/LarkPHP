<div class="headerBox">
	<div class="headerContent">
        <div class="logoBox"></div>
        <input type="text" class="searchInput" placeholder="请输入关键词按回车键搜索" />
        <div class="tabsBox">
            <ul class="tabs">
                <li<?php if ($this->tab=='HOME'){echo ' class="on"';}?>><a href="/test/index.php?action=index" title="首页"><span class="comm">//</span>go(<span class="cons">HOME</span>);</a></li>
                <li<?php if ($this->tab=='TECH'){echo ' class="on"';}?>><a href="/test/index.php?action=post/list&id=0201" title="技术"><span class="comm">//</span>go(<span class="cons">TECH</span>);</a></li>
                <li<?php if ($this->tab=='LIFE'){echo ' class="on"';}?>><a href="/test/index.php?action=post/list&id=0201" title="生活"><span class="comm">//</span>go(<span class="cons">LIFE</span>);</a></li>
                <li<?php if ($this->tab=='ABOUT'){echo ' class="on"';}?>><a href="/test/index.php?action=about" title="关于"><span class="comm">//</span>go(<span class="cons">ABOUT</span>);</a></li>
                <li<?php if ($this->tab=='LOGIN'){echo ' class="on"';}?>><a href="/test/index.php?action=user/login" title="登录"><span class="comm">//</span>go(<span class="cons">LOGIN</span>);</a></li>
            </ul>
        </div>
        <div style="clear:both"></div>
    </div>
</div>