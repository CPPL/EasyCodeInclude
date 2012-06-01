<?php
/**
 * @package		Joomla.Plugin
 * @subpackage	Content.loadmodule
 * @copyright	Copyright (C) 2012 Craig Phillips Pty Ltd. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class plgContentEasyGitHubinclude extends JPlugin
{
	/**
	 * Plugin that loads the specified github URL within content (actually it will load any url).
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The article object.  Note $article->text is also available
	 * @param	object	The article params
	 * @param	int		The 'page' number
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		// simple performance check to determine whether bot should process further
		if (strpos($article->text, 'githubinc') === false) {
			return true;
		}

		// expression to search for (positions)
		$regex      = '/{githubinc\s+(.*?)}/i';
		$inc_js     = $this->params->def('inc_js', 1);
		$inc_css    = $this->params->def('inc_css', 1);
		$wrapper    = $this->params->def('wrapper', 2);
		$defTheme   = $this->params->def('theme', 'prettify');
		$prevThemes = '';
		$useCache   = $this->params->def('force_cache', 1);

		// Find all instances of plugin and put in $matches for githubinc
		// $matches[0] is full pattern match, $matches[1] is the position
		preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);
		// No matches, skip this
		if ($matches) {
			// we can load the prettyfier.js & css and our kickoff js
			$doc = JFactory::getDocument();
			if($inc_js){
				$kickoffjs = "addEventListener('load', function (event) { prettyPrint() }, false);";
				$doc->addScriptDeclaration($kickoffjs);
				$doc->addScript('/plugins/content/easygithubinclude/prettify/prettify.js');
			}

			foreach ($matches as $match) {

				$matcheslist = explode(',', $match[1]);

				$url      = trim($matcheslist[0]);
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
						default:
							$matcheslist[1] = 'prettify';
							break;
					}
				}
				$theme    = trim($matcheslist[1]);
				// Make sure the owner wants the theme css loaded and that we haven't load one before
				if($inc_css && ($prevThemes != '')) {
					$doc->addStyleSheet("/plugins/content/easygithubinclude/prettify/$theme.css");
					$prevThemes = $theme;
				}

				// We may not have a lang so get if from the URL.
				if (!array_key_exists(2, $matcheslist) || $matcheslist[2] == ' ' || $matcheslist[2] == '') {
					$lastSeg = substr($url, strrpos($url, '/') + 1);
					$fs = substr($lastSeg, strrpos($lastSeg, '.') + 1);
					$matcheslist[2] = $fs;
				}
				$lang     = trim($matcheslist[2]);

				// Line numbers?
				$linenumbers = '';
				if(array_key_exists(3, $matcheslist)) {
					$linenumbers = trim($matcheslist[3]);
				}
				
				// Get the file
				$output = $this->_getGitHubFile($url, $theme, $useCache);
				// If we have something...
				if($output) {
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

	private function _getGitHubFile($url, $theme, $useCache)
	{
		$jAp     = JFactory::getApplication();
		// Setup the cache
		$plg_context = 'plg_content_easygithubinclude';
		$cache   = JFactory::getCache($plg_context,'');
		if($useCache)
			$cache->setCaching( $useCache );
		$hashURL = md5($url);
		$output = $cache->get($hashURL);

		// If it's not in cache let go get it...
		if(!$output)
		{
			$codeURL = curl_init($url);
			// is cURL installed yet?
		    if (!function_exists('curl_init')){
		        $jAp->enqueueMessage(JText::_('PLG_EASYGITHUBINCLUDE_CURL_NOT_INSTALLED'), 'Notice');
		    }
			// Load user_profile plugin language
			$lang = JFactory::getLanguage();
			$lang->load($plg_context, JPATH_BASE . '/plugins/content/easygithubinclude');

		 
		    // Include header in result?
		    curl_setopt($codeURL, CURLOPT_HEADER, 0);
		    // We want the data returned not printed
		    curl_setopt($codeURL,CURLOPT_RETURNTRANSFER,1);
		    // Timeout in seconds
		    curl_setopt($codeURL, CURLOPT_TIMEOUT, 10);
		 
		    // Download the given URL
		    $output = curl_exec($codeURL);
		    // Close the cURL resource, and free system resources
		    curl_close($codeURL);
			if ($output == '') {
				$jAp->enqueueMessage(JText::_('PLG_EASYGITHUBINCLUDE_CURL_WASNT_ABLE_TO_GET_URL'), 'Notice');
			}
			$output = str_replace('<','&lt;',$output);
			$cache->store($output, $hashURL,$plg_context);
		}
		return $output;
	} 
}
