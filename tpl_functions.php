<?php
if (!defined('DOKU_INC')) die();

// parse url defined in menu
function menulink($id) {
    if(strpos($id, 'http') === 0) {
        return $id;
    } elseif(strpos($id, 'www.') === 0) {
        return $id;
    }
    if(strpos($id, "#")){
        $anchor = substr($id, strpos($id, "#"));
        $id = substr($id, 0, strpos($id, "#"));
    } else {
        $anchor = "";
    }
    if(strpos($id, "?")){
        parse_str(substr($id, strpos($id, "?")+1), $urlParameters);
        $id = substr($id, 0, strpos($id, "?"));
    } else {
        $urlParameters = '';
    }
    return wl($id, $urlParameters) . $anchor;
}

// generate menu-array either from json or from index
function get_menu() {
    global $conf;
    
    $defaultmenu = json_decode('
    {
    "level1": {":wiki": "Start", ":error": "ERROR: Could not parse JSON menufile"},
    "level2": {
        ":wiki": {":wiki:welcome" : "Wiki", ":playground" : "Playground"}
    },
    "level3": {
        ":wiki:welcome": {":wiki:welcome": "Welcome", ":wiki:dokuwiki": "DokuWiki", ":wiki:syntax": "Syntax"}
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
            if(auth_quickaclcheck($id) < AUTH_READ) continue;
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
    global $ID;
$menu = get_menu();
echo '<ul class="navigation-l1"> ';
foreach($menu['level1'] as $url => $title){
    $active = (strpos(trim($ID,':'), trim($url,':')) === 0) ? "active": ""; 
    if(array_key_exists($url, $menu['level2'])){
    	echo '<li class="flyout ', $active,'">
              <a href="'.menulink($url).'">', $title, '</a>
    	      <div class="dropdown">
    		  <ul class="navigation-l2">';
    	foreach($menu['level2'][$url] as $url2 => $title2) {
    		if(array_key_exists($url2, $menu['level3'])){
                echo '<li class="has-submenu">
                      <a href="', menulink($url2), '">', $title2, '</a>
                      <div class="submenu">
                      <ul class="navigation-l3">
                      ';
                foreach($menu['level3'][$url2] as $url3 => $title3) {
    			    echo '<li class=""><a href="', menulink($url3), '">', $title3, '</a></li>';
                }
                echo '</ul></div></li>';
    		} else {
                echo '<li class=""><a href="', menulink($url2), '">', $title2, '</a></li>';
    		}
    	}
    	echo '</ul></div></li>';
    } else {
    	echo '<li class="',$active,'"><a href="', menulink($url), '">', $title, '</a></li>';
    }
}
if($_SERVER['REMOTE_USER'] && tpl_getConf('menu') == 'file' && (auth_quickaclcheck(tpl_getConf('menusite')) >= AUTH_EDIT)) {
    	echo '<li class=""><small><a href="',wl(tpl_getConf('menusite'), array('do'=>'edit')), '">Edit</a></small></li>';
}
echo '</ul>';
}

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
            echo '<li><a href="', wl($url) ,'">', ucfirst($title) ,'</a></li>';
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
