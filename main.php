<?php
// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();

// include custom template functions stolen from arctic template
require_once(dirname(__FILE__).'/tpl_functions.php');

// load translation plugin (if present)
$translation = plugin_load('helper','translation');


echo '
<!DOCTYPE html>
<html lang="', $conf['lang'], '" dir="', $lang['direction'], '">
    <head>
        <meta charset="utf-8" />
        <title>',
            tpl_getConf("title_prefix"), 
            // print title without language namespace
            preg_replace('/^'.$conf['lang'].':/','',tpl_pagetitle(null, true)),
        '</title>',
        // set viewport for mobile devices
        '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
tpl_metaheaders();

//TODO SORT OUT THE JAVASCRIPT/CSS STUFF WE REALLY NEED AND STORE IT LOCALLY
echo tpl_favicon(array('favicon', 'mobile')), '
<script src="https://static.scc.kit.edu/kit-2020/js/jquery-3.4.1.min.js"></script>
<script src="https://static.scc.kit.edu/kit-2020/js/main.js"></script>
<script src="https://static.scc.kit.edu/kit-2020/js/kit.js"></script>
<script src="https://static.scc.kit.edu/fancybox/dist/jquery.fancybox.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://kit-cd.scc.kit.edu/global_stylesheet.php.css">
<link rel="stylesheet" href="https://static.scc.kit.edu/fancybox/dist/jquery.fancybox.min.css" />

    </head>
    <body class="oe-page" vocab="http://schema.org/" typeof="WebPage" class="oe-page" vocab="http://schema.org/" typeof="WebPage">
    <header class="page-header">
        <div class="content-wrap">
            <div class="logo"><a href="http://www.kit.edu" title="KIT-Logo - Link zur KIT-Startseite"><img src="'.DOKU_URL.'/lib/tpl/dokukitv2/images/KITLogo.png" /></a></div>

            <div class="navigation">
                <div class="navigation-meta">
                    <ul class="navigation-meta-links">
                        <li>', html_wikilink(':' . $conf['start'], 'Home') ,'</li>';
 echo '                <li><a accesskey="8" href="https://kit.edu/impressum.php">Imprint</a></li>
                        <li><a href="https://kit.edu/datenschutz.php">Privacy</a></li>
                        <li><a href="',  DOKU_URL ,'doku.php?do=index">Sitemap</a></li>
                        <li><a href="https://www.kit.edu">KIT</a></li>
';

if($_SERVER['REMOTE_USER']) echo (new \dokuwiki\Menu\UserMenu())->getListItems();
echo '              </ul>';
echo '<div class="navigation-language">';
if ($translation) {
    if(!$_SERVER['REMOTE_USER']) $conf['plugin']['translation']['checkage'] = 0;
    echo $translation->showTranslations();
    if(null != tpl_getConf("institute_".$conf['lang'])) $conf['title'] = tpl_getConf("institute_".$conf['lang']);
} else {
   echo strtoupper($conf['lang']);
}
echo '</div>';

//search bar
echo ' 
		<div class="navigation-search">
        	<div class="search-form">
              <form action="', wl(),'" method="get" role="search" id="dw__search" accept-charset="utf-8">
                <input type="hidden" name="do" value="search" />
                <input type="hidden" name="id" value="', $ID ,'" />
                <input id="meta_search_input" type="search" name="q" placeholder="suchen" aria-label="suchen" size="1" required="required" />
                <button value="1" type="submit"><span>suchen</span></button>
                <div id="qsearch__out" class="ajax_qsearch JSpopup"></div>
              </form>
            </div>
			<a id="meta_search_label" class="search-trigger" title="suchen" href="#"><span>suchen</span></a>
        </div>
	    </div>';
echo '<nav class="navigation-main">', dropdown_menu(), '</nav>';



// side tools when scrolling and mobile view
//TODO: need the javascript/jquery events here
echo '<ul class="side-widgets">
<li class="meta"><a accesskey="1" href="">Home</a></li>
<li class="meta"><a href="/leichte_sprache.php">Leichte Sprache</a></li>
<li class="meta"><a accesskey="8" href="/impressum.php">Impressum</a></li>
<li class="meta"><a href="/datenschutz.php">Datenschutz</a></li>
<li class="meta"><a accesskey="3" href="/sitemap.php">Sitemap</a></li>
<li class="meta"><a href="http://intranet.kit.edu">Intranet</a></li>
<li class="search">
	<a title="suchen"><span>suchen</span></a>
    <div class="search-form">
          <form action="', wl(),'" method="get" role="search" id="dw__search" accept-charset="utf-8">
               <input type="hidden" name="do" value="search" />
               <input type="hidden" name="id" value="', $ID ,'" />
               <input type="search" name="q" placeholder="suchen" aria-label="suchen" size="1" required="required"/>
               <button value="1" type="submit"><span>suchen</span></button>
           </form>
       </div>
