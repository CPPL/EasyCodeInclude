<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.easycodeinclude
 * @copyright   Copyright (C) 2012 - ##CURYEAR## Craig Phillips Pty Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class plgContentEasyCodeInclude extends JPlugin
{
    /**
     * Plugin that loads the specified URL within content.
     *
     * @param   string  $context   The context of the content being passed to the plugin.
     *
     * @param   object  &$article  The article object.  Note $article->text is also available
     *
     * @param   object  &$params   The article params
     *
     * @param   int     $page      The 'page' number
     *
     * @return  null
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        // Don't run this plugin when the content is being indexed
        if ($context == 'com_finder.indexer') {
            return true;
        }

        // Simple performance check to determine whether bot should process further
        if (strpos($article->text, 'easycodeinc') === false) {
            return true;
        }

        // Expression to search for (positions)
        $regex      = '/{easycodeinc\s+(.*?)}/i';
        $useGoogle  = $this->params->def('use_google', 1);
        $inc_js     = $this->params->def('inc_js', 1);
        $inc_css    = $this->params->def('inc_css', 1);
        $wrapper    = $this->params->def('wrapper', 2);
        $defTheme   = $this->params->def('theme', 'prettify');
        $prevThemes = '';
        $useCache   = $this->params->def('force_cache', 1);

        // Find all instances of plugin and put in $matches for easycodeinc
        // $matches[0] is full pattern match, $matches[1] is the position
        preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

        // No matches, skip this
        if ($matches) {
            // We can load the prettyfier.js & css and our kickoff js
            $doc = JFactory::getDocument();

            if ($inc_js && !$useGoogle) {
                $kickoffjs = "addEventListener('load', function (event) { prettyPrint() }, false);";
                $doc->addScriptDeclaration($kickoffjs);
                $doc->addScript('/plugins/content/easycodeinclude/prettify/prettify.js');
            }

            foreach ($matches as $match) {

                $matcheslist = explode(',', $match[1]);

                $url = trim($matcheslist[0]);

                // We may not have a theme so get the plugin default.
                if (!array_key_exists(1, $matcheslist) || $matcheslist[1] == ' ' || $matcheslist[1] == '') {
                    $matcheslist[1] = $defTheme;
                } else {
                    switch ($matcheslist[1]) {
                        case '1':
                            $matcheslist[1] = 'desert';
                            break;
                        case '2':
                            $matcheslist[1] = 'sunburst';
                            break;
                        case '3':
                            $matcheslist[1] = 'sons-of-obsidian';
                            break;
                        case '4':
                            $matcheslist[1] = 'doxy';
                            break;
                        default:
                            $matcheslist[1] = 'prettify';
                            break;
                    }
                }
                $theme = trim($matcheslist[1]);

                // Make sure the owner wants the theme css loaded and that we haven't load one before
                if ($inc_css) {
                    if (($theme == '') || (trim($theme) == '')) {
                        $theme = $defTheme;
                    }

                    if (empty($prevThemes)) {
                        $prevThemes = $theme;
                    } else {
                        $theme = $prevThemes;
                    }
                    if ($inc_css && !$useGoogle) {
                        $doc->addStyleSheet("/plugins/content/easycodeinclude/prettify/$theme.css");
                    }

                    // We may not have a lang so get if from the URL.
                    if (!array_key_exists(2, $matcheslist) || $matcheslist[2] == ' ' || $matcheslist[2] == '') {
                        $lastSeg = substr($url, strrpos($url, '/') + 1);
                        $fs = substr($lastSeg, strrpos($lastSeg, '.') + 1);
                        $matcheslist[2] = $fs;
                    }

                    $lang = trim($matcheslist[2]);

                    // Line numbers?
                    $linenumbers = '';

                    if (array_key_exists(3, $matcheslist)) {
                        $linenumbers = trim($matcheslist[3]);
                    }

                    if ($useGoogle) {
                        if ($lang) {
                            $lang = 'lang=' . $lang . '&';
                        }
                        $doc->addScript('https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js?' . $lang . 'skin=' . $theme);
                    }


                    // Range of lines
                    $lines = '';

                    if (array_key_exists(4, $matcheslist)) {
                        $lines = explode('-', $matcheslist[4]);
                        $lines = count($lines) == 2 ? $lines : '';
                    }

                    // Get the file
                    $fileOutput = $this->getCodeFile($url, $useCache);

                    // If we have something...
                    if ($fileOutput) {
                        // Do we want the whole file or just a section
                        if ($lines != '') {
                            $outputArray = preg_split("/(\r\n|\n|\r)/", $fileOutput);
                            $start = (int)$lines[0];
                            $end = (int)$lines[1];

                            if ($end < $start) {
                                list($start, $end) = array($end, $start);

                                if ($start == 0) {
                                    $start = 1;
                                }
                            }

                            $end++;
                            $selectedLines = array_slice($outputArray, ($start - 1), ($end - $start));
                            $output = implode(PHP_EOL, $selectedLines);
                        } else {
                            $output = $fileOutput;
                        }

                        // Time to wrap the output
                        switch ($wrapper) {
                            case 1:
                                $code = '<code class="prettyprint lang-' . $lang . ' ' . $linenumbers . '">' . $output . '</code>';
                                break;
                            case 2:
                                $code = '<pre class="prettyprint' . ' ' . $linenumbers . '"><code class="lang-' . $lang . '">' . $output . '</code></pre>';
                                break;
                            default:
                                $code = '<pre class="prettyprint lang-' . $lang . ' ' . $linenumbers . '">' . $output . '</pre>';
                                break;
                        }
                        $article->text = preg_replace("|$match[0]|", $code, $article->text, 1);
                    }
                }
            }
        }
    }

    /**
     * Get a copy of the file.
     *
     * @param   string  $url       The URL of the file to retreive (needs to ba raw file).
     *
     * @param   string  $useCache  Do we cache this?
     *
     * @return bool|mixed
     */
    private function getCodeFile($url, $useCache)
    {
        $jAp     = JFactory::getApplication();

        // Setup the cache
        $plg_context = 'plg_content_easycodeinclude';
        $cache = JFactory::getCache($plg_context, '');

        if ($useCache) {
            $cache->setCaching($useCache);
        }

        $hashURL = md5($url);
        $output = $cache->get($hashURL);

        // If it's not in cache let go get it...
        if (!$output) {
            // Is cURL installed yet?
            if (!function_exists('curl_init')) {
                $jAp->enqueueMessage(JText::_('PLG_EASYCODEINCLUDE_CURL_NOT_INSTALLED'), 'Notice');

                return false;
            }
            // Load user_profile plugin language
            $lang = JFactory::getLanguage();
            $lang->load($plg_context, JPATH_BASE . '/plugins/content/easycodeinclude');

            $codeURL = curl_init($url);

            // Include header in result?
            curl_setopt($codeURL, CURLOPT_HEADER, 0);

            // We want the data returned not printed
            curl_setopt($codeURL, CURLOPT_RETURNTRANSFER, 1);

            // Timeout in seconds
            curl_setopt($codeURL, CURLOPT_TIMEOUT, 10);

            // Download the given URL
            $output = curl_exec($codeURL);

            if ($output == '') {
                $jAp->enqueueMessage(JText::_('PLG_EASYCODEINCLUDE_CURL_WASNT_ABLE_TO_GET_URL'), 'Notice');
            }

            $output = str_replace('<', '&lt;', $output);
            $cache->store($output, $hashURL, $plg_context);

            // Close the cURL resource, and free system resources
            curl_close($codeURL);
        }
        return $output;
    }
}
