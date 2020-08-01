<?php
if (!defined('DOKU_INC')) die();

// generate menu-array either from json or from index
function get_menu() {
    global $conf;
    
    $defaultmenu = json_decode('
    {
    "level1": {":start": "Start", ":cat1": "Reiter1", ":cat2": "Reiter2"},
    "level2": {
        ":cat1": {":link1" : "Kategorie1", ":link2" : "Kategorie2"},
        ":cat2": {":link3" : "Kategorie3", ":link4" : "Kategorie4"}
    },
    "level3": {
        "link1": {":link1": "Sub-Kategorie1", "link2" : "Sub-Kategorie2"},
        "link2": {":link1": "Sub-Kategorie3", "link2" : "Sub-Kategorie4"}
    }  
    }
    ', true);
    $menu = $defaultmenu;

    if(tpl_getConf('menu') == 'index') {
        $menu = array("level1" => array(), "level2" => array(), "level3" => array());
        $data = idx_get_indexer()->getPages();
		$cleanindexlist = array_merge(
                            explode(',', tpl_getConf('cleanindexlist')),
                            array('sidebar')
                        );
        foreach($data as $id){
            if(isHiddenPage($id)) continue;
            if(auth_aclcheck($id,'',array()) < AUTH_READ) continue;
			$path = '';
			if(in_array($id, $cleanindexlist)) continue;
			foreach(explode(':', $id) as $lvl => $ns) {
			    if(in_array($ns, $cleanindexlist)) continue;
				$pathpre = $path;
				$path .= ($lvl>0) ? ':' . $ns : $ns;
            	$title = p_get_first_heading($path);
            	$title = is_null($title) ? $ns : $title;
				if($lvl == 0 && !array_key_exists($path, $menu['level1'])) $menu['level1'][$path] = $title;	
				if($lvl > 0) {
					if(is_array($menu['level'. ($lvl+1)][$pathpre])) {
						$menu['level'.($lvl+1)][$pathpre][$path] = $title;
					} else {
						$menu['level'.($lvl+1)][$pathpre] = array($path => $title);
					}
				}
			}
        }
        //print("<pre>".print_r($data,true)."</pre>");
        //print("<pre>".print_r($menu,true)."</pre>");
        return $menu;
    }
    $menufile = tpl_getConf('menusite');
    if(@file_exists(wikiFN($menufile)) && auth_quickaclcheck($menufile) >= AUTH_READ) {
        $content = utf8_encode(io_readWikiPage(wikiFN($menufile), $menufile));
        preg_match('/<MENU>(.*?)<\/MENU>/s', $content, $menu);
        $menu = ((count($menu) > 0)) ? json_decode($menu[1], true) : $defaultmenu;
        if(is_null($menu)) $menu = $defaultmenu;
    }
    return $menu;
}

//render drop-down html menu from menu-array
function dropdown_menu() {
$menu = get_menu();
echo '<ul class="navigation-l1"> ';
foreach($menu['level1'] as $url => $title){
    if(array_key_exists($url, $menu['level2'])){
    	echo '<li class="flyout">
              <a href="', wl($url), '">', $title, '</a>
    	      <div class="dropdown">
    		  <ul class="navigation-l2">';
    	foreach($menu['level2'][$url] as $url2 => $title2) {
    		if(array_key_exists($url2, $menu['level3'])){
                echo '<li class="has-submenu">
                      <a href="', wl($url2), '">', $title2, '</a>
                      <div class="submenu">
                      <ul class="navigation-l3">
                      ';
                foreach($menu['level3'][$url2] as $url3 => $title3) {
    			    echo '<li class=""><a href="', wl($url3), '">', $title3, '</a></li>';
                }
                echo '</ul></div></li>';
    		} else {
    			echo '<li class=""><a href="', wl($url2), '">', $title2, '</a></li>';
    		}
    	}
    	echo '</ul></div></li>';
    } else {
    	echo '<li class=""><a href="', wl($url), '">', $title, '</a></li>';
    }
}
echo '</ul>';
}

//TODO
//render breadcrump-like bar from menu-array
function trace() {
    global $ID, $conf;
    $trace = explode(':', $ID);
    $num = count($trace);
    if($num == 1 && $trace[0] == $conf['start']) return ;
    $menu = get_menu();
    echo '
    <div class="list">
        <a href="', wl($conf['start']),'">', tpl_getConf("institute_".$conf['lang']), '</a>
        <ul>';
        foreach($menu['level1'] as $url => $title) {
            echo '<li><a href="', wl($url) ,'">', $title ,'</a></li>';
        }
        echo '</ul>
    </div>';
    $path = '';
    foreach($trace as $n => $ns) {
    	$path .= ($n>0) ? ':' . $ns : $ns;
    	$title = p_get_first_heading($path);
    	$title = is_null($title) ? $ns : $title;
        if($n == $num-1) {
            echo '<div class="list last">';
        } else {
            echo '<div class="list">';
        }
        echo '<a href="', wl($path) ,'">', $title ,'</a>';
        if(array_key_exists($path, $menu['level'.($n+2)])){
            echo '<ul>';
            foreach($menu['level'.($n+2)][$path] as $u => $t){
                echo '<li><a href="', wl($u) ,'">', $t ,'</a></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }
}

?>