</li>
</ul>';

echo '</div></div>
</header><main>';
if(
    $ID ==  $conf['start'] &&
    $ACT != 'diff' && 
    $ACT != 'edit' && 
    $ACT != 'preview' && 
    $ACT != 'admin' && 
    $ACT != 'login' && 
    $ACT != 'logout' && 
    $ACT != 'profile' && 
    $ACT != 'revisions' && 
    $ACT != 'index' && 
    $ACT != 'search' && 
    $ACT != 'media'
) {
    /* default images from https://pixabay.com/illustrations/universe-particles-vibration-line-1566161/*/
    echo '
<section class="stage stage-big">
    <img src="'.DOKU_URL.'/lib/tpl/dokukitv2/images/head-big.jpg" alt="', $conf['title'] ,'" loading="lazy" width="1920" height="700" />
    <div class="content-wrap"><p class="bigger" title="', $conf['title'] ,'">', $conf['title'] ,'</p></div>
</section>';
} else{
    echo '
<section class="stage stage-small">
    <img src="'.DOKU_URL.'/lib/tpl/dokukitv2/images/head-small.jpg" alt="', $conf['title'] ,'" loading="lazy"/>
    <div class="content-wrap">
        <a href="/index.php">', $conf['title'] ,'</a>
        <!-- <a href="/index.php"><img src="'.DOKU_URL.'/lib/tpl/dokukitv2/images/logo.jpg" alt="SCC-Logo" /></a> --!>
    </div>
</section>';
}
html_msgarea();
// BREADCRUMBS
echo '<section class="breadcrumbs-big">';
echo    '<div class="content-wrap">';
            trace();
echo '	</div>
</section>';

tpl_flush();
	
echo '<div class="side-widgets-trigger"></div>
<section class="content-wrap">
	<div class="content">
	<div class="KIT_section text full">';
	if($_SERVER['REMOTE_USER']) {
		echo '<div class="content-wrap" style="text-align:right;">';
    	foreach((new \dokuwiki\Menu\PageMenu())->getItems() as $item) {
  			echo '<a href="', $item->getLink(), '" title="', $item->getTitle(), '">
				  	<span class="icon">'.inlineSVG($item->getSvg()).'</span>
					<span class="a11y">'.$item->getLabel().'</span>
				  </a>';
		}
		echo '</div>
 <div class="side-widgets-trigger"></div>
';
	}
    tpl_content(false);
echo	'
	</div>
	</div>
	</div>
</section>
                    

    </main>

<button class="to-top-button" aria-label="scroll back to top"></button>


<footer class="page-footer">
            <div class="content-wrap">
                <div class="column full">
				    <div class="KIT_section text column fourth">';
					tpl_include_page(tpl_getConf("footer"), true, true);
echo '              </div>
                </div>
            </div>
            
            <div class="footer-meta-navigation">
                <div class="content-wrap">
                    <span class="copyright">', tpl_getLang('kitfooter') ,'</span>
                    <ul>
                        <li><a accesskey="1" href="/index.php">Home</a></li> 
                        <li><a accesskey="8" href="/impressum.php">Imprint</a></li>
                        <li><a href="/datenschutz.php">Privacy</a></li>
                        <li><a href="http://www.kit.edu"><span>KIT</span></a></li>';
    
if(!$_SERVER['REMOTE_USER']) {
    echo '<li>', (new \dokuwiki\Menu\Item\Login)->asHtmlLink('menuitem' ,false), '</li>';
} elseif (
    $ACT != 'login' &&
    $ACT != 'logout' &&
    $ACT != 'diff' &&
    $ACT != 'edit' &&
    $ACT != 'preview' &&
    $ACT != 'admin' &&
    $ACT != 'profile' &&
    $ACT != 'revisions') { 
    echo '<li>', tpl_pageinfo(true), '</li>';
}
 echo '             </ul>
                </div   >
            </div>
</footer>
';

if($_SERVER['REMOTE_USER']) tpl_indexerWebBug();

echo '</body>
    </html>';

?>
