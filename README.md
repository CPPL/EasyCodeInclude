# EasyCode Include
EasyCode Include is a simple Joomla! 3.x (and 2.5) content plugin that imports a raw source code file and inserts it inline in your articles or anywhere else a content plugin will work.

## Using EasyCode Include

### Results:

The image below shows the formatted output of a section of a PHP file included from a GitHub Repository.

![PHP Sample](readme_images/Default_Include_Example_-_PHP.png)

EasyCode Include loads the code file from a local webserver path or a website URL (e.g. from GitHub or any other URL that links to a raw code file) and applies Google Prettify syntax highlighting.

### It's not a Git Only World
In this example we're actually using a code file hosted on Google's Code repository - really it doesn't matter where the file is hosted as long as the URL you give the plugin returns just the code file. If the URL returns a HTML page with the code inside it the HTML will be formatted and presented in the page as well.

## Usage
### Syntax:

`{easycodeinc URL, THEMEX, LANG, LINENUMS:Y}`

The most common error message you will receive is that the URL is incorrect, more specifically it will look like this:

> Sorry cURL failed to retreive the page, is the URL correct?.

### Examples:

`{easycodeinc https://raw.github.com/user/project/branch/filename.suffix, 2, php}`


`{easycodeinc https://raw.github.com/user/project/branch/filename.suffix, 2, php, linenums:20}`


`{easycodeinc https://raw.github.com/user/project/branch/filename.suffix, , , linenums:12}`

**URL** - The URL should point to the raw code file and be of the form

```https://raw.github.com/user/project/branch/filename.suffix```

If the URL doesn't start with http but does start with a leading slash /  then EasyCode Include will attempt to load a local file relative to the root of the website. The example below will load a file in the /images/code_samples/ directory.

`{easycodeinc /images/code_samples/skynet.c}`

**THEME X** - This is an optional parameter, if its not provided the setting in the plugin will be used. If it is provided it must be from 0 to 3 where X is one of the following:

 0 - i.e. 0 = Default Theme  
 1 - i.e. 1 = Desert  
 2 - i.e. 2 = Sunburst  
 3 - i.e. 3 = Sons Of Obsidian  
 4 - i.e. 4 = Doxy

If you use multiple code blocks on a single page only the theme of the first code block will be used.

**LANG** - This is an optional parameter, if its not provided the file suffix from the URL will be used. If it is provided it overrides the URL's file suffix.

**LINENUMS:Y** - This is an optional parameter, if its provided Prettify will add line numbers to every 5th line. You can specify a starting line by using linenums:y where y is the first line number.

**RANGE X-Y** - This is an optional parameter, if its provided EasyCode Include will only display lines X through Y of the file. You must specify both a starting line and an end line number e.g. 20-30 where 20 is the first line number and 30 the last.

#### Notes:
- the plugin is called `plg_easycodeinclude` and can be [found here](https://extensions.joomla.org/extensions/extension/core-enhancements/coding-a-scripts-integration/easycode-include) on the [Joomla Extension Directory (JED)](http://extensions.joomla.org).

## Credits

EasyCode Include is made possible by Google Code Prettify
