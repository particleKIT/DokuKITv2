<?php

class DokuKitV2Menu {
    private $startpage = null;
    private $menupage = null;
    private $menu = [];
    private $openPath = [];

    /**
     * Initialize the menu
     */
    public function __construct() {
        global $conf;

        $translation = plugin_load('helper','translation');

        $this->menupage = tpl_getConf('menusite');

        if ($translation) {
            list($this->menupage,) = $translation->buildTransID($conf['lang'], $this->menupage);
            $this->menupage = cleanID($this->menupage);
        }

        $this->startpage = $conf['start'];

        if ($translation) {
            list($this->startpage,) = $translation->buildTransID($conf['lang'], $conf['start']);
            $this->startpage = cleanID($this->startpage);
        }

        $this->loadMenu();
        $this->calculateOpenPath();
    }

    /**
     * Get the id of the (possibly translated) start page
     * @return string The id of the start page
     */
    public function getStartPage() {
        return $this->startpage;
    }

    /**
     * Print the global dropdown and mobile menu
     */
    function printDropdownMenu() {
        global $lang;

        echo '<ul class="navigation-l1"> ';

        $current_level = 1;
        foreach ($this->menu as $key => $item) {
            if (!isset($item['page'])) continue;

            while ($item['lvl'] < $current_level) {
                echo '</ul></div></li>';
                $current_level--;
            }

            $li_classes = [];
            if (in_array($key, $this->openPath)) {
                $li_classes[] = 'active';
            }

            $has_children = !empty($item['children']);
            if ($has_children) {
                if ($item['lvl'] == 1) {
                    $li_classes[] = 'flyout';
                } else {
                    $li_classes[] = 'has-submenu';
                }
            }

            echo '<li class="'.implode(' ', $li_classes) . '">';
            echo '<a href="'.hsc($this->menulink($item['page'])).'">', hsc($item['title']), '</a>';

            if ($has_children) {
                $div_classes = [];
                if (in_array($key, $this->openPath)) {
                    $div_classes[] = 'current';
                }
                if ($item['lvl'] == 1) {
                    $div_classes[] = 'dropdown';
                } else {
                    $div_classes[] = 'submenu';
                }

                echo '<div class="'.implode(' ', $div_classes) . '">';

                echo '<ul class="navigation-breadcrumb">';
                echo '<li class="home"><button><span>Start</span></button></li>';
                foreach ($item['parents'] as $pkey) {
                    echo '<li><button>'.hsc($this->menu[$pkey]['title']).'</button></li>';
                }
                echo '<li><span>'.hsc($item['title']).'</span></li>';
                echo '</ul>';

                echo '<a class="parent" href="'.hsc($this->menulink($item['page'])).'">', hsc($item['title']), '</a>';

                echo '<ul class="navigation-l'.($item['lvl'] + 1).'">';
                $current_level++;
            } else {
                echo '</li>';
            }
        }
        while ($current_level > 1) {
            echo '</ul></div></li>';
            $current_level--;
        }
        echo '<li class="home"><a href="', wl($this->startpage), '"><span>', tpl_getLang('startpage'), '</span></a></li>';

        if($_SERVER['REMOTE_USER'] && tpl_getConf('menu') == 'file' && auth_quickaclcheck($this->menupage) >= AUTH_EDIT) {
            echo '<li class=""><small><a href="',wl($this->menupage, array('do'=>'edit')), '">',$lang['btn_secedit'],'</a></small></li>';
        }
        echo '</ul>';
    }

    /**
     * Print the breadcrumb-like menu
     */
    public function printTrace() {
        global $ID, $conf;

        if ($ID == $this->startpage) return;

        echo '
    <div class="list">
        <a href="', wl($this->startpage),'">', tpl_getConf("institute_".$conf['lang']), '</a>
        <ul>';
        foreach($this->menu['']['children'] as $l1page) {
            $l1 = $this->menu[$l1page];
            echo '<li><a href="', hsc($this->menulink($l1['page'])) ,'">', hsc($l1['title']) ,'</a></li>';
        }
        echo '</ul>
    </div>';

        $num = count($this->openPath);
        for($i = 0; $i < $num; $i++) {
            $id = $this->openPath[$i];
            if ($id === '') continue;
            $item = $this->menu[$id];

            if ($i < $num - 1) {
                echo '<div class="list">';
            } else {
                echo '<div class="list last">';
            }

            echo '<a href="', hsc($this->menulink($item['page'])) ,'">', hsc($item['title']);


            if(!empty($item['children'])){
                if ($i == $num - 1) {
                    echo '<span class="caret"></span>';
                    echo '<span class="more"></span>';
                }
                echo '</a>';

                echo '<ul>';
                foreach($item['children'] as $cpage){
                    $citem = $this->menu[$cpage];
                    echo '<li><a href="', hsc($this->menulink($citem['page'])) ,'">', hsc($citem['title']) ,'</a></li>';
                }
                echo '</ul>';

            } else {
                echo '</a>';
            }

            echo '</div>';
        }
    }

