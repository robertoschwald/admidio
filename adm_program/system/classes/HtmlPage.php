<?php
/**
 ***********************************************************************************************
 * @copyright 2004-2019 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * Creates an Admidio specific complete html page
 *
 * This class creates a html page with head and body and integrates some Admidio
 * specific elements like css files, javascript files and javascript code. It
 * also provides some methods to easily add new html data to the page. The generated
 * page will automatically integrate the chosen theme. You can optional disable the
 * integration of the theme files.
 *
 * **Code example**
 * ```
 * // create a simple html page with some text
 * $page = new HtmlPage();
 * $page->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS_CLIENT . '/jquery/jquery.min.js');
 * $page->setHeadline('A simple Html page');
 * $page->addHtml('<strong>This is a simple Html page!</strong>');
 * $page->show();
 * ```
 */

class HtmlPage extends \Smarty
{
    /**
     * @var string The title for the html page and the headline for the Admidio content.
     */
    protected $title = '';
    /**
     * @var string Additional header that could not be set with the other methods. This content will be add to head of html page without parsing.
     */
    protected $header = '';
    /**
     * @var string The main headline for the html page.
     */
    protected $headline = '';
    /**
     * @var string Contains the custom html of the current page. This will be added to the default html of each page.
     */
    protected $pageContent = '';
    /**
     * @var MenuNode An object that represents all functions of the current page that should be shown in the default menu
     */
    protected $menuNodePageFunctions;
    /**
     * @var bool If set to true then the custom html code of the theme for each page will be included.
     */
    protected $showThemeHtml = true;
    /**
     * @var bool If set to true then the menu will be included.
     */
    protected $showMenu = true;
    /**
     * @var bool Flag if the current page has a navbar.
     */
    protected $hasNavbar = false;
    /**
     * @var array<int,string> An array with all necessary cascading style sheets files for the html page.
     */
    protected $cssFiles = array();
    /**
     * @var array<int,string> An array with all necessary javascript files for the html page.
     */
    protected $jsFiles = array();
    /**
     * @var array<int|string,string> An array with all necessary rss files for the html page.
     */
    protected $rssFiles = array();
    /**
     * @var bool A flag that indicates if the page should be styled in print mode then no colors will be shown
     */
    protected $printView = false;
    /**
     * @var string Contains the custom javascript of the current page. This will be added to the header part of the page.
     */
    protected $javascriptContent = '';
    /**
     * @var string Contains the custom javascript of the current page that should be executed after pageload. This will be added to the header part of the page.
     */
    protected $javascriptContentExecute = '';
    /**
     * @var string Contains the url to the previous page. If a url is set than a link to this page will be shown under the headline
     */
    protected $urlPreviousPage = '';
    /**
     * @var bool If set to true then a page without header menue and sidebar menu will be created. The main template file will be index_inline.tpl
     */
    protected $modeInline = false;