    /**
     * Return a URL for the specified @a $id or @a $id if it already is a URL
     *
     * @param string $id The id to work on
     *
     * @return string The link
     */
    private function menulink(string $id) {
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

    /**
     * Load the menu stored in @a $this->menupage
     *
     * Adapted from the navi plugin by Andreas Gohr <gohr@cosmocode.de>
     */
    private function loadMenu() {
        if(tpl_getConf('menu') == 'index') {
            $this->menu['']['children'] = ['notsupported'];
            $this->menu['notsupported'] = array(
                'parents' => [],
                'page' => 'notsupported',
                'title' => 'Index menu is currently not supported',
                'lvl' => 1,
                'children' => []
            );
            return;
        }
        if (page_exists($this->menupage) && auth_quickaclcheck($this->menupage) >= AUTH_READ) {
            $file = wikiFN($this->menupage);
            $instructions = p_cached_instructions($file, false, $this->menupage);
            if ($instructions) {
                // prepare some vars
                $max = count($instructions);
                $pre = true;
                $lvl = 0;
                $parents = array();
                $parent = '';
                $key = '';
                $cnt = 0;

                // build a lookup table
                $this->menu = [];
                for ($i = 0; $i < $max; $i++) {
                    if ($instructions[$i][0] == 'listu_open') {
                        $pre = false;
                        $lvl++;
                        if ($key) {
                            array_push($parents, $key);
                            $parent = $key;
                        }
                    } elseif ($instructions[$i][0] == 'listu_close') {
                        $lvl--;
                        array_pop($parents);
                        $parent = $parents[count($parents) - 1];
                    } elseif ($pre || $lvl == 0) {
                        unset($instructions[$i]);
                    } elseif ($instructions[$i][0] == 'listitem_close') {
                        $cnt++;
                    } elseif ($instructions[$i][0] == 'internallink') {
                        $foo = true;
                        $page = $instructions[$i][1][0];
                        resolve_pageid(getNS($this->menupage), $page, $foo); // resolve relative to sidebar ID

                        if (auth_quickaclcheck($page) < AUTH_READ) continue;

                        $title = $instructions[$i][1][1];
                        if (empty($title)) {
                            $title = p_get_first_heading($page);
                        }
                        if (is_null($title)) {
                            $title = ucfirst($page);
                        }

                        $key = $page;
                        while (isset($this->menu[$key])) {
                            $key = '_' . $key;
                        }
                        $this->menu[$key] = array(
                            'parents' => $parents,
                            'page' => $page,
                            'title' => $title,
                            'lvl' => $lvl,
                            'children' => []
                        );

                        $this->menu[$parent]['children'][] = $key;

                    } elseif ($instructions[$i][0] == 'externallink') {
                        $url = $instructions[$i][1][0];
                        $title = $instructions[$i][1][1];
                        if (is_null($title)) $title = $url;
                        $key = '_'.$url;
                        while (isset($this->menu[$key])) {
                            $key = '_' . $key;
                        }

                        $this->menu[$key] = array(
                            'parents' => $parents,
                            'page' => $url,
                            'title' => $title,
                            'lvl' => $lvl,
                            'children' => []
                        );

                        $this->menu[$parent]['children'][] = $key;
                    }
                }
            }
        }
    }

    /**
     * Create a "path" of items above the current page
     *
     * Adapted from the navi plugin by Andreas Gohr <gohr@cosmocode.de>
     *
     * @param array $menu List of navigation items
     * @return array
     */
    private function calculateOpenPath()
    {
        global $INFO;
        $this->openPath = [];
        if (isset($this->menu[$INFO['id']])) {
            $this->openPath = (array)$this->menu[$INFO['id']]['parents']; // get the "path" of the page we're on currently
            array_push($this->openPath, $INFO['id']);
        } else {
            $ns = $INFO['id'];

            // traverse up for matching namespaces
            if ($this->menu) {
                do {
                    $ns = getNS($ns);
                    $try = "$ns:";
                    $foo = true;
                    resolve_pageid('', $try, $foo);
                    if (isset($this->menu[$try])) {
                        // got a start page
                        $this->openPath = (array)$this->menu[$try]['parents'];
                        array_push($this->openPath, $try);
                        break;
                    } else {
                        // search for the first page matching the namespace
                        foreach ($this->menu as $key => $junk) {
                            if (getNS($key) == $ns) {
                                $this->openPath = (array)$this->menu[$key]['parents'];
                                array_push($this->openPath, $key);
                                break 2;
                            }
                        }
                    }

                } while ($ns);
            }
        }
    }
}