    /**
     * Constructor creates the page object and initialized all parameters
     * @param string $headline A string that contains the headline for the page that will be shown in the <h1> tag.
     */
    public function __construct($headline = '')
    {
        global $gSettingsManager;

        $this->menuNodePageFunctions = new MenuNode('admidio-menu-page-functions', $headline);

        $this->setHeadline($headline);

        parent::__construct();

        // initialize php template engine smarty
        $this->setTemplateDir(ADMIDIO_PATH . FOLDER_THEMES . '/' . $gSettingsManager->getString('theme') . '/templates/');
        $this->setCacheDir(ADMIDIO_PATH . FOLDER_DATA . '/template/cache/');
        $this->setCompileDir(ADMIDIO_PATH . FOLDER_DATA . '/template/compile/');
        $this->setConfigDir(ADMIDIO_PATH . FOLDER_LIBS_SERVER . '/smarty/configs/');

        if ($gSettingsManager->has('system_browser_update_check') && $gSettingsManager->getBool('system_browser_update_check'))
        {
            $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS_CLIENT . '/browser-update/browser-update.js');
        }
    }

    /**
     * Adds a cascading style sheets file to the html page.
     * @param string $cssFile The url with filename or the relative path starting with **adm_program** of the css file.
     */
    public function addCssFile($cssFile)
    {
        if (!in_array($cssFile, $this->cssFiles, true))
        {
            if (StringUtils::strStartsWith($cssFile, 'http'))
            {
                $this->cssFiles[] = $cssFile;
            }
            else
            {
                $this->cssFiles[] = $this->getDebugOrMinFilepath($cssFile);
            }
        }
    }

    /**
     * Adds a RSS file to the html page.
     * @param string $rssFile The url with filename of the rss file.
     * @param string $title   (optional) Set a title. This is the name of the feed and will be shown when adding the rss feed.
     */
    public function addRssFile($rssFile, $title = '')
    {
        if ($title !== '')
        {
            $this->rssFiles[$title] = $rssFile;
        }
        elseif (!in_array($rssFile, $this->rssFiles, true))
        {
            $this->rssFiles[] = $rssFile;
        }
    }

    /**
     * Adds a javascript file to the html page.
     * @param string $jsFile The url with filename or the relative path starting with **adm_program** of the javascript file.
     */
    public function addJavascriptFile($jsFile)
    {
        if (!in_array($jsFile, $this->jsFiles, true))
        {
            if (StringUtils::strStartsWith($jsFile, 'http'))
            {
                $this->jsFiles[] = $jsFile;
            }
            else
            {
                $this->jsFiles[] = $this->getDebugOrMinFilepath($jsFile);
            }
        }
    }

    /**
     * Adds any javascript content to the page. The javascript will be added in the order you call this method.
     * @param string $javascriptCode       A valid javascript code that will be added to the header of the page.
     * @param bool   $executeAfterPageLoad (optional) If set to **true** the javascript code will be executed after
     *                                     the page is fully loaded.
     */
    public function addJavascript($javascriptCode, $executeAfterPageLoad = false)
    {
        if ($executeAfterPageLoad)
        {
            $this->javascriptContentExecute .= $javascriptCode. "\n";
        }
        else
        {
            $this->javascriptContent .= $javascriptCode. "\n";
        }
    }

    /**
     * Add content to the header segment of a html page.
     * @param string $header Content for the html header segment.
     */
    public function addHeader($header)
    {
        $this->header .= $header;
    }

    /**
     * Adds any html content to the page. The content will be added in the order
     * you call this method. The first call will place the content at the top of
     * the page. The second call below the first etc.
     * @param string $html A valid html code that will be added to the page.
     */
    public function addHtml($html)
    {
        $this->pageContent .= $html;
    }

    /**
     * Add a new menu item to the page menu part. This is only the menu that will show functions of the
     * current page. The menu header will automatically the name of the page. If a dropdown menu item should
     * be created than $parentMenuItemId must be set to each entry of the dropdown. If a badge should
     * be shown at this menu item than set the $badgeCount.
     * @param string $id.         Id of the menu item that will be the html id of the <a> tag
     * @param string $name        Name of the menu node that will also shown in the menu
     * @param string $url         The url of this menu item that will be called if someone click the menu item
     * @param string $icon        An icon that will be shown together with the name in the menu
     * @param string $parentMenuItemId The id of the parent item to which this item will be added.
     * @param string $badgeCount  If set > 0 than a small badge with the number will be shown after the menu item name
     * @param string $description A optional description of the menu node that could be shown in some output cases
     */
    public function addPageFunctionsMenuItem($id, $name, $url, $icon, $parentMenuItemId = '', $badgeCount = 0, $description = '')
    {
        $this->menuNodePageFunctions->addItem($id, $name, $url, $icon, $parentMenuItemId, $badgeCount, $description);
    }

    /**
     * The method will return the filename. If you are in debug mode than it will return the
     * not minified version of the filename otherwise it will return the minified version.
     * Therefore you must provide 2 versions of the file. One with a **min** before the file extension
     * and one version without the **min**.
     * @param string $filepath Filename of the NOT minified file.
     * @return string Returns the filename in dependence of the debug mode.
     */
    private function getDebugOrMinFilepath($filepath)
    {
        global $gDebug;

        $fileInfo = pathinfo($filepath);
        $filename = basename($fileInfo['filename'], '.min');

        $filepathDebug = '/' . $fileInfo['dirname'] . '/' . $filename . '.'     . $fileInfo['extension'];
        $filepathMin   = '/' . $fileInfo['dirname'] . '/' . $filename . '.min.' . $fileInfo['extension'];

        if ((!$gDebug && is_file(ADMIDIO_PATH . $filepathMin)) || !is_file(ADMIDIO_PATH . $filepathDebug))
        {
            return ADMIDIO_URL . $filepathMin;
        }

        return ADMIDIO_URL . $filepathDebug;
    }

    /**
     * Returns the headline of the current Admidio page. This is the text of the <h1> tag of the page.
     * @return string Returns the headline of the current Admidio page.
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /* Add page specific javascript files, css files or rss files to the header. Also specific header
     * informations will also be added
     * @return string Html string with all additional header informations
     */
    public function getHtmlAdditionalHeader()
    {
        $this->header .= $this->getHtmlCssFiles() . $this->getHtmlJsFiles() . $this->getHtmlRssFiles();
        return $this->header;
    }

    // add css files to page
    public function getHtmlCssFiles()
    {
        $html = '';

        foreach ($this->cssFiles as $cssFile)
        {
            $html .= '<link rel="stylesheet" type="text/css" href="' . $cssFile . '" />'."\n";
        }

        return $html;
    }

    // add javascript files to page
    public function getHtmlJsFiles()
    {
        $html = '';

        foreach ($this->jsFiles as $jsFile)
        {
            $html .= '<script type="text/javascript" src="' . $jsFile . '"></script>'."\n";
        }

        return $html;
    }

    // add rss feed files to page
    public function getHtmlRssFiles()
    {
        $html = '';

        foreach ($this->rssFiles as $title => $rssFile)
        {
            if (!is_numeric($title))
            {
                $html .= '<link rel="alternate" type="application/rss+xml" title="' . $title . '" href="' . $rssFile . '" />'."\n";
            }
            else
            {
                $html .= '<link rel="alternate" type="application/rss+xml" href="' . $rssFile . '" />'."\n";
            }
        }

        return $html;
    }

    /**
     * Returns the title of the html page.
     * @return string Returns the title of the html page.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Flag if the current page has a navbar.
     * @return void
     */
    public function hasNavbar()
    {
        $this->hasNavbar = true;
    }

    /**
     * Every html page of Admidio contains a menu.
     * If the menu should not be included in the current page, than this method must be called.
     * @return void
     */
    public function hideMenu()
    {
        $this->showMenu = false;
    }

    /**
     * Every html page of Admidio contains three files of the custom theme.
     * my_header.php, my_body_top.php and my_body_bottom.php
     * With these files the administrator can contain custom layout to Admidio.
     * If these files should not be included in the current page, than this method must be called.
     * @return void
     */
    public function hideThemeHtml()
    {
        $this->showThemeHtml = false;
    }

    /**
     * Set the h1 headline of the current html page. If the title of the page
     * was not set until now than this will also be the title.
     * @param string $headline A string that contains the headline for the page.
     * @return void
     */
    public function setHeadline($headline)
    {
        if ($this->title === '')
        {
            $this->setTitle($headline);
        }

        $this->headline = $headline;
    }

    /** If set to true then a page without header menue and sidebar menu will be created.
     *  The main template file will be **index_reduced.tpl** instead of index.tpl.
     */
    public function setInlineMode()
    {
        $this->modeInline = true;
    }

    /**
     * Set the title of the html page that will be shown in the <title> tag.
     * @param string $title A string that contains the title for the page.
     * @return void
     */
    public function setTitle($title)
    {
        global $gCurrentOrganization;

        if ($title === '')
        {
            $this->title = $gCurrentOrganization->getValue('org_longname');
        }
        else
        {
            $this->title = $gCurrentOrganization->getValue('org_longname') . ' - ' . $title;
        }
    }

    /**
     * If print mode is set then the reduced template file **index_reduced.tpl** will be loaded with
     * a print specific css file **print.css**. All styles will be more print compatible and are
     * only black, grey and white.
     * @return void
     */
    public function setPrintMode()
    {
        $this->setInlineMode();
        $this->printView = true;
    }

    /**
     * Set a url to the previous page that will be shown as link on the page after the headline.
     * @param string $url The url to the previous page. This must be a valid url.
     */
    public function setUrlPreviousPage($url)
    {
        //$this->urlPreviousPage = admFuncCheckUrl($url);
        $this->urlPreviousPage = $url;
    }

    /**
     * This method send the whole html code of the page to the browser.
     * Call this method if you have finished your page layout.
     */
    public function show()
    {
        global $gDebug, $gMenu, $gCurrentOrganization, $gCurrentUser, $gValidLogin, $gL10n, $gSettingsManager, $gSetCookieForDomain;

        $urlImprint = '';
        $urlDataProtection = '';

        // add page functions menu to global menu
        $gMenu->addFunctionsNode($this->menuNodePageFunctions);

        $this->assign('additionalHeaderData', $this->getHtmlAdditionalHeader());
        $this->assign('title', $this->title);
        $this->assign('headline', $this->headline);
        $this->assign('urlPreviousPage', $this->urlPreviousPage);
        $this->assign('organizationName', $gCurrentOrganization->getValue('org_longname'));
        $this->assign('urlAdmidio', ADMIDIO_URL);
        $this->assign('urlTheme', THEME_URL);
        $this->assign('javascriptContent', $this->javascriptContent);
        $this->assign('javascriptContentExecuteAtPageLoad', $this->javascriptContentExecute);

        $this->assign('userId', $gCurrentUser->getValue('usr_id'));
        $this->assign('validLogin', $gValidLogin);
        $this->assign('debug', $gDebug);
        $this->assign('registrationEnabled', $gSettingsManager->getBool('registration_enable_module'));

        $this->assign('printView', $this->printView);
        $this->assign('menuSidebar', $gMenu->getHtml());
        $this->assign('content', $this->pageContent);

        // add imprint and data protection
        if ($gSettingsManager->has('system_url_imprint') && strlen($gSettingsManager->getString('system_url_imprint')) > 0)
        {
            $urlImprint = $gSettingsManager->getString('system_url_imprint');
        }
        if ($gSettingsManager->has('system_url_data_protection') && strlen($gSettingsManager->getString('system_url_data_protection')) > 0)
        {
            $urlDataProtection = $gSettingsManager->getString('system_url_data_protection');
        }
        $this->assign('urlImprint', $urlImprint);
        $this->assign('urlDataProtection', $urlDataProtection);

        // show cookie note
        if ($gSettingsManager->has('system_cookie_note') && $gSettingsManager->getBool('system_cookie_note'))
        {
            $this->assign('cookieNote', $gSettingsManager->getBool('system_cookie_note'));
            $this->assign('cookieDomain', DOMAIN);
            $this->assign('cookiePrefix', COOKIE_PREFIX);

            if ($gSetCookieForDomain)
            {
                $this->assign('cookiePath', '/');
            }
            else
            {
                $this->assign('cookiePath', ADMIDIO_URL_PATH . '/');
            }

            if ($gSettingsManager->has('system_url_data_protection') && strlen($gSettingsManager->getString('system_url_data_protection')) > 0)
            {
                $this->assign('cookieDataProtectionUrl', '"href": "'. $gSettingsManager->getString('system_url_data_protection') .'", ');
            }
            else
            {
                $this->assign('cookieDataProtectionUrl', '');
            }
        }

        // add translation object
        $this->assign('l10n', $gL10n);

        if($this->modeInline)
        {
            $this->display('index_reduced.tpl');
        }
        else
        {
            $this->display('index.tpl');
        }
    }
}
